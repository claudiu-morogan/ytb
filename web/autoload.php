<?php
require 'cls/Env.php';
require 'cls/Song.php';
require 'cls/Csrf.php';

// $env = new DotEnvironment(__DIR__.'/../.env');
$env = new DotEnvironment(__DIR__.'/.env');
$env->load();

// Start session for CSRF protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'html/head.php';
require 'html/menu.php';
require 'html/body.php';
require 'html/footer.php';

?>