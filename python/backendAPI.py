from fastapi import FastAPI
from main import *
import logging

app = FastAPI()

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

@app.get("/trigger-downloads")
def trigger_downloads():
    try:
        resp = download()
    except Exception as e:
        logger.error(f"Error during download: {str(e)}", exc_info=True)
        resp = 'error'
    return resp