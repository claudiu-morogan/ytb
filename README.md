# Song Downloader Project
This personal project is a combined PHP and Python-based application designed to manage song downloads from YouTube. It utilizes a MySQL database for storing song information and a web interface in PHP for link registration. The backend comprises Python scripts that interact with the database, extract necessary information, and download the songs to specified directories.

## Features
- **Premium Web Interface**: Modern glassmorphism-styled PHP web interface with animations and icons
- **Playlist Management**: Organize songs into custom playlists with dedicated folders
- **Database Management**: MySQL database used to store song information, including links and metadata
- **Python Backend API**: FastAPI-based REST API handles database interaction, information extraction, and song downloading
- **Automatic Conversion**: Downloads audio from YouTube and automatically converts M4A/MP4 to MP3 format
- **Organized Storage**: Playlist-based folder structure (`mp3/{PlaylistName}/{Artist_Song}.mp3`)
- **Delete Functionality**: Remove songs from database and automatically delete MP3 files
- **Playlist Filtering**: View and filter songs by playlist
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
2. **Add Songs**: Navigate to "Add Song" and paste a YouTube URL
   - Optionally select or create a playlist name
   - Songs default to "General" playlist if none specified
3. **Download Songs**: Click the "Download" button to trigger the download process
4. The Python backend will:
   - Download audio from YouTube
   - Extract song metadata (artist, title)
   - Convert M4A/MP4 to MP3
   - Organize files into playlist folders
   - Mark songs as downloaded in the database
5. **View Songs**:
   - View all songs in the "All Songs" list
   - View playlists overview in "Playlists" section
   - Filter songs by playlist
   - Delete individual songs (removes from database and deletes MP3 file)

## Database Migration

**IMPORTANT**: If you have an existing database, you MUST run the migration to add playlist support.

### Quick Migration

Run the batch script:
```bash
run_migration.bat
```

Or manually via Docker:
```bash
docker exec -i ytb_mysql mysql -uclaudiu -p123 ytb < sql/migrations/add_playlist_simple.sql
```

See [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) for detailed migration instructions and alternatives.

## Security Features

- SQL injection protection using prepared statements
- XSS protection with output escaping
- YouTube URL validation
- Error logging without exposing sensitive information
- Secure error handling throughout the application

## Architecture

The project follows a clean 3-tier architecture with proper separation of concerns:

### **Containers:**
- **MySQL Database** (port 3306) - Stores song links and metadata
- **PHP Web Interface** (port 80) - UI layer for user interaction
- **Python Backend API** (port 353) - Business logic layer (downloads, conversions, file deletions)

### **Design Pattern:**
```
User ↔ PHP (UI/Presentation) ↔ Python API (Business Logic) ↔ Database + Filesystem
```

### **Responsibilities:**
- **PHP**: Routing, templates, forms, API communication, user sessions
- **Python**: YouTube downloads, MP3 conversion, file operations, database business logic
- **MySQL**: Data persistence

### **API Endpoints:**
- `GET /trigger-downloads` - Process download queue
- `DELETE /delete-song/{video_id}` - Delete song from database and filesystem

All business logic and file operations are handled by the Python API, ensuring proper separation of concerns, security, and maintainability. See [ARCHITECTURE_FIXED.md](ARCHITECTURE_FIXED.md) for detailed architecture documentation.

## License
This project is licensed under the MIT License.

## Disclaimer
Please use this tool responsibly and ensure compliance with YouTube's terms of service and relevant laws governing the downloading and use of content.
