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
                        <td><?php echo htmlspecialchars($row['video_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['artist'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['song'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><a href="<?php echo htmlspecialchars($row['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['link'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></td>
                        <td class="text-center"><?php echo ($row['downloaded'] == 'yes') ? '&#10003;' : '&#x2193;'; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>