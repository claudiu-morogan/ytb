<?php
// Accept both POST (with CSRF) and GET (backwards compatibility)
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) {
    // Validate CSRF token for POST requests
    Csrf::validateOrDie();
    $video_id = isset($_POST['id']) && is_numeric($_POST['id']) ? intval($_POST['id']) : null;
    $new_playlist = isset($_POST['playlist']) ? trim($_POST['playlist']) : null;
} else {
    // GET request (backwards compatibility)
    $video_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $new_playlist = isset($_GET['playlist']) ? trim($_GET['playlist']) : null;
}

if($video_id && $new_playlist) {
    $song = new Song();
    $result = $song->moveSong($video_id, $new_playlist);

    if($result === true) {
        echo "<script>
            setTimeout(function() {
                location.href='?action=list';
            }, 1500);
        </script>";
        echo '<div class="container mt-5">';
        echo '<div class="glass-container p-4">';
        echo '<div class="alert alert-success alert-premium">';
        echo '<i class="fas fa-check-circle me-2"></i>';
        echo '<strong>Success!</strong> Song moved to playlist "' . htmlspecialchars($new_playlist, ENT_QUOTES, 'UTF-8') . '" successfully.';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    } else {
        echo "<script>
            setTimeout(function() {
                location.href='?action=list';
            }, 3000);
        </script>";
        echo '<div class="container mt-5">';
        echo '<div class="glass-container p-4">';
        echo '<div class="alert alert-danger alert-premium">';
        echo '<i class="fas fa-exclamation-circle me-2"></i>';
        echo '<strong>Error!</strong> Failed to move song. ';

        // Show more specific error if available
        if(is_string($result)) {
            echo 'Reason: ' . htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        } else {
            echo 'The Python API may be unavailable or the song was not found.';
        }

        echo '<br><small>Check Docker logs for more details: <code>docker logs ytb_python</code></small>';
        echo '</div>';
        echo '<a href="?action=list" class="btn btn-premium mt-3">Back to List</a>';
        echo '</div>';
        echo '</div>';
    }
} else {
    header('Location: ?action=list');
    exit;
}
?>
