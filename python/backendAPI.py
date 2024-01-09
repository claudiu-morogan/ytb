from fastapi import FastAPI
from main import *
app = FastAPI()

@app.get("/trigger-downloads")
def trigger_downloads():
    try:
        resp = download()
    except:
        resp = 'eroare'
    return resp