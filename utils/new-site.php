<?php
/**
 * utils/new-site.php
 *
 * Детерминистичен scaffolder: създава НОВ сайт, базиран на този engine.
 * Без AI, без външни зависимости (чист PHP + SPL — работи и преди
 * `composer install`).
 *
 * Какво прави:
 *   - Копира engine скелета в целева директория.
 *   - Изключва тайните и локалното (.env, vendor/, node_modules/, .git/,
 *     .idea/, .claude/, компилиран cache/*.php, dev-документи).
 *   - Генерира свеж .env от .env.example с нов случаен JWT_SECRET.
 *   - Настройва config.php (theme, env) по подадените опции.
 *   - Добавя .env в .gitignore на новия сайт (за да не изтекат тайни).
 *
 * Употреба:
 *   php utils/new-site.php <целева-папка> [опции]
 *
 * Опции:
 *   --name="Име на сайта"   Записва се в README на новия сайт.
 *   --theme=default          Активна тема (default config.php стойност).
 *   --env=development         development | production (config.php + .env).
 *   --force                   Презаписва, дори целевата папка да не е празна.
 *   --help                    Показва тази помощ.
 *
 * Пример:
 *   php utils/new-site.php ../my-new-site --name="Моят сайт" --env=production
 */

// ---------------------------------------------------------------------------
// 0. Само от CLI
// ---------------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Този скрипт се пуска само от командния ред.\n");
}

// ---------------------------------------------------------------------------
// 1. Какво да НЕ се копира
// ---------------------------------------------------------------------------

// Папки (по име на най-горния сегмент), които никога не се копират.
const EXCLUDE_DIRS = [
    '.git',
    '.idea',
    '.claude',
    'vendor',        // възстановява се с `composer install`
    'node_modules',  // възстановява се с `npm install`
    'docs',          // dev-документи на самия engine
];

// Конкретни файлове (относителен път спрямо корена), които не се копират.
// ЗАБЕЛЕЖКА: lock файловете (composer.lock / package-lock.json) НАРОЧНО се
// копират — така всеки производен сайт тръгва от точно тестваните версии
// (възпроизводима база). Махни ги тук само ако искаш fresh resolve.
const EXCLUDE_FILES = [
    '.env',                          // тайни → генерира се наново
    'claude-code-instructions.md',   // dev-бележки на engine-а
];

// ---------------------------------------------------------------------------
// 2. Помощни функции
// ---------------------------------------------------------------------------

/** Нормализира пътя към forward-slash за сравнения (Windows-safe). */
function normalize(string $path): string
{
    return str_replace('\\', '/', $path);
}

function printHelp(): void
{
    $doc = file_get_contents(__FILE__);
    // Извеждаме само водещия docblock.
    if (preg_match('#/\*\*(.*?)\*/#s', $doc, $m)) {
        $lines = preg_split('/\R/', $m[1]);
        foreach ($lines as $line) {
            echo preg_replace('/^\s*\*?\s?/', '', $line) . "\n";
        }
    }
}

/**
 * Прости CLI аргументи: първи позиционен = целева папка, останалото --key=value
 * или --flag.
 */
function parseArgs(array $argv): array
{
    $opts = [
        'target' => null,
        'name'   => null,
        'theme'  => 'default',
        'env'    => 'development',
        'force'  => false,
        'help'   => false,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $opts['help'] = true;
        } elseif ($arg === '--force') {
            $opts['force'] = true;
        } elseif (str_starts_with($arg, '--')) {
            [$key, $val] = array_pad(explode('=', substr($arg, 2), 2), 2, '');
            if (array_key_exists($key, $opts)) {
                $opts[$key] = $val;
            } else {
                fwrite(STDERR, "⚠️  Непозната опция: --{$key}\n");
            }
        } elseif ($opts['target'] === null) {
            $opts['target'] = $arg;
        }
    }

    return $opts;
}

/** Прекратява с грешка. */
function fail(string $msg): never
{
    fwrite(STDERR, "❌ {$msg}\n");
    exit(1);
}

/** Дали даден относителен път да се прескочи. */
function shouldSkip(string $relative): bool
{
    $relative = normalize($relative);
    $top = explode('/', $relative)[0];

    if (in_array($top, EXCLUDE_DIRS, true)) {
        return true;
    }
    if (in_array($relative, EXCLUDE_FILES, true)) {
        return true;
    }
    // Компилираните Blade шаблони в cache/ — пропускаме, но запазваме самата
    // папка и нейния .gitignore.
    if (str_starts_with($relative, 'cache/') && str_ends_with($relative, '.php')) {
        return true;
    }

    return false;
}

/** Рекурсивно копира $source → $target, спазвайки shouldSkip(). */
function copyTree(string $source, string $target): int
{
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        if (shouldSkip($relative)) {
            continue;
        }

        $dest = $target . DIRECTORY_SEPARATOR . $relative;

        if ($item->isDir()) {
            if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
                fail("Не мога да създам папка: {$dest}");
            }
        } else {
            $destDir = dirname($dest);
            if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                fail("Не мога да създам папка: {$destDir}");
            }
            if (!copy($item->getPathname(), $dest)) {
                fail("Не мога да копирам: {$relative}");
            }
            $count++;
        }
    }

    return $count;
}

