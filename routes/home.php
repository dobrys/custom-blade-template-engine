<?php
global $blade;
//die(var_dump($blade));
$blade->assign('title', __('home.title'));


$blade->display('pages.home');
