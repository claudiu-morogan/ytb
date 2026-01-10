<?php
// Get selected playlists from query string (comma-separated)
$selectedPlaylists = isset($_GET['playlists']) ? explode(',', $_GET['playlists']) : [];
$selectedPlaylists = array_filter($selectedPlaylists); // Remove empty values

try {
    // Single database connection for all queries
    $db = new DataBase();

    // Get all playlists with song counts
    $playlistsResult = $db->query("SELECT playlist, COUNT(*) as count FROM ytb_downloads GROUP BY playlist ORDER BY playlist");
    $allPlaylists = [];
    foreach($playlistsResult as $row) {
        $allPlaylists[] = [
            'name' => $row['playlist'],
            'count' => $row['count']
        ];
    }

    // Get pending count for badge
    $pendingCountQuery = "SELECT COUNT(*) as pending_count FROM ytb_downloads WHERE downloaded=0";
    $pendingResult = $db->query($pendingCountQuery);
    $pendingCount = 0;
    if ($pendingResult && $pendingResult->num_rows > 0) {
        $pendingRow = $pendingResult->fetch_assoc();
        $pendingCount = (int)$pendingRow['pending_count'];
    }

    if (!empty($selectedPlaylists)) {
        // Build query with multiple playlists
        $placeholders = implode(',', array_fill(0, count($selectedPlaylists), '?'));
        $sql = "SELECT * FROM ytb_songs_list WHERE playlist IN ($placeholders) ORDER BY video_id DESC";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        // Bind parameters dynamically
        $types = str_repeat('s', count($selectedPlaylists));
        $stmt->bind_param($types, ...$selectedPlaylists);
        $stmt->execute();
        $dbData = $stmt->get_result();
        $stmt->close();
    } else {
        $sql = "SELECT * FROM ytb_songs_list ORDER BY video_id DESC";
        $dbData = $db->query($sql);

        if (!$dbData) {
            throw new Exception("Failed to execute query");
        }
    }
} catch (Exception $e) {
    error_log("Database error in list.php: " . $e->getMessage());
    $dbData = null;
    $pendingCount = 0;
}
?>

