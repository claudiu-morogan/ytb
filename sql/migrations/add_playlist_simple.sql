-- Simple migration to add playlist column
-- Run this if the previous migration fails

-- Add playlist column
ALTER TABLE ytb_downloads ADD COLUMN playlist VARCHAR(100) DEFAULT 'General';

-- Add index
ALTER TABLE ytb_downloads ADD INDEX idx_playlist (playlist);

-- Update NULL values
UPDATE ytb_downloads SET playlist = 'General' WHERE playlist IS NULL;

-- Drop and recreate view
DROP VIEW IF EXISTS ytb_songs_list;

CREATE VIEW ytb_songs_list AS
SELECT
    yd.video_id AS video_id,
    ysd.artist AS artist,
    ysd.song AS song,
    yd.link AS link,
    yd.playlist AS playlist,
    IF(yd.downloaded = 1, 'yes', 'no') AS downloaded
FROM ytb_downloads yd
LEFT JOIN ytb_song_details ysd ON yd.video_id = ysd.video_id;
