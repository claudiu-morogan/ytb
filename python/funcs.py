import mysql.connector
from dotenv import load_dotenv
from pathlib import Path
import os

def resetDB(cursor):
    sql = "UPDATE ytb_downloads SET downloaded=0"
    cursor.execute(sql)

def dbConnect():
    dotenv_path = Path('.env')
    load_dotenv(dotenv_path=dotenv_path)

    db_host = os.getenv('db_host')
    db_user = os.getenv('db_user')
    db_password = os.getenv('db_password')
    db_name = os.getenv('db_name')

    projectDB = mysql.connector.connect(
        host = db_host,
        user = db_user,
        password = db_password,
        database = db_name
    )
    return projectDB

def downloadLocation():
    dotenv_path = Path('.env')
    load_dotenv(dotenv_path=dotenv_path)

    download_location = os.getenv('download_location')
    return download_location

def setVideoToDownloaded(video_id, cursor, dbConnection):
    sql = "UPDATE ytb_downloads SET downloaded=1 WHERE video_id = %s"
    val = (str(video_id),)
    cursor.execute(sql, val)
    dbConnection.commit()


def updateSongDetails(artist, title, video_id):
    dbConnection = dbConnect()

    sql = "SELECT count(*) registrations FROM ytb_song_details where video_id = %s"
    val = (str(video_id), )

    cursor = dbConnection.cursor()
    cursor.execute(sql, val)
    result = cursor.fetchone()
    count = result[0]

    if count == 1:
        sql = "UPDATE ytb_song_details SET artist = %s, song = %s where video_id = %s"
        val = (artist, title, str(video_id))
        cursor.execute(sql, val)
        dbConnection.commit()
    else:
        sql = "INSERT INTO ytb_song_details (artist, song, video_id) VALUES (%s, %s, %s)"
        val = (artist, title, str(video_id))
        cursor.execute(sql, val)
        dbConnection.commit()

    dbConnection.close()


def cleanupEmptyPlaylists():
    """
    Remove empty playlist folders from both mp3 and mp4 directories.
    Also cleans up playlists with no songs in the database.
    """
    import logging
    logger = logging.getLogger(__name__)

    logger.info("=== Starting empty playlist cleanup ===")

    base_mp3 = os.path.join(downloadLocation(), 'mp3')
    base_mp4 = os.path.join(downloadLocation(), 'mp4')

    removed_folders = []

    # Clean up mp3 folders
    for base_path in [base_mp3, base_mp4]:
        if os.path.exists(base_path):
            for playlist_name in os.listdir(base_path):
                # Skip the General playlist
                if playlist_name == 'General':
                    continue

                playlist_path = os.path.join(base_path, playlist_name)

                if os.path.isdir(playlist_path):
                    try:
                        contents = os.listdir(playlist_path)
                        # Filter for actual media files
                        media_files = [f for f in contents if f.endswith(('.mp3', '.mp4', '.m4a', '.webm'))]

                        if len(media_files) == 0:
                            logger.info(f"Found empty playlist folder: {playlist_path}")

                            # Remove any leftover files
                            for item in contents:
                                item_path = os.path.join(playlist_path, item)
                                try:
                                    if os.path.isfile(item_path):
                                        os.remove(item_path)
                                except Exception as e:
                                    logger.warning(f"Could not remove {item_path}: {str(e)}")

                            # Remove the empty directory
                            os.rmdir(playlist_path)
                            removed_folders.append(playlist_path)
                            logger.info(f"✓ Removed empty playlist folder: {playlist_path}")
                    except Exception as e:
                        logger.warning(f"Error processing folder {playlist_path}: {str(e)}")

    if removed_folders:
        logger.info(f"=== Cleanup completed: removed {len(removed_folders)} empty folders ===")
    else:
        logger.info("=== Cleanup completed: no empty folders found ===")

    return len(removed_folders)


