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
    sql = "UPDATE ytb_downloads SET downloaded=1 where download_id = "+ str(video_id)
    cursor.execute(sql)
    dbConnection.commit()
