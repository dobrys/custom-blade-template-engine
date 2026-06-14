# Engine Upgrade Plan

> Базиран на `docs/architecture-review.md` (2026-06-12). Само за `engine` —
> без сравнения/портове от други проекти.

---

## Статус (2026-06-14)

Завършени точки:

- **DB config** — `NthMember::__construct()` сочи към env-driven `config/db.php`
  (`DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASSWORD` от `.env`).
- **Vite/Svelte build** — пресъздадена `svelte/` source директория
  (`svelte/components.js`, `svelte/svelte-load-all.js`, `svelte/svelte-all.js`
  + примерен компонент). `vite.config.js` с HMR `server` блок и
  `componentApi = 4`. `npm run build`/`npm run dev` работят.
- **Login flow** — решено: **само MSISDN login** през `AuthMiddleware` →
  `AuthService`. Премахнати: `src/Auth/LoginInterface.php`,
  `src/Auth/DefaultLoginHandler.php`, `'handler'` ключът в `config.php`,
  `views/pages/login.blade.php`, `themes/astro/pages/login.blade.php`
  (username/password UI), и закоментираният `pages.login` ред в
  `routes/login.php`. Това закрива и проблема с неприсвоен `$errors` —
  единственото место, ползващо го, е премахнато.
- **Missing routes (`profile`/`single`/`terms`/`subscribe`, nav линкове
  `/settings`/`/billing`)** — затворено като не-проблем: умишлени "показни"
  placeholder-и за разработчика, не пипаме.
- **`SessionManager::destroy()`** — поправен mojibake коментар
  (`src/SessionManager.php:19`) → коректна кирилица.
- **`site_var()` / `@sitevar`** — добавен helper (`src/helpers/functions.php`)
  + Blade директива (`src/BladeEngine.php`) за locale-keyed конфигурационни
  текстове (телефон/имейл/...), ключувани по `$_SESSION['app_language']` с
  `default` fallback. Schema в `config/site_vars.php` (празна, без реални
  данни).
- **Front controller (4.3, частично)** — решено: `public/` е каноничният
  front controller. Root `.htaccess` премахнат (`git rm .htaccess`). Остава
  решение за root `index.php` (вече без `.htaccess` rewrite за pretty URLs —
  потенциално мъртъв за всичко освен `/`) и за `routes/assets.php` (може да е
  dead code под Apache static serving).
- **Credential leak в `die()` (1.2)** — `NthMember::__construct()` вече логва
  PDO connection грешката чрез `error_log()` и показва generic съобщение
  ("Service temporarily unavailable."), без DSN/credentials в response.
- **`renderString()` eval RCE (4.1)** — преди компилиране, входният string се
  чисти от `@php ... @endphp` блокове и raw `<?php`/`<?=`/`<?` tags, така че
  CMS/DB съдържание не може да изпълни произволен PHP през `eval()`.
- **Global state/config foundation (2.1 частично, 2.5, 2.7, 3.8, 4.4)** —
  нов `App\Config` static facade (`src/Config.php`): `Config::get()` замества
  `global $config` (`Router`, `AuthMiddleware`, `routes/signin.php`);
  `Config::nav()` кешира `config/nav.php` (вече зареждан само веднъж);
  `Config::themesPath()/configPath()/routesPath()` заместват hardcoded
  `__DIR__.'/../...'` в `BladeEngine`, `routes/assets.php`, `NthMember`,
  `site_var()`, `Router`. `SK` константата премахната — `routes/logout.php`
  вече ползва `env('JWT_SECRET')` (3.8). 7 директни `$_SESSION[...]` достъпа
  (`LanguageDetector`, `GettextTranslator`, `NthMember`, `App.php`,
  `site_var()`) минават през `SessionManager::get()/set()` (2.5).
  Остава: `$GLOBALS['translator']`/`current_theme`/`$GLOBALS['blade']` —
  отделен follow-up (виж P3 таблицата).
- **Router decomposition (2.2)** — извлечен `App\Nav\RouteResolver`
  (`src/Nav/RouteResolver.php`): отговаря само за превръщане на request URI в
  route slug + next_page tracking. `Router` остава фокусиран върху
  dispatch/middleware/nav refresh/404. Попътно фиксирани:
  - **1.5** — `refreshNavAndAuth()` подава `currentUrl` като `/route`
    (`/` за home), вече съвпада с nav item `url` формата → `isActive()`
    работи.
  - **3.1** — `notFound()` присвоява `title` чрез `assign()` вместо игнориран
    2-ри аргумент на `render()`.
  - **3.9** — премахнати мъртвите `siteURL`/`user` assigns в
    `matchStaticRoute()`.
  - **3.11** — `next_page` вече не се записва за `themes/...` asset
    requests.
