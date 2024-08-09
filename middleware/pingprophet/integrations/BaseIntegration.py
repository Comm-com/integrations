import uuid

class BaseIntegration:
    def __init__(self, db, logger, background_tasks):
        self.db = db
        self.logger = logger
        self.background_tasks = background_tasks

    @staticmethod
    def get_value_by_id(required_fields, target_id):
        for field in required_fields:
            if field['id'] == target_id:
                return field['value']
        return None

    async def handle_event(self, data):
        raise NotImplementedError("handle_event not implemented")

    async def callback(self, data):
        raise NotImplementedError("callback not implemented")

    async def store_team_token(self, data):
        query = """
        insert into team_access_tokens (id, team_id, integration_id, access_token) 
        values (:id, :team_id, :integration_id, :access_token);
        """
        values = {
            "id": str(uuid.uuid4()),
            "team_id": data['team_id'],
            "integration_id": data['integration_id'],
            "access_token": data['data']['access_token'],
        }
        await self.db.execute(query, values)

        return {"ok": True, "message": "Token stored"}

    async def destroy_team_token(self, data):
        query = "update team_access_tokens set deleted_at = now() where team_id = :team_id and integration_id = :integration_id;"
        values = {
            "team_id": data['team_id'],
            "integration_id": data['integration_id'],
        }
        await self.db.execute(query, values)

    async def get_team_token(self, data):
        query = "select access_token from team_access_tokens where team_id = :team_id and integration_id = :integration_id and deleted_at is null;"
        values = {
            "team_id": data['team_id'],
            "integration_id": data['integration_id'],
        }
        return await self.db.fetch_val(query, values)
