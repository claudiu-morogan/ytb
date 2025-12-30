#!/usr/bin/env python3
"""Test script to verify pytubefix is working correctly"""

from pytubefix import YouTube

# Test with a known video
test_url = "https://www.youtube.com/watch?v=dQw4w9WgXcQ"

try:
    print(f"Testing pytubefix with: {test_url}")
    yt = YouTube(test_url)

    print(f"\nVideo Title: {yt.title}")
    print(f"Author: {yt.author}")
    print(f"Length: {yt.length} seconds")
    print(f"Views: {yt.views}")

    # Check available properties
    print("\nAvailable audio streams:")
    audio_streams = yt.streams.filter(only_audio=True)
    for stream in audio_streams:
        print(f"  - {stream}")

    print("\n✅ pytubefix is working correctly!")

except Exception as e:
    print(f"\n❌ Error: {e}")
    import traceback
    traceback.print_exc()
