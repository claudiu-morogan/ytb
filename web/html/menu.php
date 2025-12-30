<?php
    $currentAction = $_GET['action'] ?? 'list';
    $urls = [
        'list' => ['url' => '?action=list', 'icon' => 'fa-list', 'label' => 'All Songs'],
        'playlists' => ['url' => '?action=playlists', 'icon' => 'fa-folder-open', 'label' => 'Playlists'],
        'add' => ['url' => '?action=add', 'icon' => 'fa-plus-circle', 'label' => 'Add Song'],
        'download' => ['url' => '?action=download', 'icon' => 'fa-download', 'label' => 'Download']
    ];
?>

<div class="container">
    <nav class="nav-premium d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h3 class="mb-0 me-4" style="background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                <i class="fab fa-youtube me-2"></i>YTB Downloader
            </h3>
        </div>
        <div class="d-flex">
            <?php foreach($urls as $key => $item): ?>
                <a href="<?=$item['url']?>" class="<?= $currentAction == $key ? 'active' : '' ?>">
                    <i class="fas <?=$item['icon']?> me-2"></i><?=$item['label']?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
</div>
