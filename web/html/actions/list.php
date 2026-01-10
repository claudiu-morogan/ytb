<?php
// Get selected playlists from query string (comma-separated)
$selectedPlaylists = isset($_GET['playlists']) ? explode(',', $_GET['playlists']) : [];
$selectedPlaylists = array_filter($selectedPlaylists); // Remove empty values

// Search parameters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchField = isset($_GET['search_field']) && in_array($_GET['search_field'], ['artist', 'song', 'playlist', 'status'])
    ? $_GET['search_field']
    : 'all';

// Sorting parameters
$sortBy = isset($_GET['sort_by']) && in_array($_GET['sort_by'], ['video_id', 'artist', 'song', 'playlist', 'downloaded'])
    ? $_GET['sort_by']
    : 'video_id';
$sortOrder = isset($_GET['sort_order']) && $_GET['sort_order'] === 'asc' ? 'ASC' : 'DESC';

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

    // Build WHERE clause for search and filters
    $whereClauses = [];
    $params = [];
    $types = '';

    // Playlist filter
    if (!empty($selectedPlaylists)) {
        $placeholders = implode(',', array_fill(0, count($selectedPlaylists), '?'));
        $whereClauses[] = "playlist IN ($placeholders)";
        $params = array_merge($params, $selectedPlaylists);
        $types .= str_repeat('s', count($selectedPlaylists));
    }

    // Search filter
    if (!empty($searchQuery)) {
        if ($searchField === 'all') {
            $whereClauses[] = "(artist LIKE ? OR song LIKE ? OR playlist LIKE ?)";
            $searchParam = '%' . $searchQuery . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= 'sss';
        } elseif ($searchField === 'status') {
            // Search by status (downloaded/pending)
            $statusValue = (stripos($searchQuery, 'download') !== false) ? 1 : 0;
            $whereClauses[] = "downloaded = ?";
            $params[] = $statusValue;
            $types .= 'i';
        } else {
            // Search by specific field
            $whereClauses[] = "$searchField LIKE ?";
            $params[] = '%' . $searchQuery . '%';
            $types .= 's';
        }
    }

    $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM ytb_songs_list $whereSQL";
    if (!empty($params)) {
        $countStmt = $db->prepare($countSql);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRows = $countResult->fetch_assoc()['total'];
        $countStmt->close();
    } else {
        $countResult = $db->query($countSql);
        $totalRows = $countResult->fetch_assoc()['total'];
    }

    $totalPages = ceil($totalRows / $itemsPerPage);

    // Get paginated data with sorting
    $sql = "SELECT * FROM ytb_songs_list $whereSQL ORDER BY $sortBy $sortOrder LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }

    // Add pagination parameters
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $dbData = $stmt->get_result();
    $stmt->close();
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

                <!-- Search Bar -->
                <div class="search-container mb-4">
                    <form method="GET" action="" class="row g-2">
                        <input type="hidden" name="action" value="list">
                        <?php if (!empty($selectedPlaylists)): ?>
                            <input type="hidden" name="playlists" value="<?php echo htmlspecialchars(implode(',', $selectedPlaylists), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="per_page" value="<?php echo $itemsPerPage; ?>">
                        <input type="hidden" name="sort_by" value="<?php echo $sortBy; ?>">
                        <input type="hidden" name="sort_order" value="<?php echo $sortOrder; ?>">

                        <div class="col-md-3">
                            <select name="search_field" class="form-select form-select-sm">
                                <option value="all" <?php echo $searchField === 'all' ? 'selected' : ''; ?>>All Fields</option>
                                <option value="artist" <?php echo $searchField === 'artist' ? 'selected' : ''; ?>>Artist</option>
                                <option value="song" <?php echo $searchField === 'song' ? 'selected' : ''; ?>>Song</option>
                                <option value="playlist" <?php echo $searchField === 'playlist' ? 'selected' : ''; ?>>Playlist</option>
                                <option value="status" <?php echo $searchField === 'status' ? 'selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input type="text"
                                   name="search"
                                   class="form-control form-control-sm"
                                   placeholder="Search songs..."
                                   value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($searchQuery)): ?>
                                <a href="?action=list<?php echo !empty($selectedPlaylists) ? '&playlists=' . urlencode(implode(',', $selectedPlaylists)) : ''; ?>&per_page=<?php echo $itemsPerPage; ?>"
                                   class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if (!empty($searchQuery)): ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-search me-1"></i>
                                Searching in <strong><?php echo $searchField === 'all' ? 'all fields' : $searchField; ?></strong>
                                for "<strong><?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></strong>"
                            </small>
                        </div>
                    <?php endif; ?>
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

                <div class="table-responsive table-container">
            <table class="table table-premium table-fixed-layout">
                <thead>
                    <tr>
                        <th class="text-center col-id sortable" onclick="sortTable('video_id')">
                            <i class="fas fa-hashtag me-1"></i>ID
                            <?php if ($sortBy === 'video_id'): ?>
                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </th>
                        <th class="col-artist sortable" onclick="sortTable('artist')">
                            <i class="fas fa-user me-1"></i>Artist
                            <?php if ($sortBy === 'artist'): ?>
                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </th>
                        <th class="col-song sortable" onclick="sortTable('song')">
                            <i class="fas fa-music me-1"></i>Song
                            <?php if ($sortBy === 'song'): ?>
                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </th>
                        <th class="text-center col-playlist sortable" onclick="sortTable('playlist')">
                            <i class="fas fa-folder me-1"></i>Playlist
                            <?php if ($sortBy === 'playlist'): ?>
                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </th>
                        <th class="col-link"><i class="fas fa-link me-1"></i>Link</th>
                        <th class="text-center col-status sortable" onclick="sortTable('downloaded')">
                            <i class="fas fa-check-circle me-1"></i>Status
                            <?php if ($sortBy === 'downloaded'): ?>
                                <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?> ms-1"></i>
                            <?php endif; ?>
                        </th>
                        <th class="text-center col-actions"><i class="fas fa-cog me-1"></i>Actions</th>
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
                                    <div class="btn-group" role="group">
                                        <button onclick="showMoveModal(<?php echo (int)$row['video_id']; ?>, <?php echo htmlspecialchars(json_encode($row['song'] ?? 'this song'), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($row['playlist'] ?? 'General'), ENT_QUOTES, 'UTF-8'); ?>)"
                                                class="btn btn-outline-primary btn-sm"
                                                title="Move to another playlist">
                                            <i class="fas fa-folder-open me-1"></i>Move
                                        </button>
                                        <button onclick="confirmDelete(<?php echo (int)$row['video_id']; ?>, <?php echo htmlspecialchars(json_encode($row['song'] ?? 'this song'), ENT_QUOTES, 'UTF-8'); ?>)"
                                                class="btn btn-delete btn-sm"
                                                title="Delete song">
                                            <i class="fas fa-trash-alt me-1"></i>Delete
                                        </button>
                                    </div>
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

