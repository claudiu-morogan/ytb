# Song Downloader Project
This personal project is a combined PHP and Python-based application designed to manage song downloads from YouTube. It utilizes a MySQL database for storing song information and a web interface in PHP for link registration. The backend comprises Python scripts that interact with the database, extract necessary information, and download the songs to specified directories.

## Features
- Web Interface: PHP-based web interface for link registration and management.
- Database Management: MySQL database used to store song information, including links and metadata.
- Python Backend: Scripts written in Python handle database interaction, information extraction, and song downloading.
- Flexible Folder Configuration: Ability to specify download folders for organized storage.
- Song Download: Downloads songs from YouTube based on the registered links.
## Requirements
- PHP 7.x
- MySQL Database
- Python 3.x
### Dependencies:
- youtube_dl (Python) - Install using pip install youtube_dl

### Usage
* Access the PHP web interface in your browser.
* Register the YouTube links for the songs you want to download.
* Backend Python scripts will periodically extract information from the database and download the specified songs to the configured directories.

### License
This project is licensed under the MIT License.

### Disclaimer
Please use this tool responsibly and ensure compliance with YouTube's terms of service and relevant laws governing the downloading and use of content.
