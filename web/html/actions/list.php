<?php
// Get selected playlists from query string (comma-separated)
$selectedPlaylists = isset($_GET['playlists']) ? explode(',', $_GET['playlists']) : [];
$selectedPlaylists = array_filter($selectedPlaylists); // Remove empty values

// Pagination parameters
$itemsPerPageOptions = [5, 10, 15, 20, 25];
$itemsPerPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $itemsPerPageOptions)
    ? (int)$_GET['per_page']
    : 10; // Default to 10
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

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

    // Get total count for pagination
    if (!empty($selectedPlaylists)) {
        $placeholders = implode(',', array_fill(0, count($selectedPlaylists), '?'));
        $countSql = "SELECT COUNT(*) as total FROM ytb_songs_list WHERE playlist IN ($placeholders)";
        $countStmt = $db->prepare($countSql);
        $types = str_repeat('s', count($selectedPlaylists));
        $countStmt->bind_param($types, ...$selectedPlaylists);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRows = $countResult->fetch_assoc()['total'];
        $countStmt->close();
    } else {
        $countSql = "SELECT COUNT(*) as total FROM ytb_songs_list";
        $countResult = $db->query($countSql);
        $totalRows = $countResult->fetch_assoc()['total'];
    }

    $totalPages = ceil($totalRows / $itemsPerPage);

    // Get paginated data
    if (!empty($selectedPlaylists)) {
        // Build query with multiple playlists and pagination
        $placeholders = implode(',', array_fill(0, count($selectedPlaylists), '?'));
        $sql = "SELECT * FROM ytb_songs_list WHERE playlist IN ($placeholders) ORDER BY video_id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        // Bind parameters dynamically (playlists + limit + offset)
        $types = str_repeat('s', count($selectedPlaylists)) . 'ii';
        $params = array_merge($selectedPlaylists, [$itemsPerPage, $offset]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $dbData = $stmt->get_result();
        $stmt->close();
    } else {
        $sql = "SELECT * FROM ytb_songs_list ORDER BY video_id DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ii', $itemsPerPage, $offset);
        $stmt->execute();
        $dbData = $stmt->get_result();
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Database error in list.php: " . $e->getMessage());
    $dbData = null;
    $pendingCount = 0;
    $totalRows = 0;
    $totalPages = 0;
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

                <!-- Pagination Controls - Top -->
                <?php if ($totalRows > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted">
                        Showing <strong><?php echo min($offset + 1, $totalRows); ?></strong> to
                        <strong><?php echo min($offset + $itemsPerPage, $totalRows); ?></strong> of
                        <strong><?php echo $totalRows; ?></strong> songs
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="perPageSelect" class="mb-0 text-muted small">Items per page:</label>
                        <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeItemsPerPage(this.value)">
                            <?php foreach($itemsPerPageOptions as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo $option === $itemsPerPage ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

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

        <!-- Pagination Controls - Bottom -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-premium">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link page-nav-btn" href="<?php echo $currentPage > 1 ? buildPaginationUrl($currentPage - 1, $itemsPerPage, $selectedPlaylists) : '#'; ?>" aria-label="Previous">
                            <i class="fas fa-chevron-left"></i> <span class="d-none d-md-inline">Previous</span>
                        </a>
                    </li>

                    <?php
                    // Calculate page range to display
                    $range = 2; // Pages to show on each side of current page
                    $startPage = max(1, $currentPage - $range);
                    $endPage = min($totalPages, $currentPage + $range);

                    // Show first page if not in range
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link page-number" href="' . buildPaginationUrl(1, $itemsPerPage, $selectedPlaylists) . '">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link page-dots">...</span></li>';
                        }
                    }

                    // Show page numbers in range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = $i === $currentPage ? 'active' : '';
                        echo '<li class="page-item ' . $activeClass . '">';
                        echo '<a class="page-link page-number" href="' . buildPaginationUrl($i, $itemsPerPage, $selectedPlaylists) . '">' . $i . '</a>';
                        echo '</li>';
                    }

                    // Show last page if not in range
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link page-dots">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link page-number" href="' . buildPaginationUrl($totalPages, $itemsPerPage, $selectedPlaylists) . '">' . $totalPages . '</a></li>';
                    }
                    ?>

                    <!-- Next Button -->
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link page-nav-btn" href="<?php echo $currentPage < $totalPages ? buildPaginationUrl($currentPage + 1, $itemsPerPage, $selectedPlaylists) : '#'; ?>" aria-label="Next">
                            <span class="d-none d-md-inline">Next</span> <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build pagination URLs
function buildPaginationUrl($page, $perPage, $playlists) {
    $params = ['action' => 'list', 'page' => $page, 'per_page' => $perPage];
    if (!empty($playlists)) {
        $params['playlists'] = implode(',', $playlists);
    }
    return '?' . http_build_query($params);
}
?>

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

/* Pagination Styles */
.pagination-premium {
    margin: 0;
    gap: 5px;
}

/* Base page link styles */
.pagination-premium .page-link {
    border: none;
    margin: 0;
    transition: all 0.3s ease;
    font-weight: 500;
}

/* Page number buttons (1, 2, 3, etc.) */
.pagination-premium .page-number {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    background: rgba(102, 126, 234, 0.1);
}

.pagination-premium .page-number:hover {
    background: var(--gradient);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.pagination-premium .page-item.active .page-number {
    background: var(--gradient);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Previous/Next navigation buttons */
.pagination-premium .page-nav-btn {
    border-radius: 50px;
    padding: 10px 20px;
    color: var(--primary);
    background: rgba(102, 126, 234, 0.1);
    font-weight: 600;
    min-width: 50px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pagination-premium .page-nav-btn:hover {
    background: var(--gradient);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Dots (...) */
.pagination-premium .page-dots {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: #999;
    cursor: default;
}

/* Disabled state */
.pagination-premium .page-item.disabled .page-link {
    background: rgba(0, 0, 0, 0.05);
    color: #999;
    cursor: not-allowed;
    opacity: 0.5;
}

.pagination-premium .page-item.disabled .page-link:hover {
    transform: none;
    box-shadow: none;
    background: rgba(0, 0, 0, 0.05);
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

function changeItemsPerPage(perPage) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', perPage);
    urlParams.set('page', '1'); // Reset to first page when changing items per page
    window.location.href = '?' + urlParams.toString();
}
</script>
