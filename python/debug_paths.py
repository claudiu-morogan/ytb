#!/usr/bin/env python3
"""Debug script to check paths and directories"""

import os
from funcs import downloadLocation

print("=== Path Debug Info ===")
print(f"Download location: {downloadLocation()}")
print(f"MP4 path: {downloadLocation()}/mp4/")
print(f"MP3 path: {downloadLocation()}/mp3/")

mp4_dir = downloadLocation() + '/mp4/'
mp3_dir = downloadLocation() + '/mp3/'

print(f"\nMP4 directory exists: {os.path.exists(mp4_dir)}")
print(f"MP3 directory exists: {os.path.exists(mp3_dir)}")

if os.path.exists(mp4_dir):
    mp4_files = [f for f in os.listdir(mp4_dir) if f.endswith('.mp4')]
    print(f"\nMP4 files found: {len(mp4_files)}")
    for f in mp4_files:
        print(f"  - {f}")

if os.path.exists(mp3_dir):
    mp3_files = [f for f in os.listdir(mp3_dir) if f.endswith('.mp3')]
    print(f"\nMP3 files found: {len(mp3_files)}")
    for f in mp3_files:
        print(f"  - {f}")

print("\n=== Testing ffmpeg ===")
import subprocess
try:
    result = subprocess.run(['ffmpeg', '-version'], capture_output=True, text=True)
    print("ffmpeg is installed ✓")
    print(result.stdout.split('\n')[0])
except FileNotFoundError:
    print("ffmpeg is NOT installed ✗")