- **Auth input validation (3.2)** — `AuthService::handle()` вече чете
  `public_uuid`/`msisdn` през `input()` (вместо суров `$_REQUEST`) и ги
  валидира с формат regex (`^[A-Za-z0-9_-]{1,64}$` за UUID,
  `^\+?[0-9]{6,15}$` за MSISDN) преди да тръгне login опит — невалиден формат
  просто не отваря login flow.
- **P4 почистване** — премахнати `src/translator.php` (2.8, мъртъв дубликат
  на `src/helpers/translator.php`), `__globals.php` (2.9, orphan файл с
  конфликтен `const SK`), `views/pages/home.blade.php` (3.7, недостижим заради
  `FileViewFinder` приоритета на `themes/default/pages/home.blade.php`).

Останалото по-долу е **TODO**, чака одобрение преди промени по кода.

---

## P1 — Сигурност

| # | Проблем | Файл/метод |
|---|---------|-----------|
| 1.2 | `die("Connection failed: " . $e->getMessage())` изтича DSN/host/credentials в response | `src/Models/NthMember.php:24-28` | ✅ ГОТОВО |
| 3.2 | `AuthService` чете суров `$_REQUEST['public_uuid']`/`['msisdn']` без `input()` sanitizer и без валидация на формат | `src/Auth/AuthService.php:43,49` | ✅ ГОТОВО |
| 4.1 | `BladeEngine::renderString()` използва `eval()` — потенциален RCE ако CMS/DB съдържание мине през него | `src/BladeEngine.php:78-106` | ✅ ГОТОВО |
| 4.3 | Два паралелни front controller-а (`/.htaccess` vs `public/.htaccess`) с различни rewrite правила; `routes/assets.php` може да е dead code under Apache | `.htaccess`, `public/.htaccess`, `index.php`, `public/index.php` | ⏳ ЧАСТИЧНО — `public/` е каноничният, root `.htaccess` премахнат (виж Статус) |

**Препоръки:**
1. ~~`1.2` — замени `die()` с правилен error handling (логване, generic user-facing съобщение, без DSN/credentials в response).~~ Готово.
2. ~~`3.2` — мини auth входа (`public_uuid`/`msisdn`) през `input()` + базова формат-валидация.~~ Готово.
3. ~~`4.1` — документирай RCE риска на `renderString()`; ако не се ползва никъде активно — обмисли ограничаване/премахване на `eval()` пътя.~~ Готово — входният string се чисти от `@php`/raw PHP tags преди компилиране.
4. `4.3` — остава: реши съдбата на root `index.php` (вече недостижим за pretty URLs без `.htaccess`) и на `routes/assets.php` (вероятен dead code под `public/` + Apache static serving).

---

## P2 — "Тихи" бъгове

| # | Проблем | Файл/метод |
|---|---------|-----------|
| 1.5 | `NavItem::isActive()` никога не връща `true` (currentUrl формат не съвпада с nav url формат) | `src/Nav/NavItem.php:21-24` | ✅ ГОТОВО |
| 2.6 | `GettextTranslator` конструкторът мутира `$_SESSION['app_locale']`/`app_language` като side-effect при липсваща локализация | `src/Translations/GettextTranslator.php:18-23` |
| 3.1 | `Router::notFound()` подава 2-ри аргумент към `BladeEngine::render()`, който приема само 1 — `title` се игнорира тихо | `src/Nav/Router.php:148`, `src/BladeEngine.php:74-77` | ✅ ГОТОВО |
| 3.4 | Mixed return types в `NthMember` DB методи: `array` / `false`/`null` / string `'error'` | `src/Models/NthMember.php:132-156,179-218` |
| 3.5 | `processSubscribedNthMember()` е публичен stub, който винаги хвърля `RuntimeException` | `src/Models/NthMember.php:36-40` |
| 3.9 | Dead/странни assignments в `matchStaticRoute()`: `siteURL` презаписан, `user` е string placeholder | `src/Nav/Router.php:113-114` | ✅ ГОТОВО |
| 3.10 | `AuthJwt` catch-ва само `\Exception`, но `JWT::decode(null,...)` хвърля `\TypeError` ако `$jwt === null` | `src/Auth/AuthJwt.php:74-130` |
| 3.11 | `resolveRoute()` записва `next_page` за всеки non-special URI, вкл. `themes/...` asset requests | `src/Nav/Router.php:43-46` | ✅ ГОТОВО |