def moveSong(video_id, new_playlist):
    """
    Move a song from one playlist to another
    Updates database and moves MP3 file if downloaded
    """
    import logging
    import shutil
    logger = logging.getLogger(__name__)

    logger.info(f"=== Starting move operation for video_id: {video_id} to playlist: {new_playlist} ===")

    dbConnection = dbConnect()
    cursor = dbConnection.cursor()

    try:
        # Get current song details
        sql = """
            SELECT ysd.artist, ysd.song, yd.playlist, yd.downloaded
            FROM ytb_downloads yd
            LEFT JOIN ytb_song_details ysd ON yd.video_id = ysd.video_id
            WHERE yd.video_id = %s
        """
        cursor.execute(sql, (str(video_id),))
        result = cursor.fetchone()

        if not result:
            logger.warning(f"Song with video_id {video_id} not found in database")
            return False

        artist, song, old_playlist, downloaded = result
        old_playlist = old_playlist if old_playlist else 'General'
        new_playlist = new_playlist if new_playlist else 'General'

        logger.info(f"Found song - Artist: {artist}, Song: {song}, Current Playlist: {old_playlist}, Downloaded: {downloaded}")

        # If it's the same playlist, no need to do anything
        if old_playlist == new_playlist:
            logger.info(f"Song is already in playlist {new_playlist}, no move needed")
            return True

        # Update database
        sql = "UPDATE ytb_downloads SET playlist = %s WHERE video_id = %s"
        cursor.execute(sql, (new_playlist, str(video_id)))
        dbConnection.commit()
        logger.info(f"Database updated: moved from {old_playlist} to {new_playlist}")

        # Move MP3 file if song has been downloaded and we have artist/song info
        if downloaded == 1 and artist and song:
            mp3_base = os.path.join(downloadLocation(), 'mp3')

            # Source and destination paths
            old_playlist_path = os.path.join(mp3_base, old_playlist)
            new_playlist_path = os.path.join(mp3_base, new_playlist)

            # Create new playlist folder if it doesn't exist
            os.makedirs(new_playlist_path, exist_ok=True)

            logger.info(f"Searching for MP3 file in: {old_playlist_path}")

            # Find the MP3 file
            file_moved = False
            if os.path.isdir(old_playlist_path):
                files_in_dir = os.listdir(old_playlist_path)
                mp3_files = [f for f in files_in_dir if f.endswith('.mp3')]
                logger.info(f"Found {len(mp3_files)} MP3 files in {old_playlist_path}")

                for filename in mp3_files:
                    filename_lower = filename.lower()
                    artist_lower = artist.lower() if artist else ""
                    song_lower = song.lower() if song else ""

                    # Normalize strings for matching
                    def normalize_for_matching(s):
                        return s.replace('/', '').replace('\\', '').replace(':', '').replace('|', '').replace('"', '').replace('*', '').replace('?', '').replace('<', '').replace('>', '')

                    filename_normalized = normalize_for_matching(filename_lower)
                    artist_normalized = normalize_for_matching(artist_lower)
                    song_normalized = normalize_for_matching(song_lower)

                    # Try to match the file
                    match_both = artist_normalized and song_normalized and artist_normalized in filename_normalized and song_normalized in filename_normalized
                    match_song_only = song_normalized and song_normalized in filename_normalized

                    if match_both or match_song_only:
                        old_file_path = os.path.join(old_playlist_path, filename)
                        new_file_path = os.path.join(new_playlist_path, filename)

                        logger.info(f"Moving file from {old_file_path} to {new_file_path}")

                        try:
                            shutil.move(old_file_path, new_file_path)
                            logger.info(f"✓ Successfully moved MP3 file to {new_playlist}")
                            file_moved = True
                            break
                        except Exception as e:
                            logger.error(f"✗ Failed to move MP3 file: {str(e)}", exc_info=True)
                            raise

                if not file_moved:
                    logger.warning(f"MP3 file not found for - Artist: '{artist}', Song: '{song}', Playlist: '{old_playlist}'")
                    logger.warning("Note: Database was updated, but physical file was not found to move")
            else:
                logger.warning(f"Old playlist folder does not exist: {old_playlist_path}")

        logger.info(f"=== Move operation completed for video_id: {video_id} ===")
        return True

    except Exception as e:
        logger.error(f"Error moving song {video_id}: {str(e)}", exc_info=True)
        dbConnection.rollback()
        return False
    finally:
        cursor.close()
        dbConnection.close()


