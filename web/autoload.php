<?php
require 'cls/Env.php';
require 'cls/Song.php';

$env = new DotEnvironment(__DIR__.'/../.env');
$env->load();

require 'html/head.php';
require 'html/menu.php';
require 'html/body.php';
require 'html/footer.php';

?>