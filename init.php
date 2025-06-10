<?php
require_once 'vendor/autoload.php';

use App\Translations\LaravelTranslator;
use App\Translations\GettextTranslator;
use App\BladeEngine;

require_once 'src/helpers/translator.php';
session_start();
$_SESSION['locale'] = 'bg';


$locale = $_SESSION['locale'] ?? 'en';
$type = 'gettext'; //'gettext' или 'laravel'

// Създаване на съответния преводач
try {
    $translator = $type === 'laravel'
        ? new LaravelTranslator(__DIR__ . '/lang', $locale)
        : new GettextTranslator(__DIR__ . '/lang', $locale);
    // Blade инстанция
    $blade = new BladeEngine($translator, __DIR__ . '/views', __DIR__ . '/cache');
    $GLOBALS['blade'] = $blade;

} catch (Exception $e) {
    echo $e->getMessage();
}