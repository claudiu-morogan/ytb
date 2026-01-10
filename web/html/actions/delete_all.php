<?php
// Accept both POST (with CSRF) and GET (backwards compatibility)
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) {
    // Validate CSRF token for POST requests
    Csrf::validateOrDie();
    $playlistFilter = isset($_POST['playlist']) ? $_POST['playlist'] : null;
} else {
    // GET request (backwards compatibility)
    $playlistFilter = isset($_GET['playlist']) ? $_GET['playlist'] : null;
}

try {
    $db = new DataBase();

    // Get all video IDs for the playlist (or all playlists)
    if ($playlistFilter) {
        $sql = "SELECT video_id FROM ytb_downloads WHERE playlist = ?";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        $stmt->bind_param("s", $playlistFilter);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql = "SELECT video_id FROM ytb_downloads";
        $result = $db->query($sql);

        if (!$result) {
            throw new Exception("Failed to execute query");
        }
    }

    // Collect all video IDs
    $videoIds = [];
    while ($row = $result->fetch_assoc()) {
        $videoIds[] = $row['video_id'];
    }

    if (isset($stmt)) {
        $stmt->close();
    }

    // Delete each song via Python API
    $song = new Song();
    $successCount = 0;
    $failCount = 0;
    $errors = [];

    foreach ($videoIds as $videoId) {
        $result = $song->deleteSong($videoId);
        if ($result === true) {
            $successCount++;
        } else {
            $failCount++;
            $errors[] = is_string($result) ? $result : "Failed to delete video_id: $videoId";
        }
    }

    // Display results
    $totalCount = count($videoIds);

    echo "<script>
        setTimeout(function() {
            location.href='?action=list" . ($playlistFilter ? "&playlist=" . urlencode($playlistFilter) : "") . "';
        }, 3000);
    </script>";

    echo '<div class="container mt-5">';
    echo '<div class="glass-container p-4">';

    if ($failCount === 0) {
        echo '<div class="alert alert-success alert-premium">';
        echo '<i class="fas fa-check-circle me-2"></i>';
        echo '<strong>Success!</strong> All ' . $successCount . ' song(s) deleted successfully from database and filesystem.';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning alert-premium">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>';
        echo '<strong>Partial Success:</strong> ' . $successCount . ' of ' . $totalCount . ' song(s) deleted successfully.';
        echo '<br><small>' . $failCount . ' song(s) failed to delete.</small>';

        if (!empty($errors)) {
            echo '<details class="mt-2">';
            echo '<summary>Show errors</summary>';
            echo '<ul class="mt-2 mb-0">';
            foreach (array_slice($errors, 0, 5) as $error) {
                echo '<li>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            if (count($errors) > 5) {
                echo '<li>... and ' . (count($errors) - 5) . ' more errors</li>';
            }
            echo '</ul>';
            echo '</details>';
        }

        echo '</div>';
    }

    echo '<a href="?action=list' . ($playlistFilter ? '&playlist=' . urlencode($playlistFilter) : '') . '" class="btn btn-premium mt-3">Back to List</a>';
    echo '</div>';
    echo '</div>';

} catch (Exception $e) {
    error_log("Error in delete_all.php: " . $e->getMessage());

    echo "<script>
        setTimeout(function() {
            location.href='?action=list" . ($playlistFilter ? "&playlist=" . urlencode($playlistFilter) : "") . "';
        }, 3000);
    </script>";

    echo '<div class="container mt-5">';
    echo '<div class="glass-container p-4">';
    echo '<div class="alert alert-danger alert-premium">';
    echo '<i class="fas fa-exclamation-circle me-2"></i>';
    echo '<strong>Error!</strong> Failed to delete songs. ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo '</div>';
    echo '<a href="?action=list' . ($playlistFilter ? '&playlist=' . urlencode($playlistFilter) : '') . '" class="btn btn-premium mt-3">Back to List</a>';
    echo '</div>';
    echo '</div>';
}
?>
