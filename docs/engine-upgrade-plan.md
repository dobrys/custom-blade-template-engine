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

Останалото по-долу е **TODO**, чака одобрение преди промени по кода.

---

## P1 — Сигурност

| # | Проблем | Файл/метод |
|---|---------|-----------|
| 1.2 | `die("Connection failed: " . $e->getMessage())` изтича DSN/host/credentials в response | `src/Models/NthMember.php:24-28` |
| 3.2 | `AuthService` чете суров `$_REQUEST['public_uuid']`/`['msisdn']` без `input()` sanitizer и без валидация на формат | `src/Auth/AuthService.php:43,49` |
| 4.1 | `BladeEngine::renderString()` използва `eval()` — потенциален RCE ако CMS/DB съдържание мине през него | `src/BladeEngine.php:78-106` |
| 4.3 | Два паралелни front controller-а (`/.htaccess` vs `public/.htaccess`) с различни rewrite правила; `routes/assets.php` може да е dead code under Apache | `.htaccess`, `public/.htaccess`, `index.php`, `public/index.php` |

**Препоръки:**
1. `1.2` — замени `die()` с правилен error handling (логване, generic user-facing съобщение, без DSN/credentials в response).
2. `3.2` — мини auth входа (`public_uuid`/`msisdn`) през `input()` + базова формат-валидация.
3. `4.1` — документирай RCE риска на `renderString()`; ако не се ползва никъде активно — обмисли ограничаване/премахване на `eval()` пътя.
4. `4.3` — реши кой front controller е "истинският" (`/.htaccess`+root `index.php` vs `public/`), премахни/коригирай другия и `routes/assets.php` ако е dead code.

---

## P2 — "Тихи" бъгове

| # | Проблем | Файл/метод |
|---|---------|-----------|
| 1.5 | `NavItem::isActive()` никога не връща `true` (currentUrl формат не съвпада с nav url формат) | `src/Nav/NavItem.php:21-24` |
| 2.6 | `GettextTranslator` конструкторът мутира `$_SESSION['app_locale']`/`app_language` като side-effect при липсваща локализация | `src/Translations/GettextTranslator.php:18-23` |
| 3.1 | `Router::notFound()` подава 2-ри аргумент към `BladeEngine::render()`, който приема само 1 — `title` се игнорира тихо | `src/Nav/Router.php:148`, `src/BladeEngine.php:74-77` |
| 3.4 | Mixed return types в `NthMember` DB методи: `array` / `false`/`null` / string `'error'` | `src/Models/NthMember.php:132-156,179-218` |
| 3.5 | `processSubscribedNthMember()` е публичен stub, който винаги хвърля `RuntimeException` | `src/Models/NthMember.php:36-40` |
| 3.9 | Dead/странни assignments в `matchStaticRoute()`: `siteURL` презаписан, `user` е string placeholder | `src/Nav/Router.php:113-114` |
| 3.10 | `AuthJwt` catch-ва само `\Exception`, но `JWT::decode(null,...)` хвърля `\TypeError` ако `$jwt === null` | `src/Auth/AuthJwt.php:74-130` |
| 3.11 | `resolveRoute()` записва `next_page` за всеки non-special URI, вкл. `themes/...` asset requests | `src/Nav/Router.php:43-46` |

---

## P3 — Архитектура / глобално състояние

> По подразбиране **документиране** — без структурни промени без отделно одобрение.

| # | Проблем | Файл/метод |
|---|---------|-----------|
| 2.1 | Прекомерна употреба на `global`/`$GLOBALS` (`BladeEngine` сетва `$GLOBALS['translator']`/`current_theme`, Router чете `global $config`, и др.) | `init.php`, `src/BladeEngine.php:57-58`, `src/Nav/Router.php`, и др. |
| 2.2 | `App\Nav\Router` — God class (routing + middleware + nav refresh + 404 + config loading) | `src/Nav/Router.php` |
| 2.4 | Два различни layout контракта между `views/` и `themes/{theme}/` (header included/not included) | `views/layout/default.blade.php`, `themes/default/layout/default.blade.php`, `themes/default/errors/404.blade.php` |
| 2.5 | Inconsistent достъп до сесия — `SessionManager` съществува, но `LanguageDetector`/`GettextTranslator`/`NthMember`/`App.php` пишат `$_SESSION` директно | `src/SessionManager.php` vs няколко файла |
| 2.7 | `config/nav.php` се зарежда два пъти с fragile relative paths | `init.php:46`, `src/Nav/Router.php:135` |
| 3.3 | `input()` helper прилага sanitize-on-input с `htmlspecialchars` (double-encoding с Blade `{{ }}`) | `src/helpers/functions.php:50-59` |
| 3.8 | Два паралелни начина за достъп до JWT секрета (`SK` constant vs `env('JWT_SECRET')`) | `init.php:11`, `routes/logout.php:7`, `src/Middleware/AuthMiddleware.php:21` |
| 4.2 | Route файловете смесват controller/view/redirect логика | `routes/*.php` |
| 4.4 | Hardcoded relative paths (`__DIR__ . '/../themes/...'`) вместо централизиран конфиг | `src/Nav/Router.php:68,135`, `routes/assets.php:5`, `src/BladeEngine.php:48` |

---

## P4 — Dead code / почистване

| # | Проблем | Файл/метод | Действие |
|---|---------|-----------|---------|
| 2.3 | `NthMember` God object + 5-6 "CMS page" getter метода, грепвани 0 пъти извън дефиницията | `src/Models/NthMember.php` | Документирай, не премахвай без съгласие |
| 2.8 | Дублиран/мъртъв `__()` helper: `src/helpers/translator.php` (used) vs `src/translator.php` (dead) | `src/translator.php` | Премахни `src/translator.php` |
| 2.9 | `__globals.php` — orphan файл с конфликтен `const SK = 'very_very_secret_key'` | `__globals.php` (root, untracked) | Реши: изтриване или migrиране на съдържанието |
| 3.7 | `views/pages/home.blade.php` недостижим (theme версията винаги печели) | `views/pages/home.blade.php` | Документирай |

---

## Ред на изпълнение

1. **P1 — Сигурност** (1.2, 3.2, 4.1, 4.3)
2. **P2 — Тихи бъгове** (1.5, 2.6, 3.1, 3.4, 3.5, 3.9, 3.10, 3.11)
3. **P3 — Архитектурно документиране** (2.1, 2.2, 2.4, 2.5, 2.7, 3.3, 3.8, 4.2, 4.4) — без структурни промени без отделно одобрение
4. **P4 — Почистване**
   - Премахни `src/translator.php` (2.8)
   - Документирай/реши съдбата на `__globals.php` (2.9), `views/pages/home.blade.php` (3.7), `NthMember` page getters (2.3)

**Чакам одобрение на този план преди да правя промени по кода.**