<!-- Move Song Modal -->
<div class="modal fade" id="moveSongModal" tabindex="-1" aria-labelledby="moveSongModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(0,0,0,0.1);">
                <h5 class="modal-title" id="moveSongModalLabel">
                    <i class="fas fa-folder-open me-2" style="color: var(--primary);"></i>Move Song to Playlist
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    <strong>Song:</strong> <span id="moveSongName"></span><br>
                    <strong>Current Playlist:</strong> <span id="moveCurrentPlaylist"></span>
                </p>
                <div class="mb-3">
                    <label for="newPlaylistSelect" class="form-label">Select New Playlist:</label>
                    <select class="form-select" id="newPlaylistSelect" required>
                        <option value="">-- Select Playlist --</option>
                        <?php foreach($allPlaylists as $pl): ?>
                            <option value="<?php echo htmlspecialchars($pl['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($pl['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo $pl['count']; ?> songs)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="newPlaylistInput" class="form-label">Or Create New Playlist:</label>
                    <input type="text" class="form-control" id="newPlaylistInput" placeholder="Type new playlist name">
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.1);">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-premium" onclick="confirmMove()">
                    <i class="fas fa-check me-2"></i>Move Song
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build pagination URLs
function buildPaginationUrl($page, $perPage, $playlists) {
    global $searchQuery, $searchField, $sortBy, $sortOrder;

    $params = ['action' => 'list', 'page' => $page, 'per_page' => $perPage];

    if (!empty($playlists)) {
        $params['playlists'] = implode(',', $playlists);
    }

    if (!empty($searchQuery)) {
        $params['search'] = $searchQuery;
        $params['search_field'] = $searchField;
    }

    $params['sort_by'] = $sortBy;
    $params['sort_order'] = $sortOrder;

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

/* Table Responsive Container */
.table-container {
    overflow-x: auto;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Fixed table layout for better control */
.table-fixed-layout {
    table-layout: fixed;
    min-width: 1200px;
    margin-bottom: 0;
}

/* Column widths - ensures Actions column is always visible */
.table-fixed-layout .col-id {
    width: 70px;
}

.table-fixed-layout .col-artist {
    width: 180px;
}

.table-fixed-layout .col-song {
    width: 200px;
}

.table-fixed-layout .col-playlist {
    width: 130px;
}

.table-fixed-layout .col-link {
    width: 220px;
}

.table-fixed-layout .col-status {
    width: 130px;
}

.table-fixed-layout .col-actions {
    width: 200px;
    min-width: 200px; /* Ensure Actions column never shrinks */
}

/* Text overflow handling for long content */
.table-fixed-layout td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Allow wrapping in Actions column for buttons */
.table-fixed-layout td:last-child {
    white-space: normal;
}

/* Improve button group spacing in Actions column */
.btn-group .btn {
    white-space: nowrap;
}

/* Make action buttons more compact on smaller screens */
@media (max-width: 768px) {
    .btn-group .btn-sm {
        padding: 6px 10px;
        font-size: 0.8rem;
    }

    .btn-group .btn-sm i {
        margin-right: 4px !important;
    }
}

/* Sortable table headers */
.sortable {
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
}

.sortable:hover {
    background: rgba(255, 255, 255, 0.2) !important;
}

.sortable:active {
    transform: scale(0.98);
}

/* Search container styles */
.search-container {
    background: rgba(255, 255, 255, 0.5);
    padding: 15px;
    border-radius: 15px;
    border: 1px solid rgba(102, 126, 234, 0.2);
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
// Sort table functionality
function sortTable(column) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort_by');
    const currentOrder = urlParams.get('sort_order');

    // Toggle order if clicking same column, otherwise default to DESC
    let newOrder = 'desc';
    if (currentSort === column && currentOrder === 'desc') {
        newOrder = 'asc';
    }

    urlParams.set('sort_by', column);
    urlParams.set('sort_order', newOrder);
    urlParams.set('page', '1'); // Reset to first page when sorting

    window.location.href = '?' + urlParams.toString();
}

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

// Move song functionality
let currentMoveSongId = null;

function showMoveModal(id, songName, currentPlaylist) {
    currentMoveSongId = id;
    document.getElementById('moveSongName').textContent = songName;
    document.getElementById('moveCurrentPlaylist').textContent = currentPlaylist;

    // Clear previous selections
    document.getElementById('newPlaylistSelect').value = '';
    document.getElementById('newPlaylistInput').value = '';

    // Remove current playlist from dropdown options (disable it)
    const select = document.getElementById('newPlaylistSelect');
    Array.from(select.options).forEach(option => {
        if (option.value === currentPlaylist) {
            option.disabled = true;
            option.textContent += ' (Current)';
        } else {
            option.disabled = false;
            // Remove (Current) text if it exists
            option.textContent = option.textContent.replace(' (Current)', '');
        }
    });

    // Show modal using Bootstrap 5
    const modal = new bootstrap.Modal(document.getElementById('moveSongModal'));
    modal.show();
}

function confirmMove() {
    const selectValue = document.getElementById('newPlaylistSelect').value;
    const inputValue = document.getElementById('newPlaylistInput').value.trim();

    // Determine which playlist to use
    const newPlaylist = inputValue || selectValue;

    console.log('confirmMove called', {
        currentMoveSongId: currentMoveSongId,
        selectValue: selectValue,
        inputValue: inputValue,
        newPlaylist: newPlaylist
    });

    if (!newPlaylist) {
        alert('Please select a playlist or enter a new playlist name.');
        return;
    }

    // Close the modal before submitting
    const modalElement = document.getElementById('moveSongModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }

    // Create and submit a hidden form with CSRF token
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '?action=move_song';

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = currentMoveSongId;
    form.appendChild(idInput);

    const playlistInput = document.createElement('input');
    playlistInput.type = 'hidden';
    playlistInput.name = 'playlist';
    playlistInput.value = newPlaylist;
    form.appendChild(playlistInput);

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?php echo Csrf::generateToken(); ?>';
    form.appendChild(csrfInput);

    console.log('Submitting form', {
        action: form.action,
        id: idInput.value,
        playlist: playlistInput.value
    });

    document.body.appendChild(form);
    form.submit();
}

// Auto-switch between select and input
document.getElementById('newPlaylistSelect')?.addEventListener('change', function() {
    if (this.value) {
        document.getElementById('newPlaylistInput').value = '';
    }
});

document.getElementById('newPlaylistInput')?.addEventListener('input', function() {
    if (this.value) {
        document.getElementById('newPlaylistSelect').value = '';
    }
});
</script>
