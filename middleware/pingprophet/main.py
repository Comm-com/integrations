from fastapi import Request, BackgroundTasks, FastAPI
from contextlib import asynccontextmanager
from importlib import import_module
from dotenv import load_dotenv
from logging.handlers import RotatingFileHandler
from database import DatabaseWrapper
import re
import logging
# from integrations.HLRPingProphet import HLRPingProphet # for testing without dynamic imports

load_dotenv()

app = FastAPI()
main_app_lifespan = app.router.lifespan_context
db = DatabaseWrapper()
logger = logging.getLogger("app")
logger.setLevel(logging.DEBUG)
handler = RotatingFileHandler("logs/app.log", maxBytes=1000000, backupCount=5)
handler.setFormatter(logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s"))
logger.addHandler(handler)
logger.debug("App started")

@asynccontextmanager
async def lifespan_wrapper(app):
    # startup
    await db.connect()
    await db.apply_migrations()
    
    async with main_app_lifespan(app) as lifespan_state:
        yield lifespan_state
    
    # shutdown
    await db.disconnect()

app.router.lifespan_context = lifespan_wrapper

@app.post("/events")
async def events_new(request: Request, background_tasks: BackgroundTasks):
    data = await request.json()

    try:
        integration_filename = re.sub(r'[^a-zA-Z0-9]', '', data['integration_name'])

        module = import_module(f'integrations.{integration_filename}')
        class_obj = getattr(module, integration_filename)
        class_instance = class_obj(db, logger, background_tasks)
        # classInstance = HLRPingProphet()
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except ImportError:
        return {"ok": False, "message": "Integration module not found"}
    except Exception as e:
        return {"ok": False, "message": str(e)}

    try:
        return await class_instance.handle_event(data)
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except Exception as e:
        return {"ok": False, "message": str(e)}
    

@app.post("/callback/{integration_name}")
async def callback_new(request: Request, integration_name: str, background_tasks: BackgroundTasks):
    data = await request.json()

    try:
        integration_filename = re.sub(r'[^a-zA-Z0-9]', '', integration_name)

        module = import_module(f'integrations.{integration_filename}')
        class_obj = getattr(module, integration_filename)
        class_instance = class_obj(db, logger, background_tasks)
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except ImportError:
        return {"ok": False, "message": "Integration module not found"}
    except Exception as e:
        return {"ok": False, "message": str(e)}

    try:
        return await class_instance.callback(data)
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except Exception as e:
        return {"ok": False, "message": str(e)}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
