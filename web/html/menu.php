<?php
    $urls = [
        'list' => 'http://'.$_SERVER['HTTP_HOST'].'?action=list',
        'add' =>'http://'.$_SERVER['HTTP_HOST'].'?action=add',
        'download' =>'http://'.$_SERVER['HTTP_HOST'].'?action=download'
    ];
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark" style="margin-bottom: 10px">
    <div class="container">
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto mr-auto">
                <?php foreach($urls as $menuOption => $url): ?>
                    <li class="nav-item">
                        <a href="<?=$url?>" class="nav-link"><?=ucfirst($menuOption)?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>