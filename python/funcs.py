import mysql.connector

def resetDB(cursor):
    sql = "UPDATE ytb_downloads SET downloaded=0"
    cursor.execute(sql)

def dbConnect():
    projectDB = mysql.connector.connect(
        host = "localhost",
        user = "claudiu",
        password = "claudiu",
        database = "projects"
    )
    return projectDB

def setVideoToDownloaded(video_id, cursor, dbConnection):
    sql = "UPDATE ytb_downloads SET downloaded=1 where video_id = "+ str(video_id)
    cursor.execute(sql)
    dbConnection.commit()


def updateSongDetails(artist, title, video_id):   
    dbConnection = dbConnect()

    sql = "SELECT count(*) registrations FROM ytb_song_details where video_id = %s"
    val = (str(video_id), )

    cursor = dbConnection.cursor()
    cursor.execute(sql, val)
    result = cursor.fetchone()
    count = result[0]    

    if count == 1:
        sql = "UPDATE ytb_song_details SET artist = %s, song = %s where video_id = %s"
        val = (artist, title, str(video_id))
        cursor.execute(sql, val)
        dbConnection.commit()
    else:
        sql = "INSERT INTO ytb_song_details (artist, song, video_id) VALUES (%s, %s, %s)"
        val = (artist, title, str(video_id))
        cursor.execute(sql, val)
        dbConnection.commit()

    dbConnection.close()

    

    
        






    


