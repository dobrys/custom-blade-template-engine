<?php

global $config;




//var_dump($config);
global $blade;
//$umg = new App\UserManager();$new = $umg->createUser('dobrys','3aspal3aek','dobrys@abv.bg');var_dump($new);
$errors = [];
//dump($config);
$handlerClass = $config["handler"];
$redirect_success = $config["redirect_success"];
$lhClass = $config["class"];
$loginHandler = new $handlerClass;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credentials = [];

    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        $credentials['username'] = trim($_POST['username']);
        $credentials['password'] = $_POST['password'];
    } elseif (!empty($_POST['phone'])) {
        $credentials['phone'] = trim($_POST['phone']);
    }

    if ($loginHandler->attempt($credentials)) {
        header('Location: '.$redirect_success);
        exit;
    } else {
        $errors[] = 'Invalid credentials.';
    }
}


$blade->assign('title', __('Login'));
$blade->assign('errors', $errors);
$blade->display('pages.login');


