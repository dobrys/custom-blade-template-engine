<?php
return [
    'lang_dir' => __DIR__ . '/lang',
    'views_dir' => __DIR__ . '/views',
    'cache_dir' => __DIR__ . '/cache',
    'theme' => 'default',
    'translator' => 'gettext', //laravel или 'gettext'
    // Кой клас обработва логина
    'handler' => 'null',

    // Къде да пращаме след успешен логин
    'redirect_success' => '/',

    // Къде при грешка (ако не е AJAX)
    'redirect_failure' => '/login',
];