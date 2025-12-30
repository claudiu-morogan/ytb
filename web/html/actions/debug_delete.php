<?php
// Debug script to check file access from PHP container
// Access via: http://localhost?action=debug_delete

echo "<div class='container mt-5'>";
echo "<div class='glass-container p-4'>";
echo "<h2>Delete Function Debug Info</h2>";

// Check if mp3 directory is accessible
$basePath = '/var/www/html/mp3';

echo "<h4>MP3 Directory Check:</h4>";
echo "<p><strong>Base Path:</strong> " . htmlspecialchars($basePath) . "</p>";

if (file_exists($basePath)) {
    echo "<p style='color: green;'>✓ Base directory EXISTS</p>";

    if (is_dir($basePath)) {
        echo "<p style='color: green;'>✓ Base directory IS a directory</p>";

        if (is_readable($basePath)) {
            echo "<p style='color: green;'>✓ Base directory IS readable</p>";

            // List playlists (subdirectories)
            echo "<h4>Playlists Found:</h4>";
            $items = scandir($basePath);
            $playlists = array_filter($items, function($item) use ($basePath) {
                return $item !== '.' && $item !== '..' && is_dir($basePath . '/' . $item);
            });

            if (empty($playlists)) {
                echo "<p style='color: orange;'>⚠ No playlist folders found</p>";
            } else {
                echo "<ul>";
                foreach ($playlists as $playlist) {
                    $playlistPath = $basePath . '/' . $playlist;
                    $files = array_filter(scandir($playlistPath), function($f) {
                        return pathinfo($f, PATHINFO_EXTENSION) === 'mp3';
                    });
                    echo "<li><strong>" . htmlspecialchars($playlist) . "</strong> (" . count($files) . " MP3 files)";

                    if (!empty($files)) {
                        echo "<ul>";
                        foreach ($files as $file) {
                            $fullPath = $playlistPath . '/' . $file;
                            $writable = is_writable($fullPath);
                            $color = $writable ? 'green' : 'red';
                            $symbol = $writable ? '✓' : '✗';
                            echo "<li style='color: $color;'>$symbol " . htmlspecialchars($file) .
                                 " (" . number_format(filesize($fullPath) / 1024 / 1024, 2) . " MB)</li>";
                        }
                        echo "</ul>";
                    }
                    echo "</li>";
                }
                echo "</ul>";
            }

            // Also check root level
            echo "<h4>Root Level MP3 Files:</h4>";
            $rootFiles = array_filter(scandir($basePath), function($f) use ($basePath) {
                return pathinfo($f, PATHINFO_EXTENSION) === 'mp3' && is_file($basePath . '/' . $f);
            });

            if (empty($rootFiles)) {
                echo "<p style='color: gray;'>No MP3 files in root directory</p>";
            } else {
                echo "<ul>";
                foreach ($rootFiles as $file) {
                    $fullPath = $basePath . '/' . $file;
                    $writable = is_writable($fullPath);
                    $color = $writable ? 'green' : 'red';
                    $symbol = $writable ? '✓' : '✗';
                    echo "<li style='color: $color;'>$symbol " . htmlspecialchars($file) .
                         " (" . number_format(filesize($fullPath) / 1024 / 1024, 2) . " MB)</li>";
                }
                echo "</ul>";
            }

        } else {
            echo "<p style='color: red;'>✗ Base directory NOT readable</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Base path exists but is NOT a directory</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Base directory DOES NOT EXIST</p>";
    echo "<p><strong>This means:</strong></p>";
    echo "<ul>";
    echo "<li>The volume is not mounted correctly in docker-compose.yml</li>";
    echo "<li>OR the containers were not restarted after updating docker-compose.yml</li>";
    echo "</ul>";
    echo "<p><strong>Solution:</strong> Run: <code>docker-compose down && docker-compose up -d</code></p>";
}

echo "<hr>";
echo "<h4>Container Info:</h4>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Current User:</strong> " . get_current_user() . "</p>";
echo "<p><strong>Working Directory:</strong> " . getcwd() . "</p>";

echo "<hr>";
echo "<a href='?action=list' class='btn btn-premium'>Back to List</a>";
echo "</div>";
echo "</div>";
?>
