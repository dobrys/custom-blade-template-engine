<?php
$host = $_SERVER['HTTP_HOST'];
$protocol='https://';
$current_site='blade-smarty-style.test';

$our = array(
    'dailyfit24.test',
    'bg.dailyfit24.test',
    'ro.dailyfit24.test',
    'ch.dailyfit24.test',
    'si.dailyfit24.test',
    'fr.dailyfit24.test',

    'dailyfit24.com',
    'bg.dailyfit24.com',
    'ro.dailyfit24.com',
    'ch.dailyfit24.com',
    'si.dailyfit24.com',
    'fr.dailyfit24.com'
);

$scriptName = $_SERVER["SCRIPT_NAME"];
$basePath = dirname($scriptName,1);

const SK = 'very_very_secret_key';
if (in_array($host, $our)) {

    $bp='/dailyfit';
    if ($basePath==='/' || $basePath==='\\'){
        $bp='';
    }

    $current_site=$protocol.$host;
}