def deleteSong(video_id):
    """
    Delete a song from database and remove MP3 file from filesystem
    """
    import logging
    logger = logging.getLogger(__name__)

    logger.info(f"=== Starting delete operation for video_id: {video_id} ===")

    dbConnection = dbConnect()
    cursor = dbConnection.cursor()

    try:
        # Get song details before deleting
        sql = """
            SELECT ysd.artist, ysd.song, yd.playlist
            FROM ytb_downloads yd
            LEFT JOIN ytb_song_details ysd ON yd.video_id = ysd.video_id
            WHERE yd.video_id = %s
        """
        cursor.execute(sql, (str(video_id),))
        result = cursor.fetchone()

        if not result:
            logger.warning(f"Song with video_id {video_id} not found in database")
            return False

        artist, song, playlist = result
        playlist = playlist if playlist else 'General'

        logger.info(f"Found song - Artist: {artist}, Song: {song}, Playlist: {playlist}")

        # Delete from database (CASCADE will delete from ytb_song_details)
        sql = "DELETE FROM ytb_downloads WHERE video_id = %s"
        cursor.execute(sql, (str(video_id),))
        dbConnection.commit()
        logger.info(f"Database record deleted for video_id: {video_id}")

        # Try to delete MP3 file if we have artist and song info
        file_deleted = False
        if artist and song:
            mp3_base = os.path.join(downloadLocation(), 'mp3')
            logger.info(f"Searching for MP3 files in base path: {mp3_base}")

            # Search in playlist folder and root folder
            search_paths = [
                os.path.join(mp3_base, playlist),
                mp3_base
            ]

            for search_path in search_paths:
                logger.info(f"Searching in: {search_path}")

                if not os.path.isdir(search_path):
                    logger.warning(f"Directory does not exist: {search_path}")
                    continue

                try:
                    files_in_dir = os.listdir(search_path)
                    mp3_files = [f for f in files_in_dir if f.endswith('.mp3')]
                    logger.info(f"Found {len(mp3_files)} MP3 files in {search_path}")

                    for filename in mp3_files:
                        logger.info(f"Checking file: {filename}")

                        filename_lower = filename.lower()
                        artist_lower = artist.lower() if artist else ""
                        song_lower = song.lower() if song else ""

                        # Normalize strings by removing characters that can't be in filenames
                        # This handles cases where database has "M/V" but filename has "MV"
                        def normalize_for_matching(s):
                            """Remove or replace characters that can't be in filenames"""
                            return s.replace('/', '').replace('\\', '').replace(':', '').replace('|', '').replace('"', '').replace('*', '').replace('?', '').replace('<', '').replace('>', '')

                        filename_normalized = normalize_for_matching(filename_lower)
                        artist_normalized = normalize_for_matching(artist_lower)
                        song_normalized = normalize_for_matching(song_lower)

                        # Try multiple matching strategies
                        # Strategy 1: File contains both artist and song (normalized)
                        match_both = artist_normalized and song_normalized and artist_normalized in filename_normalized and song_normalized in filename_normalized

                        # Strategy 2: File contains song name (for cases where artist is YouTube channel, not actual artist)
                        match_song_only = song_normalized and song_normalized in filename_normalized

                        if match_both or match_song_only:
                            file_path = os.path.join(search_path, filename)
                            match_type = "artist+song" if match_both else "song_only"
                            logger.info(f"Match found using strategy '{match_type}'! Attempting to delete: {file_path}")

                            try:
                                if os.path.exists(file_path):
                                    os.remove(file_path)
                                    logger.info(f"✓ Successfully deleted MP3 file: {file_path}")
                                    file_deleted = True
                                    break
                                else:
                                    logger.error(f"File exists check failed: {file_path}")
                            except Exception as e:
                                logger.error(f"✗ Failed to delete MP3 file {file_path}: {str(e)}", exc_info=True)
                                raise
                except Exception as e:
                    logger.error(f"Error listing directory {search_path}: {str(e)}", exc_info=True)

                if file_deleted:
                    break

            if not file_deleted:
                logger.warning(f"MP3 file not found for - Artist: '{artist}', Song: '{song}', Playlist: '{playlist}'")
                logger.warning("Note: Database record was deleted, but physical file was not found")
        else:
            logger.warning(f"No artist/song info available for video_id {video_id}, skipping file deletion")

        # Check if playlist folder is empty and delete it
        if playlist and playlist != 'General':
            playlist_folder = os.path.join(downloadLocation(), 'mp3', playlist)
            try:
                if os.path.isdir(playlist_folder):
                    # Check if folder is empty (no files, only potentially .DS_Store or similar)
                    contents = os.listdir(playlist_folder)
                    mp3_files = [f for f in contents if f.endswith('.mp3')]

                    if len(mp3_files) == 0:
                        logger.info(f"Playlist folder '{playlist}' is empty, removing it")
                        # Remove any remaining files (like .DS_Store)
                        for item in contents:
                            item_path = os.path.join(playlist_folder, item)
                            try:
                                if os.path.isfile(item_path):
                                    os.remove(item_path)
                            except Exception as e:
                                logger.warning(f"Could not remove {item_path}: {str(e)}")

                        # Remove the empty directory
                        os.rmdir(playlist_folder)
                        logger.info(f"✓ Removed empty playlist folder: {playlist_folder}")
            except Exception as e:
                logger.warning(f"Error checking/removing playlist folder {playlist_folder}: {str(e)}")

        logger.info(f"=== Delete operation completed for video_id: {video_id} (DB: deleted, File: {'deleted' if file_deleted else 'not found'}) ===")
        return True

    except Exception as e:
        logger.error(f"Error deleting song {video_id}: {str(e)}", exc_info=True)
        dbConnection.rollback()
        return False
    finally:
        cursor.close()
        dbConnection.close()
