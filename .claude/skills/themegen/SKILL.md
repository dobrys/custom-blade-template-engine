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

### 7. Верификация (задължително)

Рендерирай home (и поне още една страница) през реалния `BladeEngine`, за да потвърдиш,
че темата буутва, `$nav` се обхожда и `__()` работи. Временен harness:

```php
<?php
$root = 'E:/laragon/www/engine';
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

## Активиране

В `config.php`: `'theme' => '{name}'` (или нов сайт направо с темата:
`php utils/new-site.php <папка> --theme={name}`).

## Накрая

- Кажи кои файлове са създадени и обобщи дизайн посоката (палитра/шрифт/мотив).
- Спомени, че `$nav` пунктовете идват от `config/nav.php` — ако темата иска различни
  страници, те се добавят там + route + view.
- Не комитвай освен ако потребителят поиска (review-first работен поток).
