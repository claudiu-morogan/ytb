-- Migration script to add indexes and foreign key to existing database
-- Run this if you already have the tables created

-- Add indexes to ytb_downloads table
ALTER TABLE ytb_downloads
ADD INDEX idx_link (link),
ADD INDEX idx_downloaded (downloaded);

-- Add index and foreign key to ytb_song_details table
ALTER TABLE ytb_song_details
ADD INDEX idx_video_id (video_id),
ADD CONSTRAINT fk_video_id
    FOREIGN KEY (video_id)
    REFERENCES ytb_downloads(video_id)
    ON DELETE CASCADE;
