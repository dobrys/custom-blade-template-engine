# Scaffolding инструменти: нов сайт и теми

Наръчник за четирите инструмента, които правят engine-а преизползваема база за други
сайтове и автоматизират създаването на теми.

```
НОВ САЙТ
  utils/new-site.php ──────────────▶ нов проект от engine скелета (детерминистично)

НОВА ТЕМА — два пътя:

  А) от сваляна тема (ThemeForest и т.н.)
     utils/theme-import.php ──▶ /themeify
     (механичен слой ~60-70%)   (семантичен finish: nav→$nav, i18n, auth)

  Б) от нулата по задание (без импорт)
     /themegen "бриф" ─────────▶ завършена тема (дизайн + engine правила)
```

| Инструмент | Тип | Какво | Слой |
|---|---|---|---|
| `utils/new-site.php` | CLI (PHP) | нов сайт от engine-а | — |
| `utils/theme-import.php` | CLI (PHP) | сваляна HTML тема → скеле | слой 1 |
| `/themeify` | Claude Code skill | дошлайфва импортирана тема | слой 2 |
| `/themegen` | Claude Code skill | нова тема от нулата по бриф | — |

Двата `utils/` скрипта са чист PHP (без `vendor/` зависимости) — работят и преди
`composer install`. `/themeify` и `/themegen` се изпълняват от Claude Code.

---

## 1. `utils/new-site.php` — нов сайт от engine-а

Създава нов проект, базиран на engine-а: копира скелета, изключва тайните и
локалното, генерира свеж `.env` и настройва конфигурацията.

### Употреба

```bash
php utils/new-site.php <целева-папка> [опции]
```

### Опции

| Опция | По подразбиране | Описание |
|---|---|---|
| `<целева-папка>` | — (задължителна) | Къде да се създаде новият сайт. |
| `--name="..."` | — | Име на сайта (записва се в `README.md`). |
| `--theme=name` | `default` | Активна тема (стойност в `config.php`). |
| `--env=...` | `development` | `development` или `production` (засяга `config.php` + `.env`). |
| `--force` | — | Презаписва, дори целевата папка да не е празна. |
| `--help` | — | Показва помощ. |

### Пример

```bash
php utils/new-site.php ../my-new-site --name="Моят сайт" --env=production
```

### Какво прави

- **Копира** engine скелета (`src/`, `routes/`, `views/`, `themes/`, `config/`,
  `public/`, `lang/`, `svelte/`, `composer.json`/`composer.lock`,
  `package.json`/`package-lock.json`, `config.php`, `router.php`, `init.php`, ...).
- **Изключва**: `.env` (тайни), `vendor/`, `node_modules/`, `.git/`, `.idea/`,
  `.claude/`, `docs/`, `claude-code-instructions.md`, компилираните `cache/*.php`
  (`cache/.gitignore` се запазва).
- **Генерира свеж `.env`** от `.env.example` с **нов случаен `JWT_SECRET`**
  (`base64(random_bytes(32))`) и `APP_ENV`/`APP_DEBUG` според `--env`.
- **Настройва `config.php`** (`theme`, `env`).
- **Добавя `.env` в `.gitignore`** на новия сайт (предпазва от изтичане на тайни).
- При `--name` записва нов `README.md`.

> **Lock файловете се копират нарочно** — всеки нов сайт тръгва от точно тестваните
> версии (възпроизводима база). За fresh resolve, махни ги от `EXCLUDE_FILES` в скрипта.

### Guard-и (скриптът отказва при)

- Непразна целева папка без `--force`.
- Невалиден `--env`.
- Целта съвпада с / е вътре в engine папката (и обратно).
- Липсваща целева папка.

### След това

```bash
cd <целева-папка>
composer install
npm install
# попълни DB_* и API_KEY в .env
npm run build        # или: npm run dev
```

---

## 2. `utils/theme-import.php` — импорт на сваляна тема (механичен слой)

Превръща свалена **статична HTML тема** (напр. от ThemeForest) в скеле на engine
тема под `themes/{name}/`. Това е **само механичният слой** — семантиката идва после
с `/themeify`.

### Употреба

```bash
php utils/theme-import.php <папка-със-сваляната-тема> <име-на-темата> [--force]
```

