<?php

namespace App;

use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Filesystem\Filesystem;
use App\Contracts\TranslatorInterface;

class BladeEngine
{
    protected Factory $factory;
    protected array $data = [];

    public function __construct(
        TranslatorInterface $translator,
        string $viewsPath,
        string $cachePath
    )
    {
        $filesystem = new Filesystem();
        $bladeCompiler = new BladeCompiler($filesystem, $cachePath);
        $resolver = new EngineResolver();
        $resolver->register('blade', fn() => new CompilerEngine($bladeCompiler));
        $finder = new FileViewFinder($filesystem, [$viewsPath]);
        $this->factory = new Factory($resolver, $finder, new Dispatcher(new Container));

        // Set global translator
        $GLOBALS['translator'] = $translator;
    }

    public function assign(string $key, mixed $value): void
    {
        //var_dump($key,$value);
        $this->data[$key] = $value;
        //var_dump($this->data);
    }

    public function assignArray(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function render(string $template): string
    {
        return $this->factory->make($template, $this->data)->render();
    }

    public function display(string $template): void
    {
        echo $this->render($template);
    }
}
