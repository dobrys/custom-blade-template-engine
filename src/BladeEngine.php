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
use Symfony\Component\VarDumper;
use App\Contracts\TranslatorInterface;

class BladeEngine
{
    protected Factory $factory;
    protected array $data = [];


    public function __construct(
        TranslatorInterface $translator,
        string $viewsPath,
        string $cachePath,
                            $assetBase = '/assets'
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
        var_dump($assetBase);
        $bladeCompiler->directive('asset', function ($expression) use ($assetBase) {
            return "<?php echo '{$assetBase}/' . ltrim(trim($expression, \"'\\\"\"), '/'); ?>";
        });

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
