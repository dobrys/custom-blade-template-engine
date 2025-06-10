<?php
require_once 'init.php';
require_once 'globals.php';
global $blade;
//var_dump($current_site);
$blade->assign('title', __('Home'));
$blade->assign('name', 'иван');
$blade->display('home');