---

## P3 — Архитектура / глобално състояние

> По подразбиране **документиране** — без структурни промени без отделно одобрение.

| # | Проблем | Файл/метод |
|---|---------|-----------|
| 2.1 | Прекомерна употреба на `global`/`$GLOBALS` (`BladeEngine` сетва `$GLOBALS['translator']`/`current_theme`, Router чете `global $config`, и др.) | `init.php`, `src/BladeEngine.php:57-58`, `src/Nav/Router.php`, и др. | ⏳ ЧАСТИЧНО — `global $config` премахнат (виж Статус); остава `$GLOBALS['translator']`/`current_theme`/`$GLOBALS['blade']` |
| 2.2 | `App\Nav\Router` — God class (routing + middleware + nav refresh + 404 + config loading) | `src/Nav/Router.php` | ✅ ГОТОВО — извлечен `RouteResolver` (виж Статус) |
| 2.4 | Два различни layout контракта между `views/` и `themes/{theme}/` (header included/not included) | `views/layout/default.blade.php`, `themes/default/layout/default.blade.php`, `themes/default/errors/404.blade.php` |
| 2.5 | Inconsistent достъп до сесия — `SessionManager` съществува, но `LanguageDetector`/`GettextTranslator`/`NthMember`/`App.php` пишат `$_SESSION` директно | `src/SessionManager.php` vs няколко файла | ✅ ГОТОВО |
| 2.7 | `config/nav.php` се зарежда два пъти с fragile relative paths | `init.php:46`, `src/Nav/Router.php:135` | ✅ ГОТОВО |
| 3.3 | `input()` helper прилага sanitize-on-input с `htmlspecialchars` (double-encoding с Blade `{{ }}`) | `src/helpers/functions.php:50-59` |
| 3.8 | Два паралелни начина за достъп до JWT секрета (`SK` constant vs `env('JWT_SECRET')`) | `init.php:11`, `routes/logout.php:7`, `src/Middleware/AuthMiddleware.php:21` | ✅ ГОТОВО |
| 4.2 | Route файловете смесват controller/view/redirect логика | `routes/*.php` |
| 4.4 | Hardcoded relative paths (`__DIR__ . '/../themes/...'`) вместо централизиран конфиг | `src/Nav/Router.php:68,135`, `routes/assets.php:5`, `src/BladeEngine.php:48` | ✅ ГОТОВО |

---

## P4 — Dead code / почистване

| # | Проблем | Файл/метод | Действие |
|---|---------|-----------|---------|
| 2.3 | `NthMember` God object + 5-6 "CMS page" getter метода, грепвани 0 пъти извън дефиницията | `src/Models/NthMember.php` | Документирай, не премахвай без съгласие |
| 2.8 | Дублиран/мъртъв `__()` helper: `src/helpers/translator.php` (used) vs `src/translator.php` (dead) | `src/translator.php` | ✅ ГОТОВО — премахнат |
| 2.9 | `__globals.php` — orphan файл с конфликтен `const SK = 'very_very_secret_key'` | `__globals.php` (root, untracked) | ✅ ГОТОВО — изтрит |
| 3.7 | `views/pages/home.blade.php` недостижим (theme версията винаги печели) | `views/pages/home.blade.php` | ✅ ГОТОВО — изтрит |

---

## Ред на изпълнение

1. **P1 — Сигурност** (1.2 ✅, 3.2 ✅, 4.1 ✅, 4.3 ⏳ частично)
2. **P2 — Тихи бъгове** (1.5 ✅, 3.1 ✅, 3.9 ✅, 3.11 ✅; остават 2.6, 3.4, 3.5, 3.10)
3. **P3 — Архитектурно документиране** (2.2 ✅, 2.5 ✅, 2.7 ✅, 3.8 ✅, 4.4 ✅; остават 2.1 частично, 2.4, 3.3, 4.2) — без структурни промени без отделно одобрение
4. **P4 — Почистване** (2.8 ✅, 2.9 ✅, 3.7 ✅; остава 2.3 — документирай/реши съдбата на `NthMember` page getters)

**Чакам одобрение на този план преди да правя промени по кода.**
