<?php
// config/site_vars.php
//
// Locale-keyed конфигурационни стойности за @sitevar('key') / site_var('key').
// Ключовете отговарят на 'app_language' кодовете от languages.php (en, bg, de, ...).
// 'default' се ползва като fallback, ако текущият език няма собствен запис
// или конкретният ключ липсва за него.

return [

    'default' => [
        'phone' => '',
        'email' => '',
    ],

    // 'bg' => [
    //     'phone' => '+359...',
    //     'email' => 'info@example.bg',
    // ],

];
