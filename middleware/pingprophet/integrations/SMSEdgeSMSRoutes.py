import aiohttp
import os
import random
import string
from integrations.BaseIntegration import BaseIntegration


class SMSEdgeSMSRoutes(BaseIntegration):
    @staticmethod
    def generate_random_string(length):
        characters = string.ascii_letters + string.digits
        random_string = ''.join(random.choice(characters) for i in range(length))
        return random_string

    def __init__(self, db, logger, background_tasks):
        super().__init__(db, logger, background_tasks)

        self.url = os.getenv('COMM_URL')
        self.bearer_token = None
        self.team_token = None
        self.hub_token = os.getenv('HUB_ACCOUNT_TOKEN')

    async def integration_activated(self, data):
        self.team_token = await self.get_team_token(data)

        if self.team_token is not None:
            return {"ok": True, "message": "Integration already activated"}

        await self.store_team_token(data)
        self.team_token = data['data']['access_token']

        # hub account
        plan_id = await self.get_default_routing_plan()
        hub_company_id = await self.create_company(
            self.hub_token,
            {
                "name": "Team ID: " + data['team_id'],
                "overdraft_enabled": True,
                "overdraft_limit": 0,
            }
        )

        endpoint_username = f"se_{self.generate_random_string(6)}"
        endpoint_password = self.generate_random_string(12)

        # first create client sms route to get sms route id
        company_id = await self.create_company(
            self.team_token,
            {
                "name": "SMSEdge SMS Routes Company",
                "overdraft_enabled": False,
                "overdraft_limit": 0,
            }
        )
        smpp = await self.create_smpp_connection({
            "url": os.getenv('HUB_SMPP_URL'),
            "port": 2775,
            "username": endpoint_username,
            "password": endpoint_password,
        })

        clicks_webhook_url = list(filter(lambda x: x['name'] == 'clicks_webhook_url', data['required_fields']))
        sms_route = await self.create_sms_route({
            "name": "SMSEdge SMS Routes",
            "description": "Auto-created from SMSEdge integration",
            "company_id": company_id,
            "connection_id": smpp['id'],
            "clicks_webhook_url": clicks_webhook_url[0]['value'] if clicks_webhook_url is not None else None,
        })

        await self.create_route_rate_settings(sms_route['id'], {
            "country": 1,
            "mcc": 3,
            "mnc": 4,
            "rate": 5,
        })

        # create hub endpoint
        pricing_email = os.getenv('HUB_PRICING_EMAIL').split('@')
        route_email = pricing_email[0] + "+" + sms_route['id'] + "@" + pricing_email[1]
        endpoint = await self.create_endpoint(
            self.hub_token,
            {
                "team_id": data['team_id'],
                "name": "Team ID: " + data['team_id'],
                "username": endpoint_username,
                "password": endpoint_password,
                "client_company_id": hub_company_id,
                "sms_routing_plan_id": plan_id,
                # "default_route_id": ?
                "pricing_update_email": route_email,
            }
        )

        # trigger price update
        res_message = await self.endpoint_send_pricing_update(endpoint['id'])

        return {
            "ok": True,
            "data": {
                "company_id": company_id,
                "sms_route_id": sms_route['id'],
                "pricing_update": res_message,
            }
        }

    async def create_company(self, token, payload):
        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {token}",
            }
            url = f"{self.url}/api/v1/audience/companies"
            self.logger.debug("Creating company in Comm.com: %s (%s)", url, payload)
            async with session.post(url, headers=headers, json=payload) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 201:
                    raise ValueError("Failed to create company in Comm.com")

                return res['id']

    async def get_default_routing_plan(self):
        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {self.hub_token}",
            }
            params = {
                "search": "default",
            }
            url = f"{self.url}/api/v1/sms/routing/plans"
            self.logger.debug("Getting default routing plan from Comm.com: %s (%s)", url, params)
            async with session.get(url, headers=headers, params=params) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 200:
                    raise ValueError("Failed to get routing plans from Comm.com")

                default_plan = list(filter(lambda x: x['is_default'], res['data']))

                if not default_plan:
                    raise ValueError("Default routing plan not found")

                return default_plan[0]['id']

    async def get_endpoint(self, token, payload):
        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {token}",
            }
            url = f"{self.url}/api/v1/hub/endpoints"
            self.logger.debug("Getting endpoint from Comm.com: %s (%s)", url, payload)
            async with session.get(url, headers=headers, params=payload) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 200:
                    raise ValueError("Failed to get endpoint from Comm.com")

                result = list(filter(lambda x: x['name'] == payload['search'], res['data']))
                if not result:
                    return None

                return result[0]

    async def create_endpoint(self, token, payload):
        endpoint = await self.get_endpoint(token, {"search": payload['name']})
        if endpoint is not None:
            return endpoint

        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {token}",
            }
            url = f"{self.url}/api/v1/hub/endpoints"
            self.logger.debug("Creating endpoint in Comm.com: %s (%s)", url, payload)
            async with session.post(url, headers=headers, json=payload) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 201:
                    raise ValueError("Failed to create endpoint in Comm.com")

                return res

    async def create_smpp_connection(self, data):
        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {self.hub_token}",
            }
            url = f"{self.url}/api/v1/sms/routing/routes/smpp-connections"
            self.logger.debug("Creating SMPP connection in Comm.com: %s (%s)", url, data)
            async with session.post(url, headers=headers, json=data) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 201:
                    raise ValueError("Failed to create SMPP connection in Comm.com")

                return res['data']

    async def create_sms_route(self, payload):
        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {self.team_token}",
            }
            url = f"{self.url}/api/v1/sms/routing/routes"
            self.logger.debug("Creating SMS route in Comm.com: %s (%s)", url, payload)
            async with session.post(url, headers=headers, json=payload) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 201:
                    raise ValueError("Failed to create SMS route in Comm.com")

                return res['data']

    async def create_route_rate_settings(self, sms_route_id, payload):
        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {self.team_token}",
            }
            url = f"{self.url}/api/v1/sms/routing/routes/{sms_route_id}/rate-settings"
            self.logger.debug("Creating route rate settings in Comm.com: %s (%s)", url, payload)
            async with session.post(url, headers=headers, json=payload) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 200:
                    raise ValueError("Failed to create route rate settings in Comm.com")

                return res['data']

    async def endpoint_send_pricing_update(self, endpoint_id):
        timeout = aiohttp.ClientTimeout(total=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            headers = {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": f"Bearer {self.hub_token}",
            }
            url = f"{self.url}/api/v1/hub/endpoints/{endpoint_id}/send-rates"
            self.logger.debug("Sending pricing update in Comm.com: %s", url)
            async with session.post(url, headers=headers) as resp:
                res = await resp.json()
                self.logger.debug("Response from Comm.com: (%s) %s", resp.status, res)

                if resp.status != 200:
                    raise ValueError("Failed to send pricing update in Comm.com")

                return res['message']

    async def integration_deactivate(self, data):
        # todo: delete related entities in Comm.com?

        await self.destroy_team_token(data)

        return {"ok": True, "message": "Integration deactivated"}

    #
    # /events endpoint
    #
    async def handle_event(self, data):
        self.logger.debug("Handling event: %s", data)

        event_type = data['event_type']

        if event_type == "integration_activated":
            return await self.integration_activated(data)

        if event_type == "integration_deactivated":
            return await self.integration_deactivate(data)

        # todo: handle other event types

        return {"ok": False, "message": "unknown event type"}

    #
    # /callback endpoint
    #
    async def callback(self, data):
        #
        return {"ok": True}
