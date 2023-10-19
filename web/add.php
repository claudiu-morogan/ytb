<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <title>Add new Song</title>
</head>
<body>
    <form action="add.php" method="POST">
        <label for="link">Link</label>
        <input type="text" name="link" id="link">
        <input type="submit" value="Add">
    </form>
</body>
</html>

<?php
if($_POST){    
    $servername = "localhost";
    $username = "claudiu";
    $password = "claudiu";
    $dbname = "projects";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }

    $sql = "INSERT INTO ytb_downloads (link)
    VALUES ('".trim($_POST['link'])."')";

    if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
    } else {
    echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}



?>