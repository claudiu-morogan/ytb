<?php
$showNotificationClass = 'none';

if($_POST && isset($_POST['link']))
{
    // Validate CSRF token
    Csrf::validateOrDie();

    $showNotificationClass = 'block';
    $db = new Song();
    $data = array(
        'link' => $_POST['link'],
        'playlist' => $_POST['playlist'] ?? 'General'
    );
    $status = $db->addSong($data);
    // Note: Song class already handles its own database connection
}

// Get existing playlists (after adding song to include newly created playlists)
$db = new DataBase();
$playlistsResult = $db->query("SELECT DISTINCT playlist FROM ytb_downloads ORDER BY playlist");
$existingPlaylists = [];
foreach($playlistsResult as $row) {
    $existingPlaylists[] = $row['playlist'];
}
?>

<div class="container">
    <div class="glass-container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <i class="fas fa-plus-circle fa-3x mb-3" style="color: var(--primary);"></i>
                <h1>Add New Song</h1>
                <p class="text-muted">Paste a YouTube link to download</p>
            </div>
        </div>

        <?php if(isset($status)): ?>
            <div class="alert alert-<?php echo array_key_first($status); ?> alert-premium" style="display: <?=$showNotificationClass?>">
                <i class="fas <?php echo array_key_first($status) == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                <strong><?php echo $status[array_key_first($status)]; ?></strong>
            </div>
        <?php endif; ?>

        <form action="<?=$_SERVER['REQUEST_URI']?>" method="POST" id="addSongForm">
            <?php echo Csrf::getTokenField(); ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="mb-4">
                        <label for="link" class="form-label">
                            <i class="fab fa-youtube me-2" style="color: #FF0000;"></i>
                            YouTube URL
                        </label>
                        <input type="text"
                               name="link"
                               id="link"
                               class="form-control form-control-premium"
                               placeholder="https://www.youtube.com/watch?v=..."
                               required
                               autofocus>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Paste a link to any YouTube video
                        </small>
                    </div>

                    <div class="mb-4">
                        <label for="playlist" class="form-label">
                            <i class="fas fa-folder me-2" style="color: var(--primary);"></i>
                            Playlist
                            <?php if(count($existingPlaylists) > 0): ?>
                                <span class="badge bg-info ms-2"><?php echo count($existingPlaylists); ?> existing</span>
                            <?php endif; ?>
                        </label>

                        <?php if(count($existingPlaylists) > 0): ?>
                            <!-- Quick select buttons for existing playlists -->
                            <div class="mb-2 d-flex flex-wrap gap-2" id="playlistQuickSelect">
                                <?php foreach($existingPlaylists as $pl): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary playlist-btn"
                                            data-playlist="<?php echo htmlspecialchars($pl, ENT_QUOTES, 'UTF-8'); ?>"
                                            onclick="selectPlaylist('<?php echo htmlspecialchars($pl, ENT_QUOTES, 'UTF-8'); ?>')">
                                        <i class="fas fa-folder me-1"></i>
                                        <?php echo htmlspecialchars($pl, ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="input-group">
                            <input type="text"
                                   name="playlist"
                                   id="playlist"
                                   class="form-control form-control-premium"
                                   placeholder="Type new playlist name"
                                   value="<?php echo isset($_POST['playlist']) ? htmlspecialchars($_POST['playlist'], ENT_QUOTES, 'UTF-8') : 'General'; ?>"
                                   list="playlistOptions">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearPlaylist()" title="Clear and create new">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <datalist id="playlistOptions">
                            <?php foreach($existingPlaylists as $pl): ?>
                                <option value="<?php echo htmlspecialchars($pl, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            Click a playlist above to select, or type a new name to create one
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <button type="submit" class="btn btn-premium w-100">
                                <i class="fas fa-plus me-2"></i>Add to Queue
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="?action=list" class="btn btn-outline-secondary w-100" style="border-radius: 50px; padding: 12px 30px;">
                                <i class="fas fa-list me-2"></i>View List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="row mt-5">
            <div class="col-md-4 text-center mb-3">
                <div class="p-4">
                    <i class="fas fa-paste fa-2x mb-3" style="color: var(--primary);"></i>
                    <h5>1. Paste Link</h5>
                    <p class="text-muted small">Copy a YouTube URL and paste it above</p>
                </div>
            </div>
            <div class="col-md-4 text-center mb-3">
                <div class="p-4">
                    <i class="fas fa-download fa-2x mb-3" style="color: var(--success);"></i>
                    <h5>2. Download</h5>
                    <p class="text-muted small">Click download to process your queue</p>
                </div>
            </div>
            <div class="col-md-4 text-center mb-3">
                <div class="p-4">
                    <i class="fas fa-music fa-2x mb-3" style="color: var(--secondary);"></i>
                    <h5>3. Enjoy</h5>
                    <p class="text-muted small">Your MP3 will be ready in seconds</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addSongForm').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Adding...';
    submitBtn.disabled = true;
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert-premium');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    });
}, 5000);

// Playlist selection functions
function selectPlaylist(playlistName) {
    const input = document.getElementById('playlist');
    input.value = playlistName;

    // Update active state of buttons
    document.querySelectorAll('.playlist-btn').forEach(btn => {
        if (btn.dataset.playlist === playlistName) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        }
    });

    // Focus on link input for better UX
    document.getElementById('link').focus();
}

function clearPlaylist() {
    const input = document.getElementById('playlist');
    input.value = '';
    input.focus();

    // Reset all buttons to outline
    document.querySelectorAll('.playlist-btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
    });
}

// Initialize the selected playlist button on page load
document.addEventListener('DOMContentLoaded', function() {
    const currentValue = document.getElementById('playlist').value;
    if (currentValue) {
        document.querySelectorAll('.playlist-btn').forEach(btn => {
            if (btn.dataset.playlist === currentValue) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary');
            }
        });
    }
});
</script>

<style>
.playlist-btn {
    border-radius: 20px;
    transition: all 0.3s ease;
}

.playlist-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.playlist-btn.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
}
</style>
