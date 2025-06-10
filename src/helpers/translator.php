<?php

use App\Contracts\TranslatorInterface;

if (!function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null)
    {
        global $translator;
        if (!isset($translator) || !$translator instanceof TranslatorInterface) {
            return $key;
        }
        return $translator->translate($key, $replace, $locale);
    }
}