/** Дали папката не съществува или е празна. */
function isEmptyDir(string $dir): bool
{
    if (!is_dir($dir)) {
        return true;
    }
    foreach (new FilesystemIterator($dir) as $_) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------------
// 3. Главна логика
// ---------------------------------------------------------------------------

$opts = parseArgs($argv);

if ($opts['help']) {
    printHelp();
    exit(0);
}

$sourceRoot = realpath(dirname(__DIR__));
if ($sourceRoot === false) {
    fail('Не мога да определя корена на engine-а.');
}

if (empty($opts['target'])) {
    fwrite(STDERR, "❌ Липсва целева папка.\n\n");
    printHelp();
    exit(1);
}

if (!in_array($opts['env'], ['development', 'production'], true)) {
    fail("--env трябва да е 'development' или 'production' (подадено: {$opts['env']}).");
}

// Целевият път (може и да не съществува още).
$targetRoot = $opts['target'];
if (!preg_match('#^([a-zA-Z]:[\\\\/]|[\\\\/])#', $targetRoot)) {
    // Относителен път → спрямо текущата работна директория.
    $targetRoot = getcwd() . DIRECTORY_SEPARATOR . $targetRoot;
}
$targetRootNorm = normalize($targetRoot);
$sourceRootNorm = normalize($sourceRoot);

// Защити: цел вътре в източника или източник вътре в целта → рекурсия/каша.
if (str_starts_with($targetRootNorm . '/', $sourceRootNorm . '/')
    || str_starts_with($sourceRootNorm . '/', $targetRootNorm . '/')) {
    fail('Целевата папка не може да съвпада/да е вътре в engine папката (и обратно).');
}

if (!$opts['force'] && !isEmptyDir($targetRoot)) {
    fail("Целевата папка не е празна: {$targetRoot}\n   Използвай --force, за да продължиш.");
}

// Предупреждение, ако избраната тема липсва.
if (!is_dir($sourceRoot . '/themes/' . $opts['theme'])) {
    fwrite(STDERR, "⚠️  Тема '{$opts['theme']}' липсва в themes/ — задавам я в config.php все пак.\n");
}

echo "🚀 Създавам нов сайт от engine-а...\n";
echo "   Източник: {$sourceRoot}\n";
echo "   Цел:      {$targetRoot}\n";
echo "   Тема:     {$opts['theme']}   Среда: {$opts['env']}\n\n";

if (!is_dir($targetRoot) && !mkdir($targetRoot, 0755, true) && !is_dir($targetRoot)) {
    fail("Не мога да създам целевата папка: {$targetRoot}");
}

// 3.1 Копиране на скелета
$copied = copyTree($sourceRoot, $targetRoot);
echo "✅ Копирани {$copied} файла.\n";

// 3.2 Генериране на свеж .env от .env.example
$envExample = $sourceRoot . '/.env.example';
if (!is_file($envExample)) {
    fail('Липсва .env.example — не мога да генерирам .env.');
}
$env = file_get_contents($envExample);

$secret = base64_encode(random_bytes(32));
$debug  = $opts['env'] === 'production' ? 'false' : 'true';

$env = preg_replace('/^JWT_SECRET=.*$/m', 'JWT_SECRET=' . $secret, $env);
$env = preg_replace('/^APP_ENV=.*$/m', 'APP_ENV=' . $opts['env'], $env);
$env = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG=' . $debug, $env);

file_put_contents($targetRoot . '/.env', $env);
echo "✅ Генериран .env с нов JWT_SECRET.\n";

// 3.3 Настройка на config.php (theme, env)
$configFile = $targetRoot . '/config.php';
if (is_file($configFile)) {
    $config = file_get_contents($configFile);
    $config = preg_replace(
        "/('env'\s*=>\s*')[^']*(')/",
        '${1}' . $opts['env'] . '$2',
        $config,
        1
    );
    $config = preg_replace(
        "/('theme'\s*=>\s*')[^']*(')/",
        '${1}' . $opts['theme'] . '$2',
        $config,
        1
    );
    file_put_contents($configFile, $config);
    echo "✅ Настроен config.php (theme={$opts['theme']}, env={$opts['env']}).\n";
}

// 3.4 Подсигуряваме, че .env е в .gitignore на новия сайт
$gitignore = $targetRoot . '/.gitignore';
$ignore = is_file($gitignore) ? file_get_contents($gitignore) : '';
if (!preg_match('/^\.env\s*$/m', $ignore)) {
    $ignore = rtrim($ignore) . "\n.env\n";
    file_put_contents($gitignore, $ignore);
    echo "✅ Добавен '.env' в .gitignore.\n";
}

// 3.5 README с името на сайта (по желание)
if (!empty($opts['name'])) {
    file_put_contents(
        $targetRoot . '/README.md',
        "# {$opts['name']}\n\nСайт, базиран на blade_custom engine.\n"
    );
    echo "✅ Записан README.md за '{$opts['name']}'.\n";
}

// ---------------------------------------------------------------------------
// 4. Следващи стъпки
// ---------------------------------------------------------------------------
echo "\n🎉 Готово! Следващи стъпки:\n";
echo "   cd " . $targetRoot . "\n";
echo "   composer install\n";
echo "   npm install\n";
echo "   # попълни DB_* и API_KEY в .env\n";
echo "   # после: npm run build  (или npm run dev)\n";

exit(0);
