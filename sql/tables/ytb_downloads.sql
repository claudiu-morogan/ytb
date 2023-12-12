create table ytb_downloads (
	video_id int auto_increment primary key,
	link varchar(255) not null,
	downloaded tinyint default 0,
	genre enum('music', 'cartoons') default 'music'
);