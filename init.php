<?php
use App\SessionManager;

require_once 'vendor/autoload.php';


\App\SessionManager::start();
$isLoggedIn = SessionManager::isLoggedIn();
$config = require_once 'config.php';
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

$theme_to_use = $_SESSION['theme'] ?? 'default';
$config['theme'] = $theme_to_use;

$app = new \App\App($config);

$GLOBALS['blade'] = $app->blade;