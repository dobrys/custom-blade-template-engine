<?php
use App\SessionManager;
use App\LanguageDetector;

require_once 'vendor/autoload.php';


\App\SessionManager::start();
$isLoggedIn = SessionManager::isLoggedIn();
$config = require_once 'config.php';
$languages = require __DIR__ . '/languages.php';
$detector = new LanguageDetector($languages);

$host = $detector->getHost();
$subDomain = $detector->getSubdomain();
$lang = $detector->getLanguage();
$locale = $detector->getLocale();
$protocol = $detector->getProtocol();
$fullUrl = $detector->getFullUrl();
$dir = $detector->getDirection();
$isValidLanguage = $detector->isValidLanguage($lang);
$_SESSION['locale'] =$locale;
//dump($locale,$dir ,$lang);
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

$theme_to_use = $_SESSION['theme'] ?? 'default';
$config['theme'] = $theme_to_use;

$app = new \App\App($config);

$GLOBALS['blade'] = $app->blade;