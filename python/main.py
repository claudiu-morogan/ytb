# Download mp4 songs from youtube
from pytubefix import YouTube
from funcs import *
import logging
import os
import subprocess

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def download():
    try:
        dbConnection = dbConnect()

        cursor = dbConnection.cursor()
        cursor.execute("SELECT link, video_id FROM ytb_downloads WHERE downloaded=0")

        result = cursor.fetchall()
        if cursor.rowcount != 0:
            for row in result:
                (url, video_id) = (row[0], row[1])
                try:
                    yt = YouTube(url)

                    # Get metadata - try different property names for compatibility
                    artist = getattr(yt, 'author', None) or getattr(yt, 'channel_name', 'Unknown Artist')
                    title = getattr(yt, 'title', 'Unknown Title')

                    songName = f"{artist}_{title}"
                    logger.info(f"Processing: {songName}")

                    # Download audio stream
                    audio_stream = yt.streams.filter(only_audio=True).first()
                    if audio_stream:
                        audio_stream.download(downloadLocation()+'/mp4/')
                        logger.info(f"Downloaded audio for: {title}")
                    else:
                        logger.error(f"No audio stream found for: {url}")
                        continue

                    # Update database
                    setVideoToDownloaded(video_id, cursor, dbConnection)
                    updateSongDetails(artist, title, video_id)
                    logger.info(f"Successfully processed: {songName}")
                except Exception as e:
                    logger.error(f"Failed to download video {video_id} from {url}: {str(e)}", exc_info=True)
                    continue

        dbConnection.close()

        # Convert M4A/MP4 to MP3
        location = downloadLocation()+'/mp4/'
        newLocation = downloadLocation()+'/mp3/'

        # Ensure MP3 directory exists
        os.makedirs(newLocation, exist_ok=True)
        logger.info(f"Checking for audio files in: {location}")

        # Look for both .mp4 and .m4a files
        audio_files = [f for f in os.listdir(location) if f.endswith(('.mp4', '.m4a', '.webm'))]
        logger.info(f"Found {len(audio_files)} audio files to convert")

        for filename in audio_files:
            input_path = os.path.join(location, filename)
            # Replace any audio extension with .mp3
            mp3_filename = filename.rsplit('.', 1)[0] + '.mp3'
            mp3_path = os.path.join(newLocation, mp3_filename)

            logger.info(f"Converting: {filename}")
            try:
                result = subprocess.run([
                    'ffmpeg',
                    '-y',  # Overwrite output file if it exists
                    '-i', input_path,
                    '-vn',  # No video
                    '-ar', '44100',  # Audio sample rate
                    '-ac', '2',  # Audio channels (stereo)
                    '-b:a', '192k',  # Audio bitrate
                    mp3_path
                ], check=True, capture_output=True, text=True)

                logger.info(f"✓ Converted {filename} to MP3")

                # Delete the source file after successful conversion
                os.remove(input_path)
                logger.info(f"✓ Deleted source file: {filename}")

            except subprocess.CalledProcessError as e:
                logger.error(f"✗ Failed to convert {filename}")
                logger.error(f"  STDOUT: {e.stdout}")
                logger.error(f"  STDERR: {e.stderr}")
                continue
            except OSError as e:
                logger.error(f"✗ Failed to delete {filename}: {str(e)}")
                continue

        logger.info("Conversion process completed")
        return 'success'
    except Exception as e:
        logger.error(f"Download function failed: {str(e)}", exc_info=True)
        return 'error'
