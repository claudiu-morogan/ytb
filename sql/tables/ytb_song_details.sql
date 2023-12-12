create table ytb_song_details(
	song_id int PRIMARY KEY AUTO_INCREMENT,
    video_id int,
    artist varchar(255),
    song varchar(255),
    created_at datetime default CURRENT_TIMESTAMP(),
    modified_at datetime default CURRENT_TIMESTAMP()
)