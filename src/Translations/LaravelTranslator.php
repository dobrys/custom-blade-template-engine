<?php

namespace App\Translations;

use Exception;
use Illuminate\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use App\Contracts\TranslatorInterface;

class LaravelTranslator implements TranslatorInterface
{
    protected Translator $translator;

    /**
     * @throws Exception
     */
    public function __construct(string $langPath, string $locale)
    {
        //var_dump($langPath);
        if (file_exists($langPath)) {
            $loader = new FileLoader(new Filesystem(), $langPath);
            $this->translator = new Translator($loader, $locale);
        }else{
            throw new Exception("No translation file found for language '{$locale}' in {$langPath}");
        }


    }

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->translator->get($key, $replace, $locale);
    }
}
