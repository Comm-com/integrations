from databases import Database
import sqlalchemy
import os


class DatabaseWrapper:
    def __init__(self):
        db_host = os.getenv('DB_HOST', 'localhost')
        db_name = os.getenv('DB_NAME', 'middleware')
        db_user = os.getenv('DB_USER', 'middleware')
        db_password = os.getenv('DB_PASSWORD', 'middleware')
        db_port = os.getenv('DB_PORT', '5432')
        self.database = Database(f"postgresql+asyncpg://{db_user}:{db_password}@{db_host}:{db_port}/{db_name}")
        self.metadata = sqlalchemy.MetaData()
        self.dialect = sqlalchemy.dialects.postgresql.dialect()

    async def connect(self):
        await self.database.connect()

    async def disconnect(self):
        await self.database.disconnect()

    async def execute(self, query, values):
        return await self.database.execute(query, values)

    async def execute_many(self, query, values):
        return await self.database.execute_many(query, values)

    async def fetch_val(self, query, values):
        return await self.database.fetch_val(query, values)

    async def fetch_one(self, query, values):
        return await self.database.fetch_one(query, values)

    async def fetch_all(self, query, values):
        return await self.database.fetch_all(query, values)

    async def apply_migrations(self):
        tables = [
            self.team_access_tokens(),
            self.ping_prophet_requests(),
            self.ping_prophet_mnp_results(),
        ]
        for table in tables:
            schema = sqlalchemy.schema.CreateTable(table, if_not_exists=True)
            query = str(schema.compile(dialect=self.dialect))
            await self.database.execute(query)

    def team_access_tokens(self):
        if "team_access_tokens" in self.metadata.tables:
            return self.metadata.tables["team_access_tokens"]

        return sqlalchemy.Table(
            "team_access_tokens",
            self.metadata,
            sqlalchemy.Column("id", sqlalchemy.UUID, primary_key=True),
            sqlalchemy.Column("team_id", sqlalchemy.UUID),
            sqlalchemy.Column("integration_id", sqlalchemy.UUID),
            sqlalchemy.Column("integration_hash", sqlalchemy.String),
            sqlalchemy.Column("access_token", sqlalchemy.String),
            sqlalchemy.Column("created_at", sqlalchemy.DateTime, server_default=sqlalchemy.sql.func.now()),
            sqlalchemy.Column("deleted_at", sqlalchemy.DateTime)
        )

    def ping_prophet_requests(self):
        if "ping_prophet_requests" in self.metadata.tables:
            return self.metadata.tables["ping_prophet_requests"]

        return sqlalchemy.Table(
            "ping_prophet_requests",
            self.metadata,
            sqlalchemy.Column("id", sqlalchemy.UUID, primary_key=True),
            sqlalchemy.Column("team_id", sqlalchemy.UUID),
            sqlalchemy.Column("integration_id", sqlalchemy.UUID),
            # pending, patching, completed, failed
            sqlalchemy.Column("status", sqlalchemy.String, server_default="pending"),
            sqlalchemy.Column("webhook_request_id", sqlalchemy.UUID, nullable=True),
            sqlalchemy.Column("created_at", sqlalchemy.DateTime, server_default=sqlalchemy.sql.func.now()),
        )

    def ping_prophet_mnp_results(self):
        if "ping_prophet_mnp_results" in self.metadata.tables:
            return self.metadata.tables["ping_prophet_mnp_results"]

            # $table->boolean('verified')->nullable();
            # $table->string('brand_name')->nullable();
            # $table->string('mcc', 3)->nullable();
            # $table->string('mnc', 3)->nullable();
            # $table->string('country_code', 2)->nullable();
            # $table->string('reason_code')->nullable();
        return sqlalchemy.Table(
            "ping_prophet_mnp_results",
            self.metadata,
            sqlalchemy.Column("id", sqlalchemy.UUID, primary_key=True),
            sqlalchemy.Column("pp_request_id", sqlalchemy.UUID),
            sqlalchemy.Column("foreign_id", sqlalchemy.UUID),
            sqlalchemy.Column("phone_normalized", sqlalchemy.BigInteger),
            sqlalchemy.Column("network_id", sqlalchemy.BigInteger, nullable=True),
            sqlalchemy.Column("verified", sqlalchemy.SmallInteger),
            sqlalchemy.Column("mcc", sqlalchemy.String(3)),
            sqlalchemy.Column("mnc", sqlalchemy.String(3)),
            sqlalchemy.Column("country_code", sqlalchemy.String(2), nullable=True),
            sqlalchemy.Column("brand_name", sqlalchemy.String, nullable=True),
            sqlalchemy.Column("reason_code", sqlalchemy.String, nullable=True),
            sqlalchemy.Column("raw_response", sqlalchemy.JSON),
            sqlalchemy.Column("status", sqlalchemy.String, server_default="pending"),  # pending, completed, failed
            sqlalchemy.Column("created_at", sqlalchemy.DateTime, server_default=sqlalchemy.sql.func.now()),
            sqlalchemy.Column("updated_at", sqlalchemy.DateTime, server_default=sqlalchemy.sql.func.now(),
                              onupdate=sqlalchemy.sql.func.now()),
        )
