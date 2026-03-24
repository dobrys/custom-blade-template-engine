<?php

use App\SessionManager;
use App\LanguageDetector;
use App\Nav\NavBuilder;

require_once 'vendor/autoload.php';

// Environment
Dotenv\Dotenv::createImmutable(__DIR__)->load();
define('SK', $_ENV['JWT_SECRET']);

// Session
SessionManager::start();
$isLoggedIn = SessionManager::isLoggedIn();

// Config
$config    = require __DIR__ . '/config.php';
$languages = require __DIR__ . '/languages.php';

// Theme
if (isset($_GET['theme'])) {
    SessionManager::set('theme', $_GET['theme']);
}
$config['theme'] = SessionManager::get('theme') ?? 'default';

// Language & locale
$detector = new LanguageDetector($languages);
$lang     = $detector->getLanguage();
$locale   = $detector->getLocale();
$dir      = $detector->getDirection();
$fullUrl  = $detector->getFullUrl();

SessionManager::set('locale', $locale);

// App & Blade
$app   = new \App\App($config);
$blade = $app->blade;
$GLOBALS['blade'] = $blade;

// Nav — ще се обнови от Router::refreshNavAndAuth() след middleware
$nav = new NavBuilder(require __DIR__ . '/config/nav.php', $isLoggedIn, $fullUrl);

// Global blade variables
// Забележка: is_logged_in и nav се обновяват от Router след middleware изпълнение
$blade->assign('nav',            $nav->build());
$blade->assign('currentUrl',     $nav->getCurrentUrl());
$blade->assign('site_language',  $lang);
$blade->assign('text_direction', $dir);
$blade->assign('siteURL',        $fullUrl);
$blade->assign('is_logged_in',   $isLoggedIn);