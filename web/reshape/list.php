<?php

$sql = "SELECT download_id, link, if(downloaded=1, 'yes', 'no') downloaded FROM ytb_downloads";
$db = new DataBase();
$dbData = $db->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <title>Ytb Downloads</title>
</head>

<body>

    <div class="row">
        <div class="col-12">
            <h1>Ytb Downloads</h1>
        </div>
    </div>
    <table class="table">
        <thead class="thead-dark">
            <tr>
                <th class="text-center">Download ID</th>
                <th class="text-center">Link</th>
                <th class="text-center">Downloaded</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($dbData as $position => $row) { ?>
                <tr>
                    <td><?php echo $row['download_id']; ?></td>
                    <td><a href="<?=$row['link'];?>" target="_blank"><?php echo $row['link']; ?></a></td>
                    <td class="text-center"><?php echo ($row['downloaded'] == 'yes') ? '&#10003;' : '&#x2193;'; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>
