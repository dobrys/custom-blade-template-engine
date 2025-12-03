<?php

use App\SessionManager;

SessionManager::start();
SessionManager::logout();

header('Location: /');




