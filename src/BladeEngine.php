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
        string $cachePath,
        string $theme = 'default'
    )
    {
        $filesystem = new Filesystem();
        $bladeCompiler = new BladeCompiler($filesystem, $cachePath);
        $bladeCompiler->directive('dump', function ($expression) {
            return "<?php \\Symfony\\Component\\VarDumper\\VarDumper::dump($expression); ?>";
        });
        $bladeCompiler->directive('dd', function ($expression) {
            return "<?php \\Symfony\\Component\\VarDumper\\VarDumper::dump($expression); die(); ?>";
        });
        $bladeCompiler->directive('asset', function ($expression) {
            return "<?php echo asset($expression); ?>";
        });
        $bladeCompiler->directive('themeAsset', function ($expression) {
            return "<?php echo theme_asset($expression); ?>";
        });



        $resolver = new EngineResolver();
        $resolver->register('blade', fn() => new CompilerEngine($bladeCompiler));

        $paths = [
            __DIR__ . "/../themes/{$theme}",
            $viewsPath
        ];

        $finder = new FileViewFinder($filesystem, $paths);
        //die(var_dump($finder));
        $this->factory = new Factory($resolver, $finder, new Dispatcher(new Container));

        // Set global translator
        $GLOBALS['translator'] = $translator;
        $GLOBALS['current_theme'] = $theme;

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
