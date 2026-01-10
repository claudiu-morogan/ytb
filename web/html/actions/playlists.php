<?php
$sql = "SELECT
    playlist,
    COUNT(*) as total_songs,
    SUM(CASE WHEN downloaded = 1 THEN 1 ELSE 0 END) as downloaded_songs
FROM ytb_downloads
GROUP BY playlist
ORDER BY playlist";

$db = new DataBase();
$playlists = $db->query($sql);
?>

<div class="container">
    <div class="glass-container">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h1><i class="fas fa-folder-open icon-hover me-3"></i>Playlists</h1>
                <p class="text-muted mb-0">Organize your downloads by playlist</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="?action=add" class="btn btn-premium">
                    <i class="fas fa-plus me-2"></i>Add Song
                </a>
            </div>
        </div>

        <div class="row">
            <?php if ($playlists->num_rows > 0): ?>
                <?php foreach($playlists as $playlist): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 20px; overflow: hidden; transition: all 0.3s ease;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                                         style="width: 60px; height: 60px; background: var(--gradient);">
                                        <i class="fas fa-folder fa-2x text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0"><?php echo htmlspecialchars($playlist['playlist'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <small class="text-muted"><?php echo $playlist['total_songs']; ?> songs</small>
                                    </div>
                                </div>

                                <div class="progress mb-3" style="height: 8px; border-radius: 10px;">
                                    <?php
                                    $percentage = $playlist['total_songs'] > 0
                                        ? ($playlist['downloaded_songs'] / $playlist['total_songs']) * 100
                                        : 0;
                                    ?>
                                    <div class="progress-bar"
                                         role="progressbar"
                                         style="width: <?php echo $percentage; ?>%; background: var(--gradient);"
                                         aria-valuenow="<?php echo $percentage; ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small">
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                        <?php echo $playlist['downloaded_songs']; ?> downloaded
                                    </span>
                                    <span class="text-muted small">
                                        <i class="fas fa-clock text-warning me-1"></i>
                                        <?php echo $playlist['total_songs'] - $playlist['downloaded_songs']; ?> pending
                                    </span>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="?action=list&playlists=<?php echo urlencode($playlist['playlist']); ?>"
                                       class="btn btn-premium flex-grow-1">
                                        <i class="fas fa-eye me-2"></i>View Songs
                                    </a>
                                    <?php if ($playlist['playlist'] !== 'General'): ?>
                                        <button onclick="confirmDeletePlaylist(<?php echo htmlspecialchars(json_encode($playlist['playlist']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo $playlist['total_songs']; ?>)"
                                                class="btn btn-delete"
                                                title="Delete playlist">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-folder-plus fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No playlists yet</h4>
                    <p class="text-muted">Start adding songs to create playlists!</p>
                    <a href="?action=add" class="btn btn-premium mt-3">
                        <i class="fas fa-plus me-2"></i>Add First Song
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}
</style>

<script>
function confirmDeletePlaylist(playlistName, songCount) {
    const message = `Are you sure you want to delete the playlist "${playlistName}"?\n\nThis will:\n- Delete ${songCount} song(s) from the database\n- Remove all MP3 files from this playlist\n- Delete the playlist folder\n\nThis action cannot be undone!`;

    if(confirm(message)) {
        // Create and submit a hidden form with CSRF token
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?action=delete_playlist';

        const playlistInput = document.createElement('input');
        playlistInput.type = 'hidden';
        playlistInput.name = 'playlist';
        playlistInput.value = playlistName;
        form.appendChild(playlistInput);

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo Csrf::generateToken(); ?>';
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
