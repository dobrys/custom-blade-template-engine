<?php
return [
    'env' => 'development', // 'production'
    'lang_dir' => __DIR__ . '/lang',
    'views_dir' => __DIR__ . '/views',
    'cache_dir' => __DIR__ . '/cache',
    'theme' => 'default',
    'translator' => 'gettext', //laravel или 'gettext'

    // Къде да пращаме след успешен логин
    'redirect_success' => '/',

    // Къде при грешка (ако не е AJAX)
    'redirect_failure' => '/login',
    'special_uri'=>['/login','/logout','/signin'],
    'next_uri_var'=>'next_page'
];