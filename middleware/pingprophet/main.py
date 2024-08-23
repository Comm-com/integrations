from fastapi import Request, BackgroundTasks, FastAPI
from contextlib import asynccontextmanager
from importlib import import_module
from dotenv import load_dotenv
from logging.handlers import RotatingFileHandler
from database import DatabaseWrapper
import re
import logging
import sys
import traceback
import os

# from integrations.HLRPingProphet import HLRPingProphet # for testing without dynamic imports

load_dotenv()

app = FastAPI()
main_app_lifespan = app.router.lifespan_context
db = DatabaseWrapper()
logger = logging.getLogger("app")
logger.setLevel(logging.DEBUG)

fileHandler = RotatingFileHandler("logs/app.log", maxBytes=1000000, backupCount=5)
fileHandler.setFormatter(logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s"))
logger.addHandler(fileHandler)

stdoutHandler = logging.StreamHandler(sys.stdout)
stdoutHandler.setFormatter(logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s"))
logger.addHandler(stdoutHandler)

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
        integration_filename = re.sub(r'[^a-zA-Z0-9]', '', data['class_name'])

        module = import_module(f'integrations.{integration_filename}')
        class_obj = getattr(module, integration_filename)
        class_instance = class_obj(db, logger, background_tasks)
        # classInstance = HLRPingProphet()
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except ImportError:
        return {"ok": False, "message": "Integration module not found"}
    except Exception as e:
        logger.error(traceback.format_exc())
        return {"ok": False, "message": str(e)}

    try:
        return await class_instance.handle_event(data)
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except Exception as e:
        logger.error(traceback.format_exc())
        return {"ok": False, "message": str(e)}


@app.post("/callback/{class_name}")
async def callback_new(request: Request, class_name: str, background_tasks: BackgroundTasks):
    data = await request.json()

    try:
        integration_filename = re.sub(r'[^a-zA-Z0-9]', '', class_name)

        module = import_module(f'integrations.{integration_filename}')
        class_obj = getattr(module, integration_filename)
        class_instance = class_obj(db, logger, background_tasks)
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except ImportError:
        return {"ok": False, "message": "Integration module not found"}
    except Exception as e:
        logger.error(traceback.format_exc())
        return {"ok": False, "message": str(e)}

    try:
        return await class_instance.callback(data)
    except KeyError as e:
        return {"ok": False, "message": "Missing: " + str(e)}
    except Exception as e:
        logger.error(traceback.format_exc())
        return {"ok": False, "message": str(e)}


@app.get("/health")
async def health_check():
    return {"ok": True, "message": "Service is healthy"}


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host="0.0.0.0", port=int(os.getenv("APP_PORT", 8000)))
