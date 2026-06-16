---
name: themeify
description: Finishing pass за механично импортирана engine тема (изход на utils/theme-import.php под themes/{name}/). Превръща скелето в завършена тема — статично меню → динамичния $nav цикъл, обвиване на текстове в __() за i18n, дедупликация на скриптове, auth layout. Ползвай след theme-import.php или когато потребителят напише /themeify [тема].
---

# /themeify — finishing pass на импортирана тема

`utils/theme-import.php` прави **механичния** слой (asset-и, layout/partials/pages
скеле, asset URL rewrite). Този skill прави **семантичния** finishing pass, който
изисква преценка и затова не е автоматизиран в скрипта.

## Вход

- Аргумент: име на темата (папка под `themes/<name>/`). Ако липсва, виж кои теми
  имат `themes/<name>/REVIEW.md` и питай коя, или вземи последно-импортираната.
- Винаги първо **прочети `themes/<name>/REVIEW.md`** — там е списъкът с TODO и
  методите на разпознаване (вкл. дали header/footer са „НЕ Е НАМЕРЕН").

## Engine контекст (важно — спазвай го)

Глобални променливи, налични във view-овете (assign-ват се в `init.php`):
`$nav`, `$currentUrl`, `$current_path`, `$languages`, `$site_language`,
`$text_direction`, `$siteURL`, `$is_logged_in`.

Blade директиви: `@asset(...)`, `@themeAsset(...)`, `@sitevar(...)`, `@dump`, `@dd`.
Преводи: `__('низ')` (и `__('низ', ['x' => $y])` за заместване).

Каноничните референции са във `views/` — отвори ги и копирай патерните оттам:
- `views/partials/header.blade.php` — пълният `$nav` цикъл + language switcher + login/logout.
- `views/layout/default.blade.php` — базов layout.
- `views/layout/auth.blade.php` — auth layout (без header).

## Процедура

Работи стъпка по стъпка. След всяка стъпка, която променя файл, продължавай;
накрая пусни render проверката.

### 1. Навигация: статично меню → `$nav`

В `themes/<name>/partials/header.blade.php` намери статичния `<ul>`/меню и го
замени с динамичния цикъл. `$nav` е списък от item-и със следния API:

- `$item->hasChildren()`, `$item->children`
- `$item->isActive($currentUrl)` → bool
- `$item->url`, `$item->icon`, `$item->label`, `$item->badge`

Запази **класовете и структурата на темата** (за да не счупиш стила) — само
вмъкни `@foreach($nav as $item)` около `<li>` патерна на темата. Скелет:

```blade
@foreach($nav as $item)
    @if($item->hasChildren())
        <li class="КЛАС-НА-ТЕМАТА dropdown">
            <a class="{{ $item->isActive($currentUrl) ? 'active' : '' }}" href="{{ $item->url }}">
                @if($item->icon)<i class="{{ $item->icon }}"></i>@endif
                {{ __($item->label) }}
            </a>
            <ul class="ДРОПДАУН-КЛАС-НА-ТЕМАТА">
                @foreach($item->children as $child)
                    <li><a class="{{ $child->isActive($currentUrl) ? 'active' : '' }}" href="{{ $child->url }}">{{ __($child->label) }}</a></li>
                @endforeach
            </ul>
        </li>
    @else
        <li class="КЛАС-НА-ТЕМАТА">
            <a class="{{ $item->isActive($currentUrl) ? 'active' : '' }}" href="{{ $item->url }}">
                @if($item->icon)<i class="{{ $item->icon }}"></i>@endif
                {{ __($item->label) }}
            </a>
        </li>
    @endif
@endforeach
```

Добави и login/logout бутон и (по желание) language switcher по модела от
`views/partials/header.blade.php`, ако темата има място за тях:

```blade
@if($is_logged_in)
    <a href="/logout">{{ __('Log-out') }}</a>
@else
    <a href="/login">{{ __('Log-in') }}</a>
@endif
```

### 2. i18n: обвий видимите текстове в `__()`

Във `partials/*`, `layout/*` и `pages/*` обвий **видимия за потребителя текст** в
`__()`. Обвивай:
- Текст в навигация, бутони, заглавия, абзаци, alt/title/placeholder атрибути.

НЕ обвивай: чисти числа, символи (`©`, `→`), икони, URL-и, класове, `{{ }}` изрази,
вече динамични стойности. Внимавай с текст, размесен с HTML — обвивай само текстовите
парчета. При съмнение остави коментар `{{-- i18n? --}}` вместо да рискуваш да счупиш markup.

### 3. Език и посока

Провери `layout/default.blade.php` за hardcode-нат `lang="en"` или `dir="ltr"` и ги
замени с `{{ $site_language }}` / `{{ $text_direction }}` (скриптът обикновено вече го
прави, но провери `<html>` тага и евентуални inline места).

### 4. Asset пътища

Прегледай `@themeAsset('...')` препратките — потвърди, че сочат реално съществуващи
файлове под `themes/<name>/assets/`. Особено провери пренаписаните inline `<style>` и
`url(...)`. Поправи грешни относителни пътища.

### 5. Скриптове

Общите скриптове трябва да са в `layout/default.blade.php` преди `@stack('scripts')`;
специфичните за страница — в `@push('scripts')` в съответната page. Махни дубликати
(един и същ `src` и в layout, и в page). Ако темата има build (Svelte/Vite), добави
`@themeAsset('js/all.js')` по модела на `themes/default/layout/default.blade.php`.

### 6. auth layout (ако темата има login/регистрация)

Ако темата съдържа login/signup страница, направи `themes/<name>/layout/auth.blade.php`
(без header/nav) по модела на `views/layout/auth.blade.php`, и насочи съответните pages
към него (`@extends('layout.auth')`).

### 7. Header/footer „НЕ Е НАМЕРЕН"

Ако REVIEW.md отбелязва липсващ header/footer, отвори началната страница на темата и
извлечи блока ръчно в `partials/header.blade.php` / `partials/footer.blade.php`.

## Верификация (задължително преди „готово")

Рендерирай поне 2 страници през реалния `BladeEngine`, за да потвърдиш, че темата
буутва и `$nav` се обхожда без грешки. Временен harness:

```php
<?php
$root = 'E:/laragon/www/engine';
require $root . '/vendor/autoload.php';
require_once $root . '/src/helpers/functions.php';   // asset/theme_asset/site_var
require_once $root . '/src/helpers/translator.php';  // __() (нужен след i18n стъпката)
$_SERVER['HTTP_HOST'] = 'example.test'; $_SERVER['REQUEST_SCHEME'] = 'https';
\App\Config::load(require $root . '/config.php');
$tr = new class implements \App\Contracts\TranslatorInterface {
    public function translate(string $k, array $r = [], ?string $l = null): string { return $k; }
};
$b = new \App\BladeEngine($tr, $root . '/views', $root . '/cache', '<name>');
// $nav е изходът на NavBuilder->build() (НЕ суровият Config::nav() масив).
$navBuilder = new \App\Nav\NavBuilder(\App\Config::nav(), false, 'https://example.test/');
$b->assign('nav', $navBuilder->build());
$b->assign('currentUrl', $navBuilder->getCurrentUrl());
$b->assign('site_language', 'en'); $b->assign('text_direction', 'ltr');
$b->assign('current_path', '/'); $b->assign('is_logged_in', false);
$b->assign('languages', require $root . '/languages.php');
foreach (['pages.home'] as $t) { echo $b->render($t) . "\n"; }
echo "OK\n";
```

Ако `$nav` цикълът гръмне (липсващ метод/property), значи структурата на item-а не
съвпада — сверѝ с `App\Nav\NavBuilder` / `App\Nav\NavItem` / `views/partials/header.blade.php`.

## Накрая

- Кажи на потребителя кои файлове са пипнати и кои TODO от REVIEW.md остават
  (ако някой изисква решение от него — напр. кои pages са auth).
- Спазвай review-first: при по-голяма промяна в чужд/съществуващ файл, потвърди преди да продължиш.
- Не комитвай освен ако потребителят поиска.
