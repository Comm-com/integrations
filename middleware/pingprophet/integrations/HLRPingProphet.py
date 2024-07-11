import aiohttp
import asyncio
import requests
import json
import os
import uuid
from integrations.BaseIntegration import BaseIntegration

class HLRPingProphet(BaseIntegration):
    def __init__(self, db, logger, background_tasks):
        super().__init__(db, logger, background_tasks)

        self.url = os.getenv('PING_PROPHET_URL', 'https://pingprophet.com')
        self.bearer_token = None

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
                    return { "ok": False, "message": res['message'] or "Invalid token" }
                
                return { "ok": True, "message": "Integration status updated" }
            
    async def integration_deactivate(self, data): 
        timeout = aiohttp.ClientTimeout(total=5)

        await self.destroy_team_token(data)

        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": "Bearer " + self.bearer_token,
            }
            async with session.post(f"{self.url}/api/v1/integrations/deactivate", headers=headers) as resp:
                res = await resp.json()
                self.logger.debug("Integration Deactivate response: %s", res)

                if resp.status == 401:
                    return { "ok": True, "message": "Integration already deactivated" }

                if resp.status != 200:
                    return { "ok": False, "message": res['message'] or "Invalid token" }
                
                return { "ok": True, "message": "Integration status updated" }
            
    async def mnp_request(self, data):
        timeout = aiohttp.ClientTimeout(total=60) # 1 minute
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": "Bearer " + self.bearer_token,
            }
            contacts = []
            for contact in data['data']:
                contactItem = {
                    "number": contact['phone_normalized'],
                    "foreign_id": contact['contact_id'] if 'contact_id' in contact else str(uuid.uuid4()),
                }
                contacts.append(contactItem)

            queryData = {
                "data": contacts,
                "callback_url": f"{os.getenv('CALLBACK_URL')}/HLRPingProphet"
            }
            async with session.post(f"{self.url}/api/v1/mnp", headers=headers, json=queryData) as resp:
                res = await resp.json()
                self.logger.debug("MNP Request response: %s", res)

                if resp.status != 200:
                    return { 
                        "ok": False, 
                        "message": res['message'] or "Invalid token",
                        "status_code": resp.status,
                        "notification": resp.status == 402,
                    }
                
                query = "insert into ping_prophet_requests (id, team_id, integration_id, api_request_id) values (gen_random_uuid(), :team_id, :integration_id, :api_request_id);"
                values = {
                    "team_id": data['team_id'],
                    "integration_id": data['integration_id'],
                    "api_request_id": res['data']['request_id'],
                }
                await self.db.execute(query, values)
                
                return { "ok": True, "message": "Integration status updated" }

    async def handle_event(self, data):
        self.logger.debug("Handling event: %s", data)

        event_type = data['event_type']
        required_fields = data['required_fields']

        self.bearer_token = self.get_value_by_id(required_fields, "api_key")
        if self.bearer_token is None:
            return { "ok": False, "message": "missing required field: api_key" }

        if event_type == "integration/test":
            return await self.test_api_key()
        
        if event_type == "integration/activated":
            return await self.store_team_token(data)
        
        if event_type == "integration/deactivated":
            return await self.integration_deactivate(data)
            
        if event_type in ["contact/created", "sms/created"]:
            return await self.mnp_request(data)

        # todo: handle other event types

        return { "ok": False, "message": "unknown event type" }
    
    async def store_mnp_response(self, data):
        self.logger.debug("Storing mnp response: %s", data)

        request_id = data['request_id']
        query = "select * from ping_prophet_requests where api_request_id = :request_id and status = 'pending';"
        values = {
            "request_id": request_id,
        }
        row = await self.db.fetch_one(query, values)

        if row is None:
            self.logger.debug("Request not found: %s", data)
            return { "ok": False, "message": "request not found" }
        
        items = data['data']
        query = """
        insert into lookup_results (id, api_request_id, foreign_id, phone_normalized, network_id, verified, raw_response) 
        values (gen_random_uuid(), :api_request_id, :foreign_id, :phone_normalized, :network_id, :verified, :raw_response);
        """
        values = []
        for item in items:
            values.append({
                "api_request_id": row['api_request_id'],
                "foreign_id": item['foreign_id'],
                "phone_normalized": item['number'],
                "network_id": item['network_id'],
                "verified": item['verified'],
                "raw_response": json.dumps(item['raw_response']),
            })

            if len(values) >= 100:
                await self.db.execute_many(query, values)
                values = []

        # insert remaining values
        if len(values) > 0:
            await self.db.execute_many(query, values)

        query = "update ping_prophet_requests set status = 'patching' where api_request_id = :request_id;"
        values = {
            "request_id": request_id,
        }
        await self.db.execute(query, values)

        await self.patch_contacts(row)

        return { "ok": True, "message": "response stored" }

    async def patch_contacts(self, request):
        self.logger.debug("Patching contacts, request ID: %s", request['api_request_id'])

        team_access_token = await self.get_team_token(request)

        if team_access_token is None:
            self.logger.debug("No api key: %s", request['api_request_id'])

            query = "update ping_prophet_requests set status = 'no_api_key' where api_request_id = :request_id;"
            values = {
                "request_id": request['api_request_id'],
            }
            await self.db.execute(query, values)
            return { "ok": False, "message": "no api key" }
        
        while True:
            query = """
                select * from lookup_results 
                where api_request_id = :request_id and status = 'pending'
                limit 100;
            """
            values = {
                "request_id": request['api_request_id'],
            }
            results = await self.db.fetch_all(query, values)

            if len(results) == 0:
                self.logger.debug("No more contacts to patch: %s", request['api_request_id'])
                query = "update ping_prophet_requests set status = 'completed' where api_request_id = :request_id;"
                break

            ids = [result['id'] for result in results]
            contacts = []

            for result in results:
                ids.append(result['id'])

                contactData = {
                    "contact_id": str(result['foreign_id']),
                    "phone_normalized": str(result['phone_normalized']),
                    "network_id": result['network_id'],
                    "phone_is_good": result['verified'],
                    "phone_is_good_reason": 1 if result['verified'] == 1 else 2,
                    "raw_response": result['raw_response'],
                }
                contacts.append(contactData)

            req = {
                "team_id": str(request['team_id']),
                "contacts": contacts,
            }
            headers = {
                "Authorization": f"Bearer {team_access_token}",
                "Content-Type": "application/json",
                "Accept": "application/json",
            }
            response = requests.post(f"{os.getenv('COMM_URL')}/api/v1/contacts/upsert", json=req, headers=headers)
            self.logger.info("Contact patch response (team id: %s), Response: %s", request['team_id'], response.json())
            placeholders = ', '.join(':id' + str(i) for i in range(len(ids)))

            if response.status_code != 200:
                query = "update lookup_results set status = 'error' where id in ({placeholders});"
                values = {
                    f"id{i}": id for i, id in enumerate(ids)
                }
                await self.db.execute(query, values)
                continue

            query = f"update lookup_results set status = 'patched' where id in ({placeholders});"
            values = {
                f"id{i}": id for i, id in enumerate(ids)
            }
            await self.db.execute(query, values)

            await asyncio.sleep(1)

        query = "update ping_prophet_requests set status = 'completed' where api_request_id = :request_id;"
        values = {
            "request_id": request['api_request_id'],
        }
        await self.db.execute(query, values)
        self.logger.debug("Contacts patched: %s", request['api_request_id'])
        
        return { "ok": True, "message": "contacts patched" }
    
    async def callback(self, data):
        self.background_tasks.add_task(self.store_mnp_response, data)
        return { "ok": True, "message": "callback received" }
