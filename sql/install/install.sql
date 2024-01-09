create table ytb_downloads (
	video_id int auto_increment primary key,
	link varchar(255) not null,
	downloaded tinyint default 0,
	genre enum('music', 'cartoons') default 'music'
);

create table ytb_song_details(
	song_id int PRIMARY KEY AUTO_INCREMENT,
    video_id int,
    artist varchar(255),
    song varchar(255),
    created_at datetime default CURRENT_TIMESTAMP(),
    modified_at datetime default CURRENT_TIMESTAMP()
);

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
	