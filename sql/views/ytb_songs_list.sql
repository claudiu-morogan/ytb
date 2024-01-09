CREATE OR REPLACE VIEW `ytb_songs_list` AS 
select
	`yd`.`video_id` AS `video_id`,
	`ysd`.`artist` AS `artist`,
	`ysd`.`song` AS `song`,
	`yd`.`link` AS `link`,
	if(`yd`.`downloaded` = 1,
	'yes',
	'no') AS `downloaded`
from
	`ytb_downloads` `yd`
	left join `ytb_song_details` `ysd` on `yd`.`video_id` = `ysd`.`video_id`
	