#!/usr/bin/env python3
"""Convert existing M4A files to MP3"""

import os
import subprocess
import logging
from funcs import downloadLocation

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

location = downloadLocation() + '/mp4/'
newLocation = downloadLocation() + '/mp3/'

# Ensure MP3 directory exists
os.makedirs(newLocation, exist_ok=True)

audio_files = [f for f in os.listdir(location) if f.endswith(('.mp4', '.m4a', '.webm'))]
logger.info(f"Found {len(audio_files)} audio files to convert")

for filename in audio_files:
    input_path = os.path.join(location, filename)
    mp3_filename = filename.rsplit('.', 1)[0] + '.mp3'
    mp3_path = os.path.join(newLocation, mp3_filename)

    logger.info(f"Converting: {filename}")
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

        logger.info(f"✓ Converted to: {mp3_filename}")

        # Delete source file
        os.remove(input_path)
        logger.info(f"✓ Deleted: {filename}")

    except subprocess.CalledProcessError as e:
        logger.error(f"✗ Failed to convert {filename}")
        logger.error(f"  STDERR: {e.stderr}")

logger.info("Conversion completed!")
