<?php

namespace App\Contracts;

interface TranslatorInterface
{
    public function translate(string $key, array $replace = [], ?string $locale = null): string;
}