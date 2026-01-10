from fastapi import FastAPI
from main import *
from funcs import deleteSong, cleanupEmptyPlaylists, moveSong
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

@app.get("/get-metadata")
def get_metadata(url: str):
    """
    Extract metadata (artist, title) from YouTube URL without downloading
    """
    try:
        from pytubefix import YouTube

        yt = YouTube(url)

        # Get metadata
        artist = getattr(yt, 'author', None) or getattr(yt, 'channel_name', 'Unknown Artist')
        title = getattr(yt, 'title', 'Unknown Title')

        logger.info(f"Successfully extracted metadata: {artist} - {title}")

        return {
            "status": "success",
            "artist": artist,
            "title": title
        }
    except Exception as e:
        logger.error(f"Error extracting metadata from {url}: {str(e)}", exc_info=True)
        return {
            "status": "error",
            "message": str(e)
        }

@app.put("/move-song/{video_id}")
def move_song(video_id: int, new_playlist: str):
    """
    Move a song from one playlist to another
    Updates database and moves MP3 file if it exists
    """
    try:
        success = moveSong(video_id, new_playlist)
        if success:
            # Clean up empty playlists after moving
            cleanupEmptyPlaylists()
            return {"status": "success", "message": f"Song {video_id} moved to {new_playlist}"}
        else:
            return {"status": "error", "message": f"Song {video_id} not found or move failed"}
    except Exception as e:
        logger.error(f"Error moving song {video_id}: {str(e)}", exc_info=True)
        return {"status": "error", "message": str(e)}

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