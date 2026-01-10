<?php
// Accept both POST (with CSRF) and GET (backwards compatibility)
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) {
    // Validate CSRF token for POST requests
    Csrf::validateOrDie();
    $playlistName = isset($_POST['playlist']) ? $_POST['playlist'] : null;
} else {
    // GET request (backwards compatibility)
    $playlistName = isset($_GET['playlist']) ? $_GET['playlist'] : null;
}

if (!$playlistName || empty($playlistName)) {
    header('Location: ?action=playlists');
    exit;
}

// Prevent deletion of General playlist
if ($playlistName === 'General') {
    echo '<div class="container mt-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Cannot delete the General playlist!
            </div>
          </div>';
    echo '<script>setTimeout(function(){ window.location.href = "?action=playlists"; }, 3000);</script>';
    exit;
}

try {
    $db = new DataBase();

    // Get all songs from this playlist
    $sql = "SELECT video_id FROM ytb_downloads WHERE playlist = ?";
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }

    $stmt->bind_param('s', $playlistName);
    $stmt->execute();
    $result = $stmt->get_result();

    $videoIds = [];
    while ($row = $result->fetch_assoc()) {
        $videoIds[] = $row['video_id'];
    }
    $stmt->close();

    $totalSongs = count($videoIds);
    $deletedCount = 0;
    $failedCount = 0;

    // Delete each song via Python API
    $pythonApiUrl = 'http://ytb_python:353/delete-song/';

    foreach ($videoIds as $videoId) {
        $ch = curl_init($pythonApiUrl . $videoId);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $deletedCount++;
        } else {
            $failedCount++;
            error_log("Failed to delete song {$videoId} from playlist {$playlistName}: HTTP {$httpCode}");
        }
    }

    $db->close();

} catch (Exception $e) {
    error_log("Error deleting playlist {$playlistName}: " . $e->getMessage());
    echo '<div class="container mt-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '
            </div>
          </div>';
    echo '<script>setTimeout(function(){ window.location.href = "?action=playlists"; }, 3000);</script>';
    exit;
}
?>

<div class="container">
    <div class="glass-container">
        <div class="text-center py-5">
            <?php if ($failedCount === 0): ?>
                <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                <h2 class="mb-3">Playlist Deleted Successfully!</h2>
                <p class="text-muted mb-4">
                    <i class="fas fa-trash-alt me-2"></i>
                    Deleted <strong><?php echo $deletedCount; ?></strong> song(s) from playlist
                    <strong>"<?php echo htmlspecialchars($playlistName, ENT_QUOTES, 'UTF-8'); ?>"</strong>
                </p>
                <p class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    All database records, MP3 files, and playlist folders have been removed.
                </p>
            <?php else: ?>
                <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
                <h2 class="mb-3">Playlist Partially Deleted</h2>
                <p class="text-muted mb-4">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Successfully deleted: <strong><?php echo $deletedCount; ?></strong> song(s)
                </p>
                <p class="text-muted mb-4">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    Failed to delete: <strong><?php echo $failedCount; ?></strong> song(s)
                </p>
                <p class="text-muted small">
                    Check the error logs for more details.
                </p>
            <?php endif; ?>

            <div class="mt-4">
                <a href="?action=playlists" class="btn btn-premium">
                    <i class="fas fa-folder-open me-2"></i>Back to Playlists
                </a>
                <a href="?action=list" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-list me-2"></i>View All Songs
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-redirect after 5 seconds
setTimeout(function() {
    window.location.href = '?action=playlists';
}, 5000);
</script>
