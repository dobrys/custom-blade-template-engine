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
    public function renderString(string $string, array $data = []): string
    {
        $bladeCompiler = new \Illuminate\View\Compilers\BladeCompiler(
            new \Illuminate\Filesystem\Filesystem(),
            sys_get_temp_dir()
        );

        // Комбинираме глобални и локални данни
        $data = array_merge($this->data, $data);

        // Автоматично добавяме $ преди имена на променливи вътре в {{ }}
        // Пример: {{ company_name }} → {{ $company_name }}
        $string = preg_replace('/\{\{\s*(?!\$)([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', '{{ \$$1 }}', $string);

        // Компилираме Blade стринга в PHP код
        $php = $bladeCompiler->compileString($string);

        // Изпълняваме PHP кода в затворен scope с подадените данни
        ob_start();
        extract($data, EXTR_SKIP);
        try {
            eval('?>' . $php);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    public function display(string $template): void
    {
        echo $this->render($template);
    }
}
