<?php
/**
 * utils/theme-import.php
 *
 * Механичен (детерминистичен) препроцесор: превръща свалена СТАТИЧНА HTML тема
 * (напр. от ThemeForest) в скеле на engine тема под themes/{name}/.
 *
 * Това е САМО механичният слой (~60-70%). Той НЕ разбира семантика:
 *   - не превръща статичното меню в нашия $nav цикъл,
 *   - не обвива текстове в __() за i18n,
 *   - не дедупликира умно повтарящи се блокове.
 * Тези стъпки остават за "finishing pass" (човек или AI чрез Claude Code) —
 * виж генерирания REVIEW.md.
 *
 * Какво ПРАВИ надеждно:
 *   - Копира asset папките (css/js/img/fonts/...) в themes/{name}/assets/.
 *   - С DOMDocument локализира <head>/<header>/<footer>/<main>.
 *   - Сглобява layout/default.blade.php + partials/header|footer.blade.php.
 *   - Всяка .html страница → pages/{name}.blade.php (@extends + @section).
 *   - Пренаписва локалните asset URL-и → @themeAsset('...').
 *   - <title> → @yield('title', '...').
 *   - Изнася общите <script> от началната страница в layout-а.
 *   - Пише REVIEW.md с обобщение и TODO за finishing pass.
 *
 * Употреба:
 *   php utils/theme-import.php <папка-със-сваляната-тема> <име-на-темата> [--force]
 *
 * Пример:
 *   php utils/theme-import.php ../downloads/cooltheme cooltheme
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Този скрипт се пуска само от командния ред.\n");
}

// Папки, които приемаме за asset-и и копираме както са (запазваме структурата).
const ASSET_DIRS = [
    'css', 'js', 'img', 'images', 'image', 'fonts', 'font',
    'assets', 'scss', 'vendor', 'vendors', 'plugins', 'lib', 'media', 'video',
];

// Разширения, които третираме като asset-и (за разлика от навигационни линкове).
const ASSET_EXT = [
    'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp',
    'woff', 'woff2', 'ttf', 'eot', 'otf', 'mp4', 'webm', 'ogg', 'mp3', 'wav',
    'pdf', 'map', 'json',
];

// ---------------------------------------------------------------------------
// Помощни
// ---------------------------------------------------------------------------

function normalize(string $path): string
{
    return str_replace('\\', '/', $path);
}

function fail(string $msg): never
{
    fwrite(STDERR, "❌ {$msg}\n");
    exit(1);
}

function printHelp(): void
{
    if (preg_match('#/\*\*(.*?)\*/#s', file_get_contents(__FILE__), $m)) {
        foreach (preg_split('/\R/', $m[1]) as $line) {
            echo preg_replace('/^\s*\*?\s?/', '', $line) . "\n";
        }
    }
}

function parseArgs(array $argv): array
{
    $opts = ['source' => null, 'name' => null, 'force' => false, 'help' => false];
    $positional = [];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $opts['help'] = true;
        } elseif ($arg === '--force') {
            $opts['force'] = true;
        } elseif (str_starts_with($arg, '--')) {
            fwrite(STDERR, "⚠️  Непозната опция: {$arg}\n");
        } else {
            $positional[] = $arg;
        }
    }

    $opts['source'] = $positional[0] ?? null;
    $opts['name']   = $positional[1] ?? null;

    return $opts;
}

/** Абсолютизира относителен път спрямо CWD (Windows-safe). */
function absPath(string $path): string
{
    if (preg_match('#^([a-zA-Z]:[\\\\/]|[\\\\/])#', $path)) {
        return $path;
    }
    return getcwd() . DIRECTORY_SEPARATOR . $path;
}

/** Зарежда HTML в DOMDocument с коректно UTF-8 третиране. */
function loadDoc(string $html): DOMDocument
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    // Префиксът форсира UTF-8 интерпретация без deprecated mb HTML-ENTITIES.
    $doc->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();

    // Махаме изкуствено добавения XML processing instruction.
    foreach (iterator_to_array($doc->childNodes) as $child) {
        if ($child->nodeType === XML_PI_NODE) {
            $doc->removeChild($child);
        }
    }

    return $doc;
}

/** Вътрешен HTML на възел (без външния таг). */
function innerHtml(DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }
    return $html;
}

/** Външен HTML на възел (с тага). */
function outerHtml(DOMNode $node): string
{
    return $node->ownerDocument->saveHTML($node);
}

/** Дали URL сочи навън (да не се пипа). */
function isExternalUrl(string $url): bool
{
    return (bool) preg_match('#^(https?:)?//#i', $url)
        || str_starts_with($url, '#')
        || str_starts_with($url, 'data:')
        || str_starts_with($url, 'mailto:')
        || str_starts_with($url, 'tel:')
        || str_starts_with($url, 'javascript:')
        || trim($url) === '';
}

/** Нормализира локален asset път спрямо папката на HTML файла. */
function normalizeAssetPath(string $ref, string $htmlRelDir): string
{
    $ref = preg_replace('/[?#].*$/', '', $ref); // махаме query/anchor
    $ref = ltrim($ref, '/');
    $combined = $htmlRelDir === '' ? $ref : $htmlRelDir . '/' . $ref;

    // Резолвиране на ./ и ../
    $parts = [];
    foreach (explode('/', $combined) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $seg;
    }

    return implode('/', $parts);
}

/** Превръща линк към статична .html страница в engine рут (index.html → /). */
function htmlToRoute(string $url): string
{
    if (!preg_match('/^([^?#]*)([?#].*)?$/', $url, $m)) {
        return $url;
    }
    $base = strtolower(pathinfo($m[1], PATHINFO_FILENAME));
    $suffix = $m[2] ?? '';
    if ($base === '') {
        return $url;
    }
    return ($base === 'index' ? '/' : '/' . $base) . $suffix;
}

/**
 * Пренаписва локалните препратки в HTML низ.
 *   - src/poster/data-src и href към asset разширение → @themeAsset('...')
 *   - href към .html страница → engine рут (навигация)
 *   - котви / external / директории → остават
 * Покрива и CSS url(...). $count брои само asset пренаписванията.
 */
function rewriteAssets(string $html, string $htmlRelDir, int &$count = 0): string
{
    $toAsset = function (string $attr, string $url) use ($htmlRelDir, &$count) {
        $count++;
        return $attr . '="@themeAsset(\'' . normalizeAssetPath($url, $htmlRelDir) . '\')"';
    };

    $html = preg_replace_callback(
        '/\b(src|href|poster|data-src)\s*=\s*(["\'])(.*?)\2/i',
        function ($m) use ($htmlRelDir, $toAsset, &$count) {
            $attr = strtolower($m[1]);
            $url  = $m[3];
            if (isExternalUrl($url)) {
                return $m[0];
            }
            $ext = strtolower(pathinfo(preg_replace('/[?#].*$/', '', $url), PATHINFO_EXTENSION));

            if ($attr === 'href') {
                if (in_array($ext, ASSET_EXT, true)) {
                    return $toAsset($m[1], $url);
                }
                if ($ext === 'html') {
                    return $m[1] . '="' . htmlToRoute($url) . '"';
                }
                return $m[0]; // котва / без разширение / директория → навигация
            }

            return $toAsset($m[1], $url); // src / poster / data-src
        },
        $html
    );

    // CSS url(...) в inline <style> и style="".
    $html = preg_replace_callback(
        '/url\(\s*([\'"]?)(.*?)\1\s*\)/i',
        function ($m) use ($htmlRelDir, &$count) {
            $url = $m[2];
            if (isExternalUrl($url)) {
                return $m[0];
            }
            $norm = normalizeAssetPath($url, $htmlRelDir);
            $count++;
            return 'url(@themeAsset(\'' . $norm . '\'))';
        },
        $html
    );

    return $html;
}

/** Подпис на <script> за дедупликация (src или хеш на inline съдържание). */
function scriptSignature(DOMElement $s): string
{
    $src = trim($s->getAttribute('src'));
    return $src !== '' ? 'src:' . $src : 'inline:' . md5(trim($s->textContent));
}

/** Локализира header възел по таг или по class/id евристика. */
function detectHeader(DOMXPath $xpath): ?array
{
    foreach (['//body//header', '//body//nav'] as $q) {
        $n = $xpath->query($q);
        if ($n->length) {
            return [$n->item(0), str_contains($q, 'header') ? '<header> таг' : '<nav> таг'];
        }
    }
    foreach ($xpath->query('//body//*[@class or @id]') as $el) {
        $sig = strtolower($el->getAttribute('class') . ' ' . $el->getAttribute('id'));
        if (preg_match('/\b(header|navbar|topbar|masthead|site-head)\b/', $sig)) {
            return [$el, 'class/id евристика'];
        }
    }
    return null;
}

/** Локализира footer възел по таг или по class/id евристика (последния). */
function detectFooter(DOMXPath $xpath): ?array
{
    $n = $xpath->query('//body//footer');
    if ($n->length) {
        return [$n->item($n->length - 1), '<footer> таг'];
    }
    $match = null;
    foreach ($xpath->query('//body//*[@class or @id]') as $el) {
        $sig = strtolower($el->getAttribute('class') . ' ' . $el->getAttribute('id'));
        if (preg_match('/\b(footer|colophon|site-foot)\b/', $sig)) {
            $match = $el;
        }
    }
    return $match ? [$match, 'class/id евристика'] : null;
}

/** index → home; иначе sanitize-нато име на файла. */
function pageName(string $file): string
{
    $base = strtolower(pathinfo($file, PATHINFO_FILENAME));
    if ($base === 'index') {
        return 'home';
    }
    $base = preg_replace('/[^a-z0-9_-]+/', '-', $base);
    return trim($base, '-') ?: 'page';
}

/**
 * Извлича content на страница: предпочита <main>; иначе взима цялото <body>
 * минус разпознатите header/footer и директните body-level скриптове
 * (последните са общи и живеят в layout-а).
 */
function extractContent(DOMXPath $xpath): string
{
    $main = $xpath->query('//body//main')->item(0);
    if ($main) {
        return trim(innerHtml($main));
    }

    $body = $xpath->query('//body')->item(0);
    if (!$body) {
        return '';
    }

    // Работим върху клонинг в отделен документ, за да не пипаме оригинала.
    $tmpDoc = new DOMDocument();
    $imported = $tmpDoc->importNode($body->cloneNode(true), true);
    $tmpDoc->appendChild($imported);
    $tx = new DOMXPath($tmpDoc);

    // Махаме header/footer (същата детекция както за layout-а → консистентно).
    foreach ([detectHeader($tx), detectFooter($tx)] as $info) {
        if ($info && $info[0]->parentNode) {
            $info[0]->parentNode->removeChild($info[0]);
        }
    }
    // Махаме директните body-level скриптове (преместени са в layout-а).
    foreach (iterator_to_array($tx->query('/body/script')) as $el) {
        $el->parentNode->removeChild($el);
    }

    return trim(innerHtml($imported));
}

// ---------------------------------------------------------------------------
// Главна логика
// ---------------------------------------------------------------------------

$opts = parseArgs($argv);

if ($opts['help']) {
    printHelp();
    exit(0);
}

if (empty($opts['source']) || empty($opts['name'])) {
    fwrite(STDERR, "❌ Нужни са <папка-със-темата> и <име-на-темата>.\n\n");
    printHelp();
    exit(1);
}

if (!preg_match('/^[a-z0-9_-]+$/i', $opts['name'])) {
    fail("Името на темата трябва да е [a-z0-9_-]: {$opts['name']}");
}

$source = realpath(absPath($opts['source']));
if ($source === false || !is_dir($source)) {
    fail("Папката със сваляната тема не съществува: {$opts['source']}");
}

$engineRoot = realpath(dirname(__DIR__));
$target = $engineRoot . '/themes/' . $opts['name'];

if (is_dir($target) && !$opts['force']) {
    fail("Тема '{$opts['name']}' вече съществува в themes/.\n   Използвай --force, за да я презапишеш.");
}

// Намираме HTML файловете (само в корена на сваляната тема — типичният случай).
$htmlFiles = glob($source . '/*.html') ?: [];
if (!$htmlFiles) {
    // Опит и една ниво по-навътре (някои теми имат html/ подпапка).
    $htmlFiles = glob($source . '/*/*.html') ?: [];
}
if (!$htmlFiles) {
    fail('Не намерих .html файлове в сваляната тема.');
}

// Подреждаме: index.html да е първи (от него правим layout-а).
usort($htmlFiles, function ($a, $b) {
    $ai = strtolower(basename($a)) === 'index.html' ? 0 : 1;
    $bi = strtolower(basename($b)) === 'index.html' ? 0 : 1;
    return $ai <=> $bi ?: strcmp($a, $b);
});

echo "🎨 Импортирам тема '{$opts['name']}'\n";
echo "   Източник: {$source}\n";
echo "   Цел:      {$target}\n\n";

// Създаваме структурата.
foreach (['', '/layout', '/partials', '/pages', '/assets'] as $sub) {
    $dir = $target . $sub;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        fail("Не мога да създам: {$dir}");
    }
}

// 1. Копиране на asset папките.
$assetCount = 0;
$copiedDirs = [];
foreach (ASSET_DIRS as $d) {
    $src = $source . '/' . $d;
    if (!is_dir($src)) {
        continue;
    }
    $copiedDirs[] = $d;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = substr($item->getPathname(), strlen($source) + 1);
        $dest = $target . '/assets/' . normalize($rel);
        if ($item->isDir()) {
            if (!is_dir($dest)) {
                mkdir($dest, 0755, true);
            }
        } else {
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($item->getPathname(), $dest);
            $assetCount++;
        }
    }
}
echo "✅ Копирани {$assetCount} asset файла (" . (implode(', ', $copiedDirs) ?: 'няма') . ").\n";

// 2. Layout + partials от началната страница.
$primary = $htmlFiles[0];
$primaryRelDir = normalize(trim(substr(dirname($primary), strlen($source)), '/\\'));
$doc = loadDoc(file_get_contents($primary));
$xpath = new DOMXPath($doc);

$headNode = $xpath->query('//head')->item(0);
$headInner = $headNode ? innerHtml($headNode) : '<meta charset="utf-8">';

// <title> → @yield('title', '...')
$headInner = preg_replace_callback(
    '#<title>(.*?)</title>#is',
    fn($m) => "<title>@yield('title', '" . str_replace("'", "\\'", trim($m[1])) . "')</title>",
    $headInner
);
$rw = 0;
$headInner = rewriteAssets($headInner, $primaryRelDir, $rw);

// Header / footer.
[$headerInfo, $footerInfo] = [detectHeader($xpath), detectFooter($xpath)];
$headerNode = $headerInfo[0] ?? null;
$footerNode = $footerInfo[0] ?? null;
$headerMethod = $headerInfo[1] ?? 'НЕ Е НАМЕРЕН';
$footerMethod = $footerInfo[1] ?? 'НЕ Е НАМЕРЕН';

$headerHtml = $headerNode
    ? rewriteAssets(outerHtml($headerNode), $primaryRelDir, $rw)
    : "{{-- TODO: header не е разпознат автоматично. Виж REVIEW.md --}}\n<header></header>";
$footerHtml = $footerNode
    ? rewriteAssets(outerHtml($footerNode), $primaryRelDir, $rw)
    : "{{-- TODO: footer не е разпознат автоматично. Виж REVIEW.md --}}\n<footer></footer>";

// Общи скриптове (директни деца на body) → layout.
$layoutScripts = [];
$layoutSigs = [];
foreach ($xpath->query('/html/body/script') as $s) {
    $layoutSigs[] = scriptSignature($s);
    $layoutScripts[] = rewriteAssets(outerHtml($s), $primaryRelDir, $rw);
}
$scriptsBlock = $layoutScripts ? "\n" . implode("\n", $layoutScripts) : '';

// Сглобяване на layout-а.
$layout = <<<BLADE
{{--
    Авто-генериран layout от theme-import.php (механичен слой).
    Виж REVIEW.md за стъпките, които остават (nav → \$nav, i18n, дедуп).
--}}
<!DOCTYPE html>
<html lang="{{ \$site_language }}" dir="{{ \$text_direction }}">
<head>
{$headInner}
@stack('styles')
</head>
<body>
@include('partials.header')
<main>
@yield('content')
</main>
@include('partials.footer')
{$scriptsBlock}
@stack('scripts')
</body>
</html>

BLADE;

file_put_contents($target . '/layout/default.blade.php', $layout);
file_put_contents($target . '/partials/header.blade.php', $headerHtml . "\n");
file_put_contents($target . '/partials/footer.blade.php', $footerHtml . "\n");
echo "✅ Сглобени layout/default + partials/header + partials/footer.\n";
echo "   header: {$headerMethod} | footer: {$footerMethod}\n";

// 3. Страници.
$pages = [];
foreach ($htmlFiles as $file) {
    $relDir = normalize(trim(substr(dirname($file), strlen($source)), '/\\'));
    $pdoc = loadDoc(file_get_contents($file));
    $px = new DOMXPath($pdoc);

    $content = extractContent($px);
    $content = rewriteAssets($content, $relDir, $rw);

    // Уникалните за страницата скриптове → @push('scripts').
    $pushScripts = [];
    foreach ($px->query('/html/body/script') as $s) {
        if (!in_array(scriptSignature($s), $layoutSigs, true)) {
            $pushScripts[] = rewriteAssets(outerHtml($s), $relDir, $rw);
        }
    }
    $push = $pushScripts
        ? "\n\n@push('scripts')\n" . implode("\n", $pushScripts) . "\n@endpush"
        : '';

    // Per-page <title> → @section('title', '...').
    $titleSection = '';
    $titleNode = $px->query('//head/title')->item(0);
    if ($titleNode && trim($titleNode->textContent) !== '') {
        $t = str_replace("'", "\\'", trim($titleNode->textContent));
        $titleSection = "@section('title', '{$t}')\n\n";
    }

    $name = pageName($file);
    // Избягваме дублиране на име.
    if (isset($pages[$name])) {
        $name .= '-' . substr(md5($file), 0, 4);
    }

    $blade = "@extends('layout.default')\n\n{$titleSection}@section('content')\n{$content}\n@endsection{$push}\n";
    file_put_contents($target . '/pages/' . $name . '.blade.php', $blade);
    $pages[$name] = basename($file);
}
echo "✅ Създадени " . count($pages) . " страници.\n";

// 4. REVIEW.md
$date = date('Y-m-d H:i');
$copiedDirsStr = implode(', ', $copiedDirs) ?: '—';
$primaryBase = basename($primary);
$pageList = '';
foreach ($pages as $name => $srcFile) {
    $pageList .= "- `pages/{$name}.blade.php`  ←  `{$srcFile}`\n";
}

$review = <<<MD
# Theme import: `{$opts['name']}`

Генериран на {$date} от `utils/theme-import.php` (механичен слой).
Източник: `{$source}`

## Какво е направено автоматично

- Копирани **{$assetCount}** asset файла от папки: {$copiedDirsStr}
- `layout/default.blade.php` — от началната страница (`{$primaryBase}`)
- `partials/header.blade.php` — разпознат чрез: **{$headerMethod}**
- `partials/footer.blade.php` — разпознат чрез: **{$footerMethod}**
- Asset препратки пренаписани: **{$rw}** (→ `@themeAsset('...')`)
- Страници:
{$pageList}

## TODO — finishing pass (човек или AI чрез Claude Code)

Механичният слой НЕ прави следното — то изисква семантика:

1. **Навигация.** `partials/header.blade.php` още съдържа статичното меню на
   темата. Замени го с нашия динамичен `\$nav` цикъл — за референция виж
   `views/partials/header.blade.php` в engine-а.
2. **i18n.** Обвий видимите текстове в `__()`, за да станат преводими.
3. **Език/посока.** `layout` вече ползва `\$site_language` / `\$text_direction`;
   провери дали в темата няма hardcode-нат `lang`/`dir`.
4. **Asset пътища.** Провери, че `@themeAsset(...)` пътищата сочат реални
   файлове под `themes/{$opts['name']}/assets/`. Inline `<style>` и `url(...)`
   са пренаписани автоматично — прегледай ги.
5. **Скриптове.** Общите скриптове са в layout-а; специфичните за страница —
   в `@push('scripts')`. Махни евентуални дубликати.
6. **auth layout.** Ако темата има login/регистрация, направи `layout/auth`
   (без header) по модела на `views/layout/auth.blade.php`.
7. **Header/footer не е намерен?** Ако горе пише „НЕ Е НАМЕРЕН", извлечи ги
   ръчно от съответната страница.

## Активиране

В `config.php`: `'theme' => '{$opts['name']}'` (или ползвай
`utils/new-site.php --theme={$opts['name']}`).
MD;

file_put_contents($target . '/REVIEW.md', $review);
echo "✅ Записан REVIEW.md с обобщение и TODO.\n";

echo "\n🎉 Готово! Тема '{$opts['name']}' е скелетирана в themes/{$opts['name']}/.\n";
echo "   Следва finishing pass — виж themes/{$opts['name']}/REVIEW.md\n";

exit(0);