- `<име-на-темата>` трябва да е `[a-z0-9_-]`.
- `--force` презаписва съществуваща тема със същото име.

### Пример

```bash
php utils/theme-import.php ../downloads/cooltheme cooltheme
```

### Какво прави надеждно

- **Копира asset папките** (`css`, `js`, `img`/`images`, `fonts`, `assets`,
  `vendor`, `plugins`, ...) в `themes/{name}/assets/`, запазвайки структурата.
- С **DOMDocument** локализира `<head>`/`<header>`/`<footer>`/`<main>`
  (по таг, иначе class/id евристика: `header`/`navbar`/`footer`/`site-header`...).
- Сглобява `layout/default.blade.php` + `partials/header.blade.php` +
  `partials/footer.blade.php`.
- Всяка `.html` → `pages/{name}.blade.php` (`@extends` + `@section`);
  `index.html` → `home.blade.php`.
- **Пренаписва препратките**:
  - `src`/`href` към asset (css/js/img/...) и `url(...)` → `@themeAsset('...')`
  - навигационни `href="x.html"` → engine рутове (`index.html`→`/`, `about.html`→`/about`)
  - външни URL-и (http, CDN, `//`, `mailto:`, `#`) остават недокоснати
- `<title>` → `@yield('title', '...')` в layout-а + per-page `@section('title', '...')`.
- Изнася общите `<script>` (директни деца на `<body>`) в layout-а; специфичните за
  страница → `@push('scripts')` (без дублиране на общите).
- Пише **`themes/{name}/REVIEW.md`** с обобщение и TODO за finishing pass.

### Какво НЕ прави (изисква семантика → слой 2)

- Статичното меню → динамичния `$nav` цикъл.
- Обвиване на текстове в `__()` за i18n.
- Умна дедупликация на повтарящи се блокове.

### Изходна структура

```
themes/{name}/
├── layout/default.blade.php
├── partials/header.blade.php
├── partials/footer.blade.php
├── pages/
│   ├── home.blade.php
│   └── ...
├── assets/                 (css/js/img/...)
└── REVIEW.md               (обобщение + TODO)
```

---

## 3. `/themeify` — семантичен finishing pass (Claude Code skill)

Проектен Skill, който довършва механично импортираната тема — частта, изискваща
преценка. Намира се в `.claude/skills/themeify/SKILL.md`.

### Кога е наличен

Skill-овете се зареждат в началото на сесия. Ако току-що е създаден, ще е достъпен в
**следваща** Claude Code сесия (или след рестарт).

### Употреба

В Claude Code напиши:

```
/themeify <име-на-темата>
```

Ако пропуснеш името, Claude ще погледне кои теми имат `REVIEW.md` и ще попита.

### Какво прави

1. **Чете `REVIEW.md`** на темата (TODO + методите на разпознаване).
2. **Навигация** — заменя статичното меню в `partials/header.blade.php` с
   динамичния `$nav` цикъл (пазейки класовете/стила на темата).
3. **i18n** — обвива видимите текстове в `__()`.
4. **Език/посока** — `{{ $site_language }}` / `{{ $text_direction }}`.
5. **Asset пътища** — проверява, че `@themeAsset(...)` сочат реални файлове.
6. **Скриптове** — дедупликация layout vs `@push('scripts')`.
7. **auth layout** — създава `layout/auth` при login/регистрация.
8. **Верификация** — рендерира страници през реалния `BladeEngine`, за да потвърди,
   че темата буутва и `$nav` се обхожда без грешки.

---

## 4. `/themegen` — нова тема от нулата по задание (Claude Code skill)

Генерира **завършена тема от текстов бриф**, без да разчита на импортирана тема.
Прилага engine правилата (динамичен `$nav`, `__()`, `@themeAsset`) и добър фронтенд
дизайн от самото начало. Намира се в `.claude/skills/themegen/SKILL.md`.

### Употреба

```
/themegen <бриф> [име-на-темата]
```

### Пример

```
/themegen Тема за платформа с обучителни видеа за деца 6-10 г.
Светъл, игрив, цветен вид; едри заоблени видео-карти с thumbnail и категория;
hero със CTA „Гледай сега"; страници: home, категории, за родители.
Аудиторията са деца + родители — четимост и едри тъч таргети.
```

