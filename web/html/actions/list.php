<?php
// Filter by playlist if specified
$playlistFilter = $_GET['playlist'] ?? null;

try {
    if ($playlistFilter) {
        $sql = "SELECT * FROM ytb_songs_list WHERE playlist = ? ORDER BY video_id DESC";
        $db = new DataBase();
        $stmt = $db->connection->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->connection->error);
        }

        $stmt->bind_param("s", $playlistFilter);
        $stmt->execute();
        $dbData = $stmt->get_result();
        $stmt->close();
    } else {
        $sql = "SELECT * FROM ytb_songs_list ORDER BY video_id DESC";
        $db = new DataBase();
        $dbData = $db->query($sql);

        if (!$dbData) {
            throw new Exception("Failed to execute query");
        }
    }
} catch (Exception $e) {
    error_log("Database error in list.php: " . $e->getMessage());
    $dbData = null;
}
?>

<div class="container">
    <div class="glass-container">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h1><i class="fas fa-music icon-hover me-3"></i>Your Downloads</h1>
                <?php if ($playlistFilter): ?>
                    <p class="text-muted mb-0">
                        Playlist: <strong><?php echo htmlspecialchars($playlistFilter, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <a href="?action=list" class="ms-2 text-decoration-none">
                            <i class="fas fa-times-circle"></i> Clear filter
                        </a>
                    </p>
                <?php else: ?>
                    <p class="text-muted mb-0">Manage your YouTube downloads</p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <a href="?action=download" class="btn btn-premium me-2">
                    <i class="fas fa-download me-2"></i>Download Pending
                </a>
                <a href="?action=add" class="btn btn-premium">
                    <i class="fas fa-plus me-2"></i>Add New
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-premium">
                <thead>
                    <tr>
                        <th class="text-center"><i class="fas fa-hashtag me-2"></i>ID</th>
                        <th><i class="fas fa-user me-2"></i>Artist</th>
                        <th><i class="fas fa-music me-2"></i>Song</th>
                        <th><i class="fas fa-link me-2"></i>Link</th>
                        <th class="text-center"><i class="fas fa-check-circle me-2"></i>Status</th>
                        <th class="text-center"><i class="fas fa-cog me-2"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dbData && $dbData->num_rows > 0): ?>
                        <?php foreach($dbData as $position => $row): ?>
                            <tr>
                                <td class="text-center"><strong><?php echo htmlspecialchars($row['video_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td>
                                    <?php if($row['artist']): ?>
                                        <i class="fas fa-microphone me-2 text-primary"></i><?php echo htmlspecialchars($row['artist'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-hourglass-half me-2"></i>Pending...</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['song']): ?>
                                        <i class="fas fa-headphones me-2 text-success"></i><?php echo htmlspecialchars($row['song'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-hourglass-half me-2"></i>Pending...</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $link = $row['link'] ?? '';
                                    // Validate that it's a safe URL (starts with http:// or https://)
                                    $isSafeUrl = filter_var($link, FILTER_VALIDATE_URL) &&
                                                 (strpos($link, 'http://') === 0 || strpos($link, 'https://') === 0);
                                    ?>
                                    <?php if ($isSafeUrl): ?>
                                        <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="text-decoration-none">
                                            <i class="fas fa-external-link-alt me-2"></i>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-ban me-2"></i>Invalid URL</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['downloaded'] == 'yes'): ?>
                                        <span class="badge badge-status badge-downloaded">
                                            <i class="fas fa-check-circle me-1"></i>Downloaded
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-pending">
                                            <i class="fas fa-clock me-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button onclick="confirmDelete(<?php echo (int)$row['video_id']; ?>, <?php echo htmlspecialchars(json_encode($row['song'] ?? 'this song'), ENT_QUOTES, 'UTF-8'); ?>)"
                                            class="btn btn-delete btn-sm"
                                            title="Delete song">
                                        <i class="fas fa-trash-alt me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No songs yet</h5>
                                <p class="text-muted">Start by adding your first YouTube link!</p>
                                <a href="?action=add" class="btn btn-premium mt-3">
                                    <i class="fas fa-plus me-2"></i>Add Song
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, songName) {
    if(confirm('Are you sure you want to delete "' + songName + '"?\n\nThis will remove the song from the database and delete the MP3 file.')) {
        window.location.href = '?action=delete&id=' + id;
    }
}
</script>
