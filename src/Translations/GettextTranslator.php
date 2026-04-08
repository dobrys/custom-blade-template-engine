<?php

namespace App\Translations;

use Gettext\Loader\MoLoader;
use Gettext\Loader\PoLoader;
use App\Contracts\TranslatorInterface;
class GettextTranslator implements TranslatorInterface
{
    private $translations;

    public function __construct(string $langPath, string $locale)
    {

        $this->translations = $this->loadFromLocale($langPath, $locale);

        // fallback към en
        if (!$this->translations && $locale !== 'en') {
            error_log('GettextTranslator::fallback към enfallback към "en" за '.$locale);
            $_SESSION['app_locale']    = 'en_US';
            $_SESSION['app_language']  = 'en';
            $this->translations = $this->loadFromLocale($langPath, 'en');
        }
    }

    private function loadFromLocale(string $langPath, string $locale)
    {
        $moFile = $langPath . "/{$locale}.mo";
        $poFile = $langPath . "/{$locale}.po";

        return $this->loadFile($moFile, $poFile);
    }

    private function loadFile(string $moFile, string $poFile)
    {
        if (file_exists($moFile)) {
            //error_log("GettextTranslator::using MO file: {$moFile}");
            return (new MoLoader())->loadFile($moFile);
        }

        if (file_exists($poFile)) {
            //error_log("GettextTranslator::using PO file: {$poFile}");
            return (new PoLoader())->loadFile($poFile);
        }

        return null;
    }

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        if (!$this->translations) {
            error_log("GettextTranslator::Translations not loaded. Returning key: {$key}");
            return $key;
        }

        $translation = $this->translations->find(null, $key);

        return ($translation && $translation->getTranslation() !== '')
            ? $translation->getTranslation()
            : $key;
    }
}