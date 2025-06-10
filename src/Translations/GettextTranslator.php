<?php

namespace App\Translations;

use Exception;
use Gettext\Loader\MoLoader;
use Gettext\Loader\PoLoader;
use App\Contracts\TranslatorInterface;

class GettextTranslator implements TranslatorInterface
{
    private $translations;

    /**
     * @throws Exception
     */
    public function __construct(string $langPath, string $locale)
    {
        $moFile = $langPath . "/{$locale}.mo";
        $poFile = $langPath . "/{$locale}.po";

        if (file_exists($moFile)) {
            $loader = new MoLoader();
            $this->translations = $loader->loadFile($moFile);
        } elseif (file_exists($poFile)) {
            $loader = new PoLoader();
            $this->translations = $loader->loadFile($poFile);
        } else {
            throw new Exception("No translation file found for language '{$locale}' in {$langPath}");
        }
    }

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $translation = $this->translations->find(null, $key);
        return ($translation && $translation->getTranslation() !== '') ? $translation->getTranslation() : $key;
    }
}