<div class="container">
    <div class="glass-container">
        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-md-3 col-lg-2 pe-4 border-end">
                <h5 class="mb-3">
                    <i class="fas fa-filter me-2"></i>Playlists
                </h5>

                <?php if (!empty($selectedPlaylists)): ?>
                    <button onclick="clearFilters()" class="btn btn-sm btn-outline-secondary w-100 mb-3">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </button>
                <?php endif; ?>

                <div class="playlist-filter-list">
                    <?php foreach($allPlaylists as $playlist): ?>
                        <?php
                        $isSelected = in_array($playlist['name'], $selectedPlaylists);
                        ?>
                        <div class="form-check mb-2 playlist-filter-item">
                            <input class="form-check-input"
                                   type="checkbox"
                                   value="<?php echo htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                   id="playlist_<?php echo htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                   <?php echo $isSelected ? 'checked' : ''; ?>
                                   onchange="togglePlaylist(this.value, this.checked)">
                            <label class="form-check-label w-100" for="playlist_<?php echo htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fas fa-folder me-1"></i>
                                <?php echo htmlspecialchars($playlist['name'], ENT_QUOTES, 'UTF-8'); ?>
                                <span class="badge bg-secondary float-end"><?php echo $playlist['count']; ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h1><i class="fas fa-music icon-hover me-3"></i>Your Downloads</h1>
                            <?php if (!empty($selectedPlaylists)): ?>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-filter me-1"></i>
                                    Showing: <strong><?php echo count($selectedPlaylists); ?></strong> playlist(s)
                                </p>
                            <?php else: ?>
                                <p class="text-muted mb-0">Showing all songs</p>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($dbData && $dbData->num_rows > 0): ?>
                                <button onclick="confirmDeleteAll()" class="btn btn-delete">
                                    <i class="fas fa-trash-alt me-2"></i>Delete All
                                </button>
                            <?php endif; ?>
                            <a href="?action=download" class="btn btn-premium position-relative">
                                <i class="fas fa-download me-2"></i>Download Pending
                                <?php if ($pendingCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $pendingCount; ?>
                                        <span class="visually-hidden">pending songs</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <a href="?action=add" class="btn btn-premium">
                                <i class="fas fa-plus me-2"></i>Add New
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
            <table class="table table-premium">
                <thead>
                    <tr>
                        <th class="text-center"><i class="fas fa-hashtag me-2"></i>ID</th>
                        <th><i class="fas fa-user me-2"></i>Artist</th>
                        <th><i class="fas fa-music me-2"></i>Song</th>
                        <th><i class="fas fa-folder me-2"></i>Playlist</th>
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
                                    $playlist = $row['playlist'] ?? 'General';
                                    $isCurrentPlaylist = in_array($playlist, $selectedPlaylists);
                                    ?>
                                    <a href="?action=list&playlists=<?php echo urlencode($playlist); ?>"
                                       class="badge <?php echo $isCurrentPlaylist ? 'bg-primary' : 'bg-secondary'; ?> text-decoration-none"
                                       title="Filter by this playlist"
                                       style="font-size: 0.9em;">
                                        <i class="fas fa-folder me-1"></i>
                                        <?php echo htmlspecialchars($playlist, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
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
                            <td colspan="7" class="text-center py-5">
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
    </div>
</div>

<style>
.playlist-filter-item {
    transition: all 0.2s ease;
    padding: 5px;
    border-radius: 5px;
}

.playlist-filter-item:hover {
    background: rgba(102, 126, 234, 0.1);
}

.playlist-filter-item .form-check-input:checked ~ label {
    color: var(--primary);
    font-weight: 500;
}

.playlist-filter-item label {
    cursor: pointer;
    user-select: none;
}
</style>

<script>
function confirmDelete(id, songName) {
    if(confirm('Are you sure you want to delete "' + songName + '"?\n\nThis will remove the song from the database and delete the MP3 file.')) {
        // Create and submit a hidden form with CSRF token
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?action=delete';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo Csrf::generateToken(); ?>';
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }
}

function confirmDeleteAll() {
    const urlParams = new URLSearchParams(window.location.search);
    const selectedPlaylists = urlParams.get('playlists');

    const message = selectedPlaylists
        ? `Are you sure you want to delete ALL songs from the selected playlist(s)?\n\nThis will:\n- Remove all songs from the database\n- Delete all MP3 files from selected playlists\n\nThis action cannot be undone!`
        : 'Are you sure you want to delete ALL songs from ALL playlists?\n\nThis will:\n- Remove all songs from the database\n- Delete all MP3 files\n\nThis action cannot be undone!';

    if(confirm(message)) {
        // Create and submit a hidden form with CSRF token
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?action=delete_all';

        if (selectedPlaylists) {
            const playlists = selectedPlaylists.split(',');
            const playlistInput = document.createElement('input');
            playlistInput.type = 'hidden';
            playlistInput.name = 'playlist';
            playlistInput.value = playlists[0]; // For now, just handle first playlist
            form.appendChild(playlistInput);
        }

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo Csrf::generateToken(); ?>';
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }
}

function togglePlaylist(playlistName, isChecked) {
    const urlParams = new URLSearchParams(window.location.search);
    let playlists = urlParams.get('playlists') ? urlParams.get('playlists').split(',') : [];

    if (isChecked) {
        // Add playlist if not already in array
        if (!playlists.includes(playlistName)) {
            playlists.push(playlistName);
        }
    } else {
        // Remove playlist from array
        playlists = playlists.filter(p => p !== playlistName);
    }

    // Update URL
    if (playlists.length > 0) {
        window.location.href = '?action=list&playlists=' + encodeURIComponent(playlists.join(','));
    } else {
        window.location.href = '?action=list';
    }
}

function clearFilters() {
    window.location.href = '?action=list';
}
</script>
