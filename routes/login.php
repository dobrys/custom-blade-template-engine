<?php

global $config;

use App\SessionManager;
use App\UserManager;


//var_dump($config);
global $blade;
//$umg = new App\UserManager();$new = $umg->createUser('dobrys','3aspal3aek','dobrys@abv.bg');var_dump($new);
$errors = [];



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (dummyValidate($username,$password)) {
        header('Location: /beauty-factor');
        exit;
    } else {
        $errors[] = __('Invalid username or password.');
    }
}
$blade->assign('title', __('Login'));
$blade->assign('errors', $errors);
$blade->display('pages.login');


