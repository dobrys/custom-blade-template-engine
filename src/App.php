<?php

namespace App;

use App\Contracts\TranslatorInterface;
require_once  __DIR__ . '/../src/helpers/functions.php';
require_once  __DIR__ . '/../src/helpers/translator.php';
class App {
    public BladeEngine $blade;
    public TranslatorInterface $translator;
    public string $locale;

    public function __construct(array $config) {
        $this->locale = $_SESSION['app_locale'] ?? 'en_US';

        $translatorClass = $config['translator'] === 'gettext'
            ? \App\Translations\GettextTranslator::class
            : \App\Translations\LaravelTranslator::class;

$this->translator = new $translatorClass($config['lang_dir'], $this->locale);
#$this->translator = new $translatorClass($config['lang_dir'], 'bg_BG');
        //die(var_dump($this->translator ));
        $theme = $config['theme'] ?? 'default';

        $this->blade = new \App\BladeEngine(
            $this->translator,
            $config['views_dir'],
            $config['cache_dir'],
            $theme
        );
        //var_dump($this->translator);
    }
}
