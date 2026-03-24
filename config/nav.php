<?php

return [
    [
        'label' => 'Home',
        'url'   => '/',
        'icon'  => 'bi bi-house',
        'auth'  => false,
    ],
    [
        'label' => 'Content',
        'url'   => '#',
        'icon'  => 'bi bi-collection',
        'auth'  => false,
        'children' => [
            ['label' => 'Articles', 'url' => '/single', 'icon' => 'bi bi-newspaper'],
            ['label' => 'Terms',    'url' => '/terms',  'icon' => 'bi bi-file-text'],
        ],
    ],
    [
        'label' => 'Profile',
        'url'   => '/profile',
        'icon'  => 'bi bi-person',
        'auth'  => true,  // само за логнати
    ],
    [
        'label' => 'Account',
        'url'   => '#',
        'icon'  => 'bi bi-gear',
        'auth'  => true,
        'children' => [
            ['label' => 'Settings', 'url' => '/settings', 'icon' => 'bi bi-sliders'],
            ['label' => 'Billing',  'url' => '/billing',  'icon' => 'bi bi-credit-card', 'badge' => 'New'],
        ],
    ],
];