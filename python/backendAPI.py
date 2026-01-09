from fastapi import FastAPI
from main import *
from funcs import deleteSong, cleanupEmptyPlaylists
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

@app.delete("/delete-song/{video_id}")
def delete_song(video_id: int):
    """
    Delete a song by video_id
    Removes from database and deletes MP3 file
    """
    try:
        success = deleteSong(video_id)
        if success:
            # Clean up empty playlists after deletion
            cleanupEmptyPlaylists()
            return {"status": "success", "message": f"Song {video_id} deleted successfully"}
        else:
            return {"status": "error", "message": f"Song {video_id} not found"}
    except Exception as e:
        logger.error(f"Error deleting song {video_id}: {str(e)}", exc_info=True)
        return {"status": "error", "message": str(e)}