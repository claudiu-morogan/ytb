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
        cursor.execute("SELECT link, video_id, COALESCE(playlist, 'General') as playlist FROM ytb_downloads WHERE downloaded=0")

        result = cursor.fetchall()
        if cursor.rowcount != 0:
            for row in result:
                (url, video_id, playlist) = (row[0], row[1], row[2])
                try:
                    yt = YouTube(url)

                    # Get metadata - try different property names for compatibility
                    artist = getattr(yt, 'author', None) or getattr(yt, 'channel_name', 'Unknown Artist')
                    title = getattr(yt, 'title', 'Unknown Title')

                    songName = f"{artist}_{title}"
                    logger.info(f"Processing: {songName}")

                    # Create playlist-specific folder
                    mp4_folder = os.path.join(downloadLocation(), 'mp4', playlist)
                    os.makedirs(mp4_folder, exist_ok=True)

                    # Download audio stream
                    audio_stream = yt.streams.filter(only_audio=True).first()
                    if audio_stream:
                        audio_stream.download(mp4_folder)
                        logger.info(f"Downloaded audio for: {title} to playlist: {playlist}")
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

        # Convert M4A/MP4 to MP3 - Process all playlist folders
        base_mp4_location = os.path.join(downloadLocation(), 'mp4')
        base_mp3_location = os.path.join(downloadLocation(), 'mp3')

        logger.info(f"Scanning for audio files in: {base_mp4_location}")

        # Process all playlist folders
        total_converted = 0
        if os.path.exists(base_mp4_location):
            # Get all playlist folders
            for playlist_folder in os.listdir(base_mp4_location):
                playlist_path = os.path.join(base_mp4_location, playlist_folder)

                if os.path.isdir(playlist_path):
                    logger.info(f"Processing playlist: {playlist_folder}")

                    # Create corresponding MP3 playlist folder
                    mp3_playlist_path = os.path.join(base_mp3_location, playlist_folder)
                    os.makedirs(mp3_playlist_path, exist_ok=True)

                    # Find all audio files in this playlist folder
                    audio_files = [f for f in os.listdir(playlist_path) if f.endswith(('.mp4', '.m4a', '.webm'))]
                    logger.info(f"Found {len(audio_files)} audio files in {playlist_folder}")

                    for filename in audio_files:
                        input_path = os.path.join(playlist_path, filename)
                        mp3_filename = filename.rsplit('.', 1)[0] + '.mp3'
                        mp3_path = os.path.join(mp3_playlist_path, mp3_filename)

                        logger.info(f"Converting: {playlist_folder}/{filename}")
                        try:
                            subprocess.run([
                                'ffmpeg',
                                '-y',
                                '-i', input_path,
                                '-vn',
                                '-ar', '44100',
                                '-ac', '2',
                                '-b:a', '192k',
                                mp3_path
                            ], check=True, capture_output=True, text=True)

                            logger.info(f"✓ Converted to: {playlist_folder}/{mp3_filename}")

                            # Delete the source file
                            os.remove(input_path)
                            logger.info(f"✓ Deleted source: {filename}")
                            total_converted += 1

                        except subprocess.CalledProcessError as e:
                            logger.error(f"✗ Failed to convert {filename}")
                            logger.error(f"  STDERR: {e.stderr}")
                            continue
                        except OSError as e:
                            logger.error(f"✗ Failed to delete {filename}: {str(e)}")
                            continue

        logger.info(f"Conversion completed: {total_converted} files converted")
        return 'success'
    except Exception as e:
        logger.error(f"Download function failed: {str(e)}", exc_info=True)
        return 'error'
