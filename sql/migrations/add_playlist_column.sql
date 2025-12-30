-- Migration to add playlist support to existing database
-- Run this on your existing database to add the playlist column

-- Check if the column exists first, if not add it
-- If you have a 'genre' column, you can rename it, otherwise add new column

-- Option 1: If you have an existing 'genre' column, rename it to 'playlist'
-- ALTER TABLE ytb_downloads CHANGE COLUMN genre playlist VARCHAR(100) DEFAULT 'General';

-- Option 2: If you don't have a 'genre' column, add 'playlist' column
ALTER TABLE ytb_downloads ADD COLUMN IF NOT EXISTS playlist VARCHAR(100) DEFAULT 'General';

-- Add index for better query performance
ALTER TABLE ytb_downloads ADD INDEX IF NOT EXISTS idx_playlist (playlist);

-- Update any NULL values to 'General'
UPDATE ytb_downloads SET playlist = 'General' WHERE playlist IS NULL;

-- Recreate the view to include playlist column
DROP VIEW IF EXISTS ytb_songs_list;

CREATE OR REPLACE VIEW `ytb_songs_list` AS
select
	`yd`.`video_id` AS `video_id`,
	`ysd`.`artist` AS `artist`,
	`ysd`.`song` AS `song`,
	`yd`.`link` AS `link`,
	`yd`.`playlist` AS `playlist`,
	if(`yd`.`downloaded` = 1, 'yes', 'no') AS `downloaded`
from
	`ytb_downloads` `yd`
	left join `ytb_song_details` `ysd` on `yd`.`video_id` = `ysd`.`video_id`;
