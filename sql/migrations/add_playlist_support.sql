-- Migration to add playlist support
-- The genre column already exists, we'll use it as playlist name

-- First, let's change the enum to allow custom playlist names
ALTER TABLE ytb_downloads MODIFY COLUMN genre VARCHAR(100) DEFAULT 'General';

-- Update existing records
UPDATE ytb_downloads SET genre = 'General' WHERE genre = 'music' OR genre IS NULL;
UPDATE ytb_downloads SET genre = 'Cartoons' WHERE genre = 'cartoons';

-- Rename column for clarity (optional - keeps backward compatibility)
-- ALTER TABLE ytb_downloads CHANGE COLUMN genre playlist VARCHAR(100) DEFAULT 'General';
