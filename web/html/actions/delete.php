<?php
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $video_id = intval($_GET['id']);
    $song = new Song();

    $result = $song->deleteSong($video_id);

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
        echo '<strong>Success!</strong> Song deleted successfully from database and filesystem.';
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
        echo '<strong>Error!</strong> Failed to delete song. ';

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