Резултат: `themes/kids-edu/` с `layout/default`, `partials/header` (вече с `$nav`
цикъла), `partials/footer`, `pages/` (home, categories, parents),
`assets/css/theme.css` (дизайн системата) и стилизирана `errors/404`.

### Какво е важно да укажеш за оптимални резултати

Колкото по-конкретен е брифът, толкова по-малко гадае Claude. Полезно е да включиш:

- **Аудитория и цел** — за кого е и какво трябва да правят (напр. „деца 6-10 + родители;
  да намират и гледат видеа"). Това движи тон, размери, достъпност.
- **Тон/настроение** — игрив / корпоративен / минималистичен / премиум / тъмен и т.н.
- **Светла или тъмна** схема (или „и двете").
- **Палитра и шрифтове** — ако имаш предпочитания (или „избери подходящи"). Може
  референтен стил („като YouTube Kids", „като Duolingo") — Claude взима **посоката**,
  не копира.
- **Ключови страници** — изброй ги (home, категории, единично видео, login...).
  Те стават `pages/*` и трябва да съвпадат с `config/nav.php` (виж по-долу).
- **Задължителни компоненти** — видео-карта, hero, категории-плочки, форма и т.н.
- **Брандинг** — име/слоган за header-а и `@yield('title')`.
- **Език и i18n** — основен език; текстовете и без това отиват в `__()`, но кажи ако
  трябва да е RTL-готова.

> **Вагуен бриф** („направи готина тема") → Claude ще зададе 1-2 уточняващи въпроса.
> **Конкретен бриф** (горния пример) → директно качествен резултат.

### Важно: nav и страници трябва да съвпадат

`$nav` в header-а идва от `config/nav.php` (през `NavBuilder`). Ако темата въвежда
нови страници, те се добавят на **три** места: route в `router.php`, view под
`pages/`, и пункт в `config/nav.php`. `/themegen` създава view-овете; пунктовете в nav
и рутовете обикновено се добавят отделно (Claude ще го отбележи).

---

## Пълен пример (end-to-end)

```bash
# 1. Нов сайт от engine-а
php utils/new-site.php ../client-site --name="Клиентски сайт" --env=production
cd ../client-site
composer install && npm install

# 2. Импорт на сваляна тема (механичен слой)
php utils/theme-import.php ../downloads/fancytheme fancytheme

# 3. Активирай темата
#    config.php → 'theme' => 'fancytheme'
#    (или още на стъпка 1: new-site.php --theme=fancytheme)

# 4. Семантичен finish (в Claude Code сесия)
#    /themeify fancytheme

# 5. Преглед на themes/fancytheme/REVIEW.md за останали ръчни TODO
npm run build
```

**Алтернатива (път Б — тема от нулата вместо импорт):** на стъпки 2-4 вместо
`theme-import.php` + `/themeify` използвай един `/themegen` с бриф (виж секция 4),
после `config.php → 'theme' => '...'` и `npm run build`.

---

## Бележки и добри практики

- **Тайни.** `new-site.php` никога не копира `.env`; генерира свеж със случаен
  `JWT_SECRET` и добавя `.env` в `.gitignore`. Не комитвай реален `.env`.
- **Лиценз на темите.** ThemeForest лицензите обикновено са „един лиценз на краен
  продукт". Превръщането на купена тема в преизползваема за много сайтове може да
  нарушава лиценза — провери преди масово ползване.
- **Claude Pro vs API.** `/themeify` и `/themegen` използват Claude Code (Pro/Max
  план). Собствен админ панел, който вика `api.anthropic.com`, се таксува отделно по
  API — Pro не го покрива.
- **CLI, не web админ.** Това са build-time dev инструменти, пускани рядко от
  разработчик. Изходът (Blade файлове) така или иначе изисква преглед в редактор.
- **Качество без AI.** Само механичният слой (`theme-import.php`) дава тема, която
  се рендерира със стил/asset-и, но с hardcode-нат nav и без i18n. Слой 2
  (`/themeify` или ръчно) я довежда до завършена.
