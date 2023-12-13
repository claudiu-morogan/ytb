# Download mp4 songs from youtube
from pytube import YouTube
import PySimpleGUI as sg
from funcs import *

from dotenv import load_dotenv
from pathlib import Path
import os
import subprocess

dbConnection = dbConnect()

cursor = dbConnection.cursor()
# resetDB(cursor)
cursor.execute("SELECT link, video_id FROM ytb_downloads WHERE downloaded=0")

result = cursor.fetchall()
if cursor.rowcount != 0:
    layoutElements = [[sg.Text('Downloaded Songs')]]
    layout = [[sg.Text('Downloaded Songs')]]
    for row in result:
        (url, video_id) = (row[0], row[1])
        yt = YouTube(url)
        songName = yt.author + '_' + yt.title
        yt.streams.filter(only_audio=True).first().download(downloadLocation()+'/mp4/')


        setVideoToDownloaded(video_id, cursor, dbConnection)
        updateSongDetails(yt.author, yt.title, video_id)

        layoutElements.append([sg.Text(songName)])

    layoutElements.append([sg.Button("OK")])    
    window = sg.Window("List of downloaded songs", layoutElements)

    while True:
        event, values = window.read()
        # End program if user closes window or
        # presses the OK button
        if event == "OK" or event == sg.WIN_CLOSED:
            break

    window.close()

dbConnection.close()

location = downloadLocation()+'/mp4/'
newLocation = downloadLocation()+'/mp3/'
for filename in os.listdir(location):
    if filename.endswith(".mp4"):
        print(filename)
        subprocess.run([
            'ffmpeg',
            '-i', location+filename,
            newLocation+filename.replace('.mp4', '.mp3')
        ])