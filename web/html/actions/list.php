<?php
$sql = "SELECT * FROM ytb_songs_list";
$db = new DataBase();
$dbData = $db->query($sql);
?>

<div class="container">

    <div class="row">
            <div class="col-12 text-center">
                <h1>Ytb Downloads</h1>
            </div>
    </div>
    <div class="row">
        <table class="table">
            <thead class="thead-dark">
                <tr>
                    <th class="text-center">Download ID</th>
                    <th class="text-center">Artist</th>
                    <th class="text-center">Song</th>
                    <th class="text-center">Link</th>
                    <th class="text-center">Downloaded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($dbData as $position => $row) { ?>
                    <tr>
                        <td><?php echo $row['video_id']; ?></td>
                        <td><?php echo $row['artist']; ?></td>
                        <td><?php echo $row['song']; ?></td>
                        <td><a href="<?=$row['link'];?>" target="_blank"><?php echo $row['link']; ?></a></td>
                        <td class="text-center"><?php echo ($row['downloaded'] == 'yes') ? '&#10003;' : '&#x2193;'; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>