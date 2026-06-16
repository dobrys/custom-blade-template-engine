---
name: themegen
description: Създава ЦЯЛА НОВА engine тема от нулата по текстово задание (бриф), прилагайки нашите engine правила и добър фронтенд дизайн — без да разчита на импортирана тема. Ползвай когато потребителят иска нова тема по описание (напр. „тема за обучителни видеа за деца") или напише /themegen [бриф].
---

# /themegen — нова engine тема от нулата по задание

За разлика от `/themeify` (който дошлайфва механично импортирана тема), този skill
**генерира завършена тема от текстов бриф**, прилагайки engine конвенциите и добър
дизайн от самото начало. Header-ът се ражда директно с динамичния `$nav` цикъл,
текстовете са обвити в `__()`, asset-ите минават през `@themeAsset`.

## Вход

- Бриф (свободен текст): напр. „тема за обучителни видеа за деца", „минималистично
  корпоративно портфолио", „тъмен лендинг за SaaS".
- Име на темата (`[a-z0-9_-]`). Ако не е дадено, изведи го от брифа и потвърди.
- Ако брифът е твърде мъгляв за дизайн посока, задай 1-2 уточняващи въпроса
  (аудитория, светла/тъмна, ключови страници), после действай.

## Engine контекст (спазвай го стриктно)

Глобални променливи във view-овете (от `init.php`):
`$nav`, `$currentUrl`, `$current_path`, `$languages`, `$site_language`,
`$text_direction`, `$siteURL`, `$is_logged_in`.

Директиви: `@asset(...)`, `@themeAsset(...)`, `@sitevar(...)`, `@dump`, `@dd`.
Преводи: `__('низ')`.

`$nav` е изходът на `App\Nav\NavBuilder->build()` — списък от item-и:
`$item->hasChildren()`, `$item->children`, `$item->isActive($currentUrl)`,
`$item->url`, `$item->icon`, `$item->label`, `$item->badge`.

Резолюция на view-ове: **theme-first, после `views/` fallback**. Темата override-ва
само това, което се различава от базата. За референция: `views/layout/default.blade.php`,
`views/layout/auth.blade.php`, `views/partials/header.blade.php`.

Asset сервиране: `routes/assets.php` сервира `themes/{name}/assets/**` с MIME map за
css/js/шрифтове/изображения/видео (непознати типове → `finfo`). Custom CSS, шрифтове и
изображения работят.

## Процедура

### 1. Дизайн посока от брифа

Приложи принципите на **`frontend-design` skill-а** (избягвай generic AI естетика).
От брифа изведи и **запиши накратко** дизайн система:

- **Палитра** — 4-6 цвята като CSS custom properties (вкл. accent, фон, текст,
  състояния). Пример за „детски обучителни видеа": топли, наситени, дружелюбни
  цветове, висок контраст за четимост, но не агресивни.
- **Типография** — display шрифт за заглавия + четим body шрифт (Google Fonts по CDN
  или локални под `assets/fonts/`). За деца: заоблен, приятелски display.
- **Форма и ритъм** — radius, spacing скала, сенки. За деца: големи заоблени картички,
  щедри отстъпи, едри тъч таргети.
- **Ключови компоненти** според брифа (за видео тема: видео карта с thumbnail/badge,
  категории-плочки, hero с CTA).
- **Достъпност** — контраст, focus стилове, `alt`, semantic HTML, responsive (mobile-first).

### 2. Структура

Създай:

```
themes/{name}/
├── layout/default.blade.php
├── layout/auth.blade.php        (ако брифът има login/регистрация)
├── partials/header.blade.php
├── partials/footer.blade.php
├── pages/                       (home + страниците от брифа)
├── errors/404.blade.php         (по желание, стилизирана)
└── assets/css/theme.css         (дизайн системата)
```

### 3. CSS дизайн система → `assets/css/theme.css`

Истински CSS файл с custom properties в `:root`, mobile-first, без зависимост от
Bootstrap (освен ако брифът го изисква). Скелет:

```css
:root {
  --color-bg: #...; --color-surface: #...; --color-text: #...;
  --color-accent: #...; --radius: 1rem; --space: 1rem;
  --font-display: "...", system-ui; --font-body: "...", system-ui;
}
* { box-sizing: border-box; }
body { margin: 0; font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); }
/* компоненти според дизайн системата: .card, .hero, .nav, .btn ... */
```

### 4. `layout/default.blade.php`

Пълен layout, който ползва engine глобалите и линква темата CSS:

```blade
<!DOCTYPE html>
<html lang="{{ $site_language }}" dir="{{ $text_direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '{{ ИМЕ/СЛОГАН ОТ БРИФА }}')</title>
    <link rel="stylesheet" href="@themeAsset('css/theme.css')">
    {{-- по желание: Google Fonts CDN --}}
    @stack('styles')
</head>
<body>
@include('partials.header')
<main>
@yield('content')
</main>
@include('partials.footer')
@stack('scripts')
</body>
</html>
```

> Ако темата има JS bundle (Svelte/Vite), добави `@themeAsset('js/all.js')` по модела
> на `themes/default/layout/default.blade.php`.

### 5. `partials/header.blade.php` — С `$nav` от самото начало

НЕ пиши статично меню. Генерирай директно динамичния цикъл, стилизиран по дизайна:

```blade
<header class="site-header">
    <a class="brand" href="/">{{ __('ИМЕ ОТ БРИФА') }}</a>
    <nav class="main-nav">
        @foreach($nav as $item)
            @if($item->hasChildren())
                <div class="nav-group">
                    <a href="{{ $item->url }}" class="{{ $item->isActive($currentUrl) ? 'active' : '' }}">{{ __($item->label) }}</a>
                    <div class="nav-children">
                        @foreach($item->children as $child)
                            <a href="{{ $child->url }}">{{ __($child->label) }}</a>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ $item->url }}" class="{{ $item->isActive($currentUrl) ? 'active' : '' }}">
                    @if($item->icon)<i class="{{ $item->icon }}"></i>@endif
                    {{ __($item->label) }}
                </a>
            @endif
        @endforeach
    </nav>
    @if($is_logged_in)
        <a class="btn" href="/logout">{{ __('Log-out') }}</a>
    @else
        <a class="btn" href="/login">{{ __('Log-in') }}</a>
    @endif
</header>
```

(По желание добави и language switcher по модела от `views/partials/header.blade.php`.)

### 6. Страници

Всяка page: `@extends('layout.default')`, `@section('title', '...')`, `@section('content')`.
Целият видим текст в `__()`. Изображенията/иконите през `@themeAsset(...)`. Реализирай
компонентите от дизайн системата (напр. видео карти за обучителната тема — с placeholder
thumbnail и `__()` надписи).

> **Ако съдържанието е частно** (видеа/материали само за логнати) — НЕ го слагай в
> `assets/` и НЕ го inline-вай на публични страници. Виж **секция 8**.

### 7. Верификация (задължително)

Рендерирай home (и поне още една страница) през реалния `BladeEngine`, за да потвърдиш,
че темата буутва, `$nav` се обхожда и `__()` работи. Временен harness:

```php
<?php
// project root = текущата работна папка (сайтът, в който пускаш skill-а).
// Така harness-ът е преносим — работи и в engine-а, и във всеки производен сайт.
$root = getcwd();
require $root . '/vendor/autoload.php';
require_once $root . '/src/helpers/functions.php';   // asset/theme_asset/site_var
require_once $root . '/src/helpers/translator.php';  // __()
$_SERVER['HTTP_HOST'] = 'example.test'; $_SERVER['REQUEST_SCHEME'] = 'https';
\App\Config::load(require $root . '/config.php');
$tr = new class implements \App\Contracts\TranslatorInterface {
    public function translate(string $k, array $r = [], ?string $l = null): string { return $k; }
};
$b = new \App\BladeEngine($tr, $root . '/views', $root . '/cache', '{name}');
$navBuilder = new \App\Nav\NavBuilder(\App\Config::nav(), false, 'https://example.test/');
$b->assign('nav', $navBuilder->build());
$b->assign('currentUrl', $navBuilder->getCurrentUrl());
$b->assign('site_language', 'en'); $b->assign('text_direction', 'ltr');
$b->assign('current_path', '/'); $b->assign('is_logged_in', false);
$b->assign('languages', require $root . '/languages.php');
echo $b->render('pages.home') . "\nOK\n";
```

Изтрий harness-а след проверката.

### 8. Защитено съдържание — видеа/частни файлове само за логнати (опционално)

Прилага се КОГАТО брифът включва съдържание, достъпно само след login (видеа,
членски материали, платени уроци). Презентационните asset-и (css/js/img) минават
през `assets.php` и са публични — това е ОК за тях, но **частното съдържание НЕ бива
да е в `themes/{name}/assets/` (публично е) и НЕ бива да се inline-ва на публични
страници**. Вместо това:

**1. Хранилище извън web root и извън темата:** `storage/videos/{slug}.mp4`.
Добави `storage/videos/` в `.gitignore` (тежки файлове, не се комитват).

**2. Два auth-gated route-а** в `router.php` (`auth` middleware гейтва ПРЕДИ файла):

```php
'watch'  => ['file' => __DIR__ . '/routes/watch.php',  'middleware' => ['auth']],
'stream' => ['file' => __DIR__ . '/routes/stream.php', 'middleware' => ['auth']],
```

**3. `routes/stream.php`** — стриймва файла с HTTP Range. Три задължителни неща:

```php
<?php
// КРИТИЧНО: освободи session lock-а ВЕДНАГА след auth. Иначе PHP държи
// ексклузивен lock върху сесията докато стриймваш (секунди) и всяка паралелна
// заявка от същия потребител (seek/нова навигация) БЛОКИРА.
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

$slug = $_GET['v'] ?? '';
// Allowlist: само [a-z0-9-] (без точки/слешове) → path traversal невъзможен.
if (!preg_match('/^[a-z0-9-]+$/', $slug)) { http_response_code(400); exit('Bad request'); }

$dir  = realpath(\App\Config::basePath('storage/videos'));
$file = realpath($dir . '/' . $slug . '.mp4');
if ($dir === false || $file === false || strpos($file, $dir) !== 0 || !is_file($file)) {
    http_response_code(404); exit('Not found');
}

$size = filesize($file); $start = 0; $end = $size - 1;
header('Content-Type: video/mp4');
header('Cache-Control: private, no-store');   // НЕ public — съдържанието е гейтнато
header('Accept-Ranges: bytes');
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m)) {
    if ($m[1] === '' && $m[2] !== '') { $start = max(0, $size - (int)$m[2]); }
    else { $start = (int)$m[1]; $end = $m[2] === '' ? $end : min((int)$m[2], $end); }
    if ($start > $end || $start >= $size) { http_response_code(416); header("Content-Range: bytes */$size"); exit; }
    http_response_code(206); header("Content-Range: bytes {$start}-{$end}/{$size}");
}
header('Content-Length: ' . ($end - $start + 1));
$fp = fopen($file, 'rb'); fseek($fp, $start); $remaining = $end - $start + 1;
while ($remaining > 0 && !feof($fp)) {
    $chunk = fread($fp, min(8192, $remaining));
    if ($chunk === false) break;
    echo $chunk; flush(); $remaining -= strlen($chunk);
}
fclose($fp); exit;
```

**4. `routes/watch.php`** — само рендерира страницата с плейъра:

```php
<?php
global $blade;
$slug = $_GET['v'] ?? '';
$blade->assign('video_slug', preg_match('/^[a-z0-9-]+$/', $slug) ? $slug : '');
$blade->display('pages.watch');
```

**5. `pages/watch.blade.php`** — плейърът сочи към гейтнатия поток:
`<video src="/stream?v={{ $slug }}" controls autoplay playsinline>` (+ заглавие, back линк).

**6. Публичните карти/линкове сочат към `/watch?v={slug}`** — никакъв медиен файл на
публична страница (gradient + emoji + play икона като thumbnail).

**Верификация на гейта (ЗАДЪЛЖИТЕЛНА при защитено съдържание):**
- нелогнат → `/watch` и `/stream` дават **302 към `/login`** (не 200/байтове);
- логнат (в dev: `?msisdn=<валиден от DummyProvider>`) → `/stream` Range дава **206** + `Cache-Control: private`;
- **конкурентност** (доказва `session_write_close`): пусни throttle-нат стрийм във фон и
  засечи паралелна authed заявка — трябва да е ~ms, не да чака стрийма:
  ```bash
  curl -skb jar --limit-rate 1M --max-time 15 -o /dev/null ".../stream?v=slug" &
  curl -skb jar -o /dev/null -w "%{time_total}\n" ".../watch?v=other"   # ~0.05s = OK
  ```

**Бележка:** нелогнат, бутнат към `/login` от `/watch?v=slug`, се връща автоматично на
същото видео след вход — `RouteResolver` пази пълния `REQUEST_URI` (с query) като
`next_page`. (Ако работиш на стар сайт, където `next_page` пази само path-а, видеото
ще се губи при login bounce — backport-ни поправката от engine `RouteResolver`/`signin`.)

## Активиране

В `config.php`: `'theme' => '{name}'` (или нов сайт направо с темата:
`php utils/new-site.php <папка> --theme={name}`).

## Накрая

- Кажи кои файлове са създадени и обобщи дизайн посоката (палитра/шрифт/мотив).
- Спомени, че `$nav` пунктовете идват от `config/nav.php` — ако темата иска различни
  страници, те се добавят там + route + view.
- Не комитвай освен ако потребителят поиска (review-first работен поток).
