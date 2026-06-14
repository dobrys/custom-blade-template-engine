<?php
global $blade;

$redirect_reason = \App\SessionManager::get('redirect_reason');
\App\SessionManager::clear('redirect_reason');

$blade->assign('title', __('Login'));
$blade->assign('redirect_reason', $redirect_reason);
$blade->display('pages.mobile-login');


