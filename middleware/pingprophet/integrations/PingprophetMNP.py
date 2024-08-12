import aiohttp
import asyncio
import requests
import json
import os
import uuid
from integrations.BaseIntegration import BaseIntegration


class PingprophetMNP(BaseIntegration):
    def __init__(self, db, logger, background_tasks):
        super().__init__(db, logger, background_tasks)

        self.url = os.getenv('PING_PROPHET_URL', 'https://pingprophet.com')
        self.bearer_token = os.getenv('PING_PROPHET_API_KEY')

    async def test_api_key(self):
        timeout = aiohttp.ClientTimeout(total=5)

        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": "Bearer " + self.bearer_token,
            }
            async with session.get(f"{self.url}/api/v1/user", headers=headers) as resp:
                res = await resp.json()
                self.logger.debug("Test API Key response: %s", res)

                if resp.status != 200:
                    return {"ok": False, "message": res['message'] or "Invalid token"}

                return {"ok": True, "message": "API key is valid"}

    async def integration_deactivate(self, data):
        # timeout = aiohttp.ClientTimeout(total=5)

        await self.destroy_team_token(data)

        # async with aiohttp.ClientSession(timeout=timeout) as session:
        #     headers = {
        #         "Content-Type": "application/json",
        #         "Accept": "application/json",
        #         "Authorization": "Bearer " + self.bearer_token,
        #     }
        #     async with session.post(f"{self.url}/api/v1/integrations/deactivate", headers=headers) as resp:
        #         res = await resp.json()
        #         self.logger.debug("Integration Deactivate response: %s", res)

        #         if resp.status == 401:
        #             return { "ok": True, "message": "Integration already deactivated" }

        #         if resp.status != 200:
        #             return { "ok": False, "message": res['message'] or "Invalid token" }

        #         return { "ok": True, "message": "Integration deactivated" }
        return {"ok": True, "message": "Integration deactivated"}

    async def mnp_request(self, data):
        timeout = aiohttp.ClientTimeout(total=60)  # 1 minute
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": "Bearer " + self.bearer_token,
            }
            contacts = []
            for contact in data['data']:
                contact_item = {
                    "number": contact['phone_normalized'],
                    "foreign_id": contact['contact_id'] if 'contact_id' in contact else str(uuid.uuid4()),
                }
                contacts.append(contact_item)

            query_data = {
                "data": contacts,
                "callback_url": f"{os.getenv('CALLBACK_URL')}/PingprophetMNP"
            }
            async with session.post(f"{self.url}/api/v1/mnp", headers=headers, json=query_data) as resp:
                res = await resp.json()
                self.logger.debug("MNP Request response: %s", res)

                if resp.status != 200:
                    return {
                        "ok": False,
                        "message": res['message'] or "Invalid token",
                        "status_code": resp.status,
                        "notification": resp.status == 402,
                    }

                query = """
                    insert into ping_prophet_requests (id, team_id, integration_id, webhook_request_id) 
                    values (:id, :team_id, :integration_id, :webhook_request_id);
                """
                values = {
                    "id": res['data']['request_id'],
                    "team_id": data['team_id'],
                    "integration_id": data['integration_id'],
                    "webhook_request_id": data['webhook_request_id'],
                }
                await self.db.execute(query, values)

                return {"ok": True, "message": "MNP request sent"}

    #
    # /events endpoint
    #
    async def handle_event(self, data):
        self.logger.debug("Handling event: %s", data)

        event_type = data['event_type']
        # required_fields = data['required_fields']

        # self.bearer_token = self.get_value_by_id(required_fields, "api_key")
        # if self.bearer_token is None:
        # return { "ok": False, "message": "missing required field: api_key" }

        if event_type == "integration_test":
            return await self.test_api_key()

        if event_type == "integration_activated":
            return await self.store_team_token(data)

        if event_type == "integration_deactivated":
            return await self.integration_deactivate(data)

        if event_type in ["contact_created", "sms_created"]:
            return await self.mnp_request(data)

        # todo: handle other event types

        return {"ok": False, "message": "unknown event type"}

    async def store_mnp_response(self, data):
        self.logger.debug("Storing mnp response: %s", data)

        request_id = data['request_id']
        query = "select * from ping_prophet_requests where id = :request_id and status = 'pending';"
        values = {
            "request_id": request_id,
        }
        pp_request = await self.db.fetch_one(query, values)

        if pp_request is None:
            self.logger.debug("Request not found: %s", data)
            return {"ok": False, "message": "request not found"}

        items = data['data']

        query = """
        insert into ping_prophet_mnp_results (id, pp_request_id, foreign_id, phone_normalized, verified, mcc, mnc, brand_name, country_code, reason_code, raw_response) 
        values (:id, :pp_request_id, :foreign_id, :phone_normalized, :verified, :mcc, :mnc, :brand_name, :country_code, :reason_code, :raw_response);
        """
        values = []
        for item in items:
            values.append({
                "id": str(uuid.uuid4()),
                "pp_request_id": pp_request['id'],
                "foreign_id": item['foreign_id'],
                "phone_normalized": item['number'],
                "verified": item['verified'],
                "mcc": item['mcc'],
                "mnc": item['mnc'],
                "brand_name": item['brand_name'],
                "country_code": item['country_code'],
                "reason_code": item['reason_code'],
                "raw_response": json.dumps(item['raw_response']),
            })

            if len(values) >= 100:
                await self.db.execute_many(query, values)
                values = []

        # insert remaining values
        if len(values) > 0:
            await self.db.execute_many(query, values)

        query = "update ping_prophet_requests set status = 'patching' where id = :request_id;"
        values = {
            "request_id": request_id,
        }
        await self.db.execute(query, values)

        await self.patch_contacts(pp_request)

        return {"ok": True, "message": "mnp response stored"}

    async def patch_contacts(self, pp_request):
        self.logger.debug("Patching contacts, request ID: %s", pp_request['id'])

        team_access_token = await self.get_team_token(pp_request)

        if team_access_token is None:
            self.logger.debug("No api key: %s", pp_request['id'])

            query = "update ping_prophet_requests set status = 'failed' where id = :id;"
            values = {
                "id": pp_request['id'],
            }
            await self.db.execute(query, values)
            return {"ok": False, "message": "no api key"}

        while True:
            query = """
                select * from ping_prophet_mnp_results 
                where pp_request_id = :request_id and status = 'pending'
                limit 100;
            """
            values = {
                "request_id": pp_request['id'],
            }
            results = await self.db.fetch_all(query, values)

            if len(results) == 0:
                self.logger.debug("No more contacts to patch: %s", pp_request['id'])
                query = "update ping_prophet_requests set status = 'completed' where id = :request_id;"
                values = {
                    "request_id": pp_request['id'],
                }
                await self.db.execute(query, values)
                break

            ids = [result['id'] for result in results]
            contacts = []

            for result in results:
                ids.append(result['id'])

                contact_data = {
                    "contact_id": str(result['foreign_id']),
                    "phone_normalized": str(result['phone_normalized']),
                    "phone_is_good": result['verified'],
                    "phone_is_good_reason": 1 if result['verified'] == 1 else 2,
                    "mcc": result['mcc'],
                    "mnc": result['mnc'],
                    "brand_name": result['brand_name'],
                    "country": result['country_code'],
                    "reason_code": result['reason_code'],
                    "raw_response": result['raw_response'],
                }
                contacts.append(contact_data)

            req = {
                "team_id": str(pp_request['team_id']),
                "contacts": contacts,
            }
            headers = {
                "Authorization": f"Bearer {team_access_token}",
                "Content-Type": "application/json",
                "Accept": "application/json",
            }
            response = requests.post(f"{os.getenv('COMM_URL')}/api/v1/contacts/upsert", json=req, headers=headers)
            self.logger.info("Contact patch response (team id: %s), Response: %s", pp_request['team_id'],
                             response.json())
            placeholders = ', '.join(':id' + str(i) for i in range(len(ids)))

            if response.status_code != 200:
                query = "update ping_prophet_mnp_results set status = 'failed' where id in ({placeholders});"
                values = {
                    f"id{i}": id for i, id in enumerate(ids)
                }
                await self.db.execute(query, values)
                continue

            query = f"update ping_prophet_mnp_results set status = 'completed' where id in ({placeholders});"
            values = {
                f"id{i}": id for i, id in enumerate(ids)
            }
            await self.db.execute(query, values)

            await asyncio.sleep(1)

        query = "update ping_prophet_requests set status = 'completed' where id = :request_id;"
        values = {
            "request_id": pp_request['id'],
        }
        await self.db.execute(query, values)
        self.logger.debug("Contacts patched: %s", pp_request['id'])

        return {"ok": True, "message": "contacts patched"}

    async def billing_charge(self, data):
        self.logger.debug("Billing charge: %s", data)

        request_id = data['request_id']
        query = "select * from ping_prophet_requests where id = :request_id;"
        values = {
            "request_id": request_id,
        }
        pp_request = await self.db.fetch_one(query, values)

        if pp_request is None:
            self.logger.debug("Request not found: %s", data)
            return {"ok": False, "message": "request not found"}

        team_access_token = await self.get_team_token(pp_request)

        if team_access_token is None:
            self.logger.debug("No api key: %s", pp_request['id'])

            query = "update ping_prophet_requests set status = 'failed' where id = :request_id;"
            values = {
                "request_id": pp_request['id'],
            }
            await self.db.execute(query, values)
            return {"ok": False, "message": "no api key"}

            # 'event_type' => EventTypeEnum::billing_charge->value,
            # 'request_id' => $apiRequest->id,
            # 'cost' => $cost,
            # 'reason' => "MNP lookup for {$totalSuccess} numbers",

        metadata = {
            "reason": data['reason'],
            "integration_id": str(pp_request['integration_id']),
            "webhook_request_id": str(pp_request['webhook_request_id']),
        }
        req = {
            "cost": data['cost'],
            "meta": metadata,
        }
        headers = {
            "Authorization": f"Bearer {team_access_token}",
            "Content-Type": "application/json",
            "Accept": "application/json",
        }
        response = requests.post(f"{os.getenv('COMM_URL')}/api/v1/billing/charge", json=req, headers=headers)
        self.logger.info("Billing charge response (team id: %s), Response: %s", pp_request['team_id'], response.json())

        return {"ok": True, "message": "billing charged"}

    #
    # /callback endpoint
    #
    async def callback(self, data):
        event_type = data['event_type']

        if event_type == "mnp_response":
            self.background_tasks.add_task(self.store_mnp_response, data)
            return {"ok": True, "message": "callback received"}

        if event_type == "billing_charge":
            return await self.billing_charge(data)

        return {"ok": False, "message": "unknown event type"}
