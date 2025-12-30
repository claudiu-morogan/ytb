# Song Downloader Project
This personal project is a combined PHP and Python-based application designed to manage song downloads from YouTube. It utilizes a MySQL database for storing song information and a web interface in PHP for link registration. The backend comprises Python scripts that interact with the database, extract necessary information, and download the songs to specified directories.

## Features
- **Web Interface**: PHP-based web interface for link registration and management
- **Database Management**: MySQL database used to store song information, including links and metadata
- **Python Backend API**: FastAPI-based REST API handles database interaction, information extraction, and song downloading
- **Automatic Conversion**: Downloads audio from YouTube and automatically converts MP4 to MP3 format
- **Flexible Folder Configuration**: Ability to specify download folders for organized storage
- **Dockerized**: Fully containerized application with Docker Compose for easy deployment

## Requirements

### For Docker Deployment (Recommended)
- Docker
- Docker Compose

### For Manual Installation
- PHP 7.x or higher
- MySQL 8.0 or higher
- Python 3.11 or higher
- FFmpeg

### Python Dependencies
See [python/requirements.txt](python/requirements.txt) for the full list. Key dependencies include:
- `pytubefix` - YouTube video downloading (replaces deprecated pytube)
- `FastAPI` - REST API framework
- `mysql-connector-python` - Database connectivity
- `python-dotenv` - Environment configuration

## Installation & Usage

### Docker Deployment (Recommended)

1. Clone the repository
2. Create a `.env` file in the root directory with your configuration:
   ```env
   db_host=ytb_mysql
   db_user=your_user
   db_password=your_password
   db_name=ytb
   download_location=/scripts
   ```
3. Start the containers:
   ```bash
   docker-compose up -d
   ```
4. Access the web interface at `http://localhost`

### Manual Installation

1. Install dependencies listed in `python/requirements.txt`
2. Configure your database connection in `.env` file
3. Import the database schema from `sql/install/install.sql`
4. Configure your web server to serve the `web` directory
5. Start the Python API: `uvicorn backendAPI:app --host 0.0.0.0 --port 353`

## How to Use

1. Access the PHP web interface in your browser
2. Register YouTube links for the songs you want to download
3. Click the download button to trigger the download process
4. The Python backend will:
   - Download audio from YouTube
   - Extract song metadata (artist, title)
   - Convert MP4 to MP3
   - Mark songs as downloaded in the database
5. View all downloaded songs in the list view

## Database Migration

If you have an existing database, run the migration script to add indexes and foreign keys:
```sql
source sql/migrations/add_indexes.sql
```

## Security Features

- SQL injection protection using prepared statements
- XSS protection with output escaping
- YouTube URL validation
- Error logging without exposing sensitive information
- Secure error handling throughout the application

## Architecture

The project uses a 3-tier Docker architecture:
- **MySQL Database** (port 3306) - Stores song links and metadata
- **PHP Web Interface** (port 80) - Frontend for user interaction
- **Python Backend API** (port 353) - Handles downloading via FastAPI

## License
This project is licensed under the MIT License.

## Disclaimer
Please use this tool responsibly and ensure compliance with YouTube's terms of service and relevant laws governing the downloading and use of content.
