# Framework Upgrade Plan

## 0. Статус на изпълнението (2026-06-14)

Следните P0 точки вече са адресирани (ad-hoc, по заявка на потребителя в
рамките на отделна Vite/Svelte задача):

- **1.1** — `NthMember::__construct()` вече сочи към `config/db.php`
  (`__DIR__ . '/../../config/db.php'`), който вече съществува в репото и е
  env-driven (`DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASSWORD` от `.env`,
  `charset` hardcoded `utf8mb4`). Не се създаваше нов `src/assets/config/face.php`
  както предлагаше плана по-долу — `config/db.php` вече покриваше нуждата.
- **1.4 (частично)** / **3 item 5** — `src/Auth/LoginInterface.php`,
  `src/Auth/DefaultLoginHandler.php` и `'handler'` ключът в `config.php` бяха
  orphan scaffold без consumer (реалният login flow минава изцяло през
  `AuthMiddleware` → `AuthService`, MSISDN/UUID + JWT). Премахнати с
  `git rm`. Остава решение само за username/password формата в
  `views/pages/login.blade.php` (виж 1.3/3.6) — отделен въпрос от premахнатата
  абстракция.
- **3.13** — пресъздадена е `svelte/` source директорията
  (`svelte/components.js` с `import.meta.glob(..., { eager: true })`,
  `svelte/svelte-load-all.js`, `svelte/svelte-all.js`, плюс примерен
  `svelte/components/example-widget.svelte`). `vite.config.js` обновен:
  добавен `server` блок за HMR (`localhost:5173`) и
  `compilerOptions.compatibility.componentApi = 4` за обратна съвместимост
  със Svelte 3/4 Component API. `npm run build`/`npm run dev` вече работят;
  `themes/default/layout/default.blade.php` зарежда `@@vite/client` +
  `svelte/svelte-all.js` в dev и статичния `all.js` в production (превключва
  на `$app_env`, нов global, присвоен в `init.php`).

Останалите точки по-долу остават **TODO**, чакат решение по отворените
въпроси (1.3, 1.4 и т.н.).

---

> Базиран на `docs/architecture-review.md` (2026-06-12) и кръстосан анализ
> срещу `E:\laragon\www\facedesign` (2026-06-13).
>
> **Извод от кръстосания анализ:** `facedesign` е по-стар fork на engine.
> Повечето "framework" класове там (`SessionManager`, `LanguageDetector`,
> `GettextTranslator`, `App`, `BladeEngine`, `Nav/Router` го няма въобще)
> предхождат подобренията, направени по-късно в engine. С две малки
> изключения (Секция 2), **engine е по-напредналата кодова база** — не
> обратното. Затова Секция 3 не предлага масови премахвания заради
> "facedesign не го ползва" — нещата, които facedesign не ползва, най-често
> просто не са съществували при неговото форкване.

---

## 1. Бъг фиксове (от architecture-review.md)

Приоритизация (P0 = блокираща функционалност → P4 = dead-code почистване).
Колоната "facedesign" показва какво открихме при кръстосаната проверка.

### P0 — Счупена функционалност

| # | Проблем | Файл/метод | facedesign | Статус |
|---|---------|-----------|------------|--------|
| 1.1 | Production auth е fatal — `NthMember::__construct()` сочи към `src/assets/config/face.php`, който не съществува | `src/Models/NthMember.php:25` | facedesign има реален `assets/config/face.php` (от `src/nthMember.php` пътят е `__DIR__/../assets/config/face.php` = `facedesign/assets/config/face.php`). При engine `__DIR__` е `src/Models`, значи трябва `src/assets/config/face.php`. **Не копираме credentials на facedesign** — нов файл, env-driven (виж план по-долу) | ✅ DONE — пренасочено към съществуващия env-driven `config/db.php` |
| 1.3 | Регистрирани routes без файлове (`profile`, `single`, `terms`, `subscribe`) + nav линкове без routes (`/settings`, `/billing`) | `router.php`, `config/nav.php` | facedesign има `routes/profile.php` и `routes/terms.php`, но: `profile.php` сочи към `pages.profile`, view което **не съществува дори в facedesign** (низкокачествен stub — не го копираме). `terms.php` е генерична 2-реда обвивка (`assign('title', …); display('pages.terms')`) + view с изцяло site-specific (MBX/Швейцария) правен текст. `single`/`subscribe` ги няма в facedesign въобще. | TODO |
| 1.4 | Username/Password login flow напълно нереализиран; `DefaultLoginHandler`/`LoginInterface` никога не се инстанцират | `routes/login.php`, `config.php:10`, `src/Auth/DefaultLoginHandler.php` | facedesign проверява `$_SERVER['REQUEST_METHOD'] === 'POST'` (engine не го прави — частичен plus), но реалният POST handler **игнорира подадените credentials** и просто пуска `AuthService::handle()` (MSISDN/UUID flow). Истинската username/password проверка е в мъртъв, закоментиран `dummyValidate()` блок. `LoginInterface`/`DefaultLoginHandler` нямат аналог в facedesign — никога не са били адаптирани. Няма "чист" reference implementation за копиране. | TODO |
| 3.13 | Vite build entry (`svelte/svelte-all.js`) не съществува → `npm run build`/`dev` гърмят | `vite.config.js:15` | Не проверено срещу facedesign (отделен frontend стек) | ✅ DONE — пресъздадена `svelte/` source директория, `npm run build`/`dev` работят (вкл. HMR) |

### P1 — Сигурност

| # | Проблем | Файл/метод | facedesign | Статус |
|---|---------|-----------|------------|--------|
| 1.2 | `die("Connection failed: " . $e->getMessage())` — изтича DSN/host/credentials в response | `src/Models/NthMember.php:24-28` | Идентичен бъг и в `src/nthMember.php:26-28` — неоправен и там (но latent, защото `face.php` съществува и connect-ва успешно) | TODO |
| 3.2 | `AuthService` чете суров `$_REQUEST['public_uuid']`/`['msisdn']` без `input()` sanitizer и без валидация на формат | `src/Auth/AuthService.php:43,49` | Същият бъг, неоправен. Engine версията е по-напред в друг аспект (има JWT-restore-from-session branch, facedesign не го има) | TODO |
| 4.1 | `BladeEngine::renderString()` използва `eval()` — потенциален RCE ако CMS/DB съдържание мине през него | `src/BladeEngine.php:78-106` | facedesign няма `renderString()` въобще (postdates fork) — методът е engine-специфично разширение | TODO |
| 4.3 | Два паралелни front controller-а (`/.htaccess` vs `public/.htaccess`) с различни rewrite правила; `routes/assets.php` може да е dead code under Apache | `.htaccess`, `public/.htaccess`, `index.php`, `public/index.php` | Не проверено срещу facedesign | TODO |

### P2 — "Тихи" бъгове

| # | Проблем | Файл/метод | facedesign | Статус |
|---|---------|-----------|------------|--------|
| 1.5 | `NavItem::isActive()` никога не връща `true` (currentUrl формат не съвпада с nav url формат) | `src/Nav/NavItem.php:21-24` | Не приложимо — facedesign няма `Nav/Router`/`NavBuilder` | TODO |
| 2.6 | `GettextTranslator` конструкторът мутира `$_SESSION['app_locale']`/`app_language` като side-effect при липсваща локализация | `src/Translations/GettextTranslator.php:18-23` | НЕ присъства — facedesign-ската версия е по-старата, само хвърля `Exception` при липсващ `.mo`/`.po` (без fallback, без session мутация). Бъгът е въведен в engine ПОСЛЕ форка | TODO |
| 3.1 | `Router::notFound()` подава 2-ри аргумент към `BladeEngine::render()`, който приема само 1 — `title` се игнорира тихо | `src/Nav/Router.php:148`, `src/BladeEngine.php:74-77` | Аналогичен pattern: facedesign-ското `public/index.php` прави същия "broken-arg" `render('errors.404', ['title' => ...])` call, инлайн вместо в Router клас | TODO |
| 3.4 | Mixed return types в `NthMember` DB методи: `array` / `false`/`null` / string `'error'` | `src/Models/NthMember.php:132-156,179-218` | Идентичен pattern byte-for-byte в `src/nthMember.php` — наследен, неоправен | TODO |
| 3.5 | `processSubscribedNthMember()` е публичен stub, който винаги хвърля `RuntimeException` | `src/Models/NthMember.php:36-40` | facedesign версията е различна, но също нефункционална: празно тяло (`//@toDO za posle!`), тих no-op връщащ `null`. И двете неимплементирани — engine версия е "loud" (хвърля), facedesign "silent" | TODO |
| 3.6 | `$errors` се ползва в login темплейтите, но никога не се присвоява от `routes/login.php` | `views/pages/login.blade.php:14`, `routes/login.php` | facedesign-ското `login.php` POST handler прави `$errors[] = __('Invalid username or password.')`, но не е потвърдено дали се подава към Blade с `assign('errors', $errors)` — вероятно същият проблем | TODO |
| 3.9 | Dead/странни assignments в `matchStaticRoute()`: `siteURL` презаписан, `user` е string placeholder | `src/Nav/Router.php:113-114` | Аналогичен inline pattern в facedesign-ското `public/index.php` (`global $blade; $blade->assign('siteURL', ...)`/`assign('user', ...)`) — наследен pattern, просто не е изолиран в Router клас | TODO |
| 3.10 | `AuthJwt` catch-ва само `\Exception`, но `JWT::decode(null,...)` хвърля `\TypeError` ако `$jwt === null` | `src/Auth/AuthJwt.php:74-130` | Същият риск в `src/authJwt.php`. Engine е леко по-напред — `_getJwt()` прави `$_COOKIE[...] ?? null`, facedesign го прави без `?? null` (undefined-key warning, но същия краен TypeError риск) | TODO |
| 3.11 | `resolveRoute()` записва `next_page` за всеки non-special URI, вкл. `themes/...` asset requests | `src/Nav/Router.php:43-46` | Не приложимо — facedesign няма тази router логика | TODO |

### P3 — Архитектура / глобално състояние

| # | Проблем | Файл/метод | facedesign | Статус |
|---|---------|-----------|------------|--------|
| 2.1 | Прекомерна употреба на `global`/`$GLOBALS` (`BladeEngine` сетва `$GLOBALS['translator']`/`current_theme`, Router чете `global $config`, и др.) | `init.php`, `src/BladeEngine.php:57-58`, `src/Nav/Router.php`, и др. | Присъства и в facedesign — `BladeEngine` сетва същите `$GLOBALS` идентично (`:60-61`), `public/index.php` отваря с `global $blade;`. Arguably по-зле, защото е инлайн в front controller-а | TODO (документирай) |
| 2.2 | `App\Nav\Router` — God class (routing + middleware + nav refresh + 404 + config loading) | `src/Nav/Router.php` | Не приложимо — facedesign няма този клас (по-стар front controller подход) | TODO (документирай) |
| 2.4 | Два различни layout контракта между `views/` и `themes/{theme}/` (header included/not included) | `views/layout/default.blade.php`, `themes/default/layout/default.blade.php`, `themes/default/errors/404.blade.php` | Не проверено срещу facedesign | TODO (документирай) |
| 2.5 | Inconsistent достъп до сесия — `SessionManager` съществува, но `LanguageDetector`/`GettextTranslator`/`NthMember`/`App.php` пишат `$_SESSION` директно | `src/SessionManager.php` vs няколко файла | По-зле в facedesign: `init.php:128-129` пише `$_SESSION['lang']`/`['locale']` (различни keys от `app_locale` конвенцията), `App::__construct` (`src/App.php:14-19`) прави `session_start()` и чете `$_SESSION['locale']`/`$_REQUEST['lang']` директно — трета паралелна конвенция | TODO (документирай) |
| 2.7 | `config/nav.php` се зарежда два пъти с fragile relative paths | `init.php:46`, `src/Nav/Router.php:135` | Не приложимо — facedesign няма `config/nav.php`/`Nav/Router` | TODO (документирай) |
| 3.3 | `input()` helper прилага sanitize-on-input с `htmlspecialchars` (double-encoding с Blade `{{ }}`) | `src/helpers/functions.php:50-59` | Не проверено директно, но `AuthService` в двата проекта **не** ползва `input()` за auth входа (виж 3.2) | TODO (документирай) |
| 3.8 | Два паралелни начина за достъп до JWT секрета (`SK` constant vs `env('JWT_SECRET')`) | `init.php:11`, `routes/logout.php:7`, `src/Middleware/AuthMiddleware.php:21` | Не проверено директно. Забележка извън обхвата: facedesign-ското `globals.php`/`init.php` дефинират `const SK = 'very_very_secret_key'` като **hardcoded placeholder**, не env-driven — facedesign-специфичен проблем, не наша грижа тук | TODO (документирай) |
| 4.2 | Route файловете смесват controller/view/redirect логика | `routes/*.php` | Не проверено директно — facedesign-ските route файлове следват същия "all-in-one" thin-wrapper pattern (`assign()` + `display()`), но без сериозен анализ | TODO (документирай) |
| 4.4 | Hardcoded relative paths (`__DIR__ . '/../themes/...'`) вместо централизиран конфиг | `src/Nav/Router.php:68,135`, `routes/assets.php:5`, `src/BladeEngine.php:48` | Идентичен pattern в facedesign (`src/BladeEngine.php:51`, `public/index.php` route table) — наследен непроменен | TODO (документирай) |

### P4 — Dead code / почистване

| # | Проблем | Файл/метод | facedesign | Статус |
|---|---------|-----------|------------|--------|
| 2.3 | `NthMember` God object + 5-6 "CMS page" getter метода, грепвани 0 пъти извън дефиницията | `src/Models/NthMember.php` | Същите `Get*Page` методи съществуват и в `src/nthMember.php`, потвърдено **дефинирани, никога извикани** в facedesign също — наистина мъртъв код, наследен от общ предшественик | TODO (документирай, не премахвай без съгласие) |
| 2.8 | Дублиран/мъртъв `__()` helper: `src/helpers/translator.php` (used) vs `src/translator.php` (dead) | `src/translator.php` | Идентична ситуация в facedesign — двата файла с byte-identical `__()`, същия dead-duplicate | TODO (опрости — премахни `src/translator.php`) |
| 2.9 | `__globals.php` — orphan файл с конфликтен `const SK = 'very_very_secret_key'` | `__globals.php` (root, untracked) | facedesign-ският еквивалент (`globals.php`) **НЕ е orphan** — активно се `require`-ва от `public/index.php` и носи hardcoded SK placeholder, която там реално се ползва като JWT secret (facedesign-специфичен проблем, отделен от engine). В engine файлът остава orphan/dead | TODO (документирай / реши дали да се изтрие или да се migrира съдържанието) |
| 3.7 | `views/pages/home.blade.php` недостижим (theme версията винаги печели) | `views/pages/home.blade.php` | Не проверено директно | TODO (документирай) |
| 3.12 | Mojibake коментар в `SessionManager::destroy()` | `src/SessionManager.php:19` | **facedesign има правилния кирилски коментар** (`// Изтриване на сесийния cookie, ако съществува`) — директно копируем 1-реден fix | TODO (поправи — виж Секция 2) |

---

## 2. Features за портване от facedesign → engine

> Кратка секция — кръстосаният анализ показа, че engine е по-напредналата
> кодова база. Само две конкретни, генерични находки си заслужават.

| # | Feature/Pattern | Произход (файл в facedesign) | Бележки |
|---|----------------|------------------------------|---------|
| 1 | Поправен (некоригиран) кирилски коментар в `SessionManager::destroy()` | `src/SessionManager.php:19` | Покрива P4/3.12 по-горе. Тривиален 1-реден текстов fix, нулев риск |
| 2 | `site_var($key)` helper + `@sitevar` Blade директива + `config/site_vars.php` — locale-keyed config lookup с `default` fallback за CMS-ish текстове (телефон/имейл/цена/правен текст по държава) | `src/helpers/functions.php:65-78`, `src/BladeEngine.php:41-43`, `config/site_vars.php` | **Механизмът** (helper + директива + locale-fallback масив) е генеричен и engine няма еквивалент за такъв тип конфигурируем, локал-специфичен текст. **Съдържанието** на `site_vars.php` е site-specific — портваме само механизма + празен/примерен `config/site_vars.php` schema, не реалните данни на facedesign |

---

## 3. Framework features, неизползвани в facedesign (за документиране/опростяване)

> **Важно:** "неизползвано в facedesign" тук означава предимно "добавено в
> engine след форкването на facedesign", не "dead weight". По подразбиране
> препоръката е **Документирай** — реално премахване само за елементи, които
> architecture-review вече маркира като мъртъв код (2.8, и евентуално 1.4
> неизползваните абстракции).

| # | Feature | Причина за неизползване | Действие |
|---|---------|------------------------|---------|
| 1 | `App\Nav\Router` / `NavBuilder` / `config/nav.php` (динамична навигация, middleware pipeline) | facedesign е форкнат преди тези класове да съществуват — има свой статичен route table в `public/index.php` | Документирай (engine е по-напред; запази) |
| 2 | `LanguageDetector::persist()`/session-override логика, `GettextTranslator` fallback-to-`en` | По-стара версия в facedesign предхожда тези подобрения | Документирай (engine е по-напред; запази) |
| 3 | `BladeEngine::renderString()` (eval-базиран dynamic Blade) | facedesign никога не е имал нужда от CMS dynamic-content рендиране | Документирай — но виж 4.1 (P1): ако остане, трябва ограничен достъп/санитизация преди да бъде свързан с реално DB съдържание |
| 4 | `AuthService::restoreSessionFromJwt()` / JWT-restore-from-session branch | facedesign-ската `AuthService` е по-стара версия без този branch (и има латентен bug: вика стар `getMemberData()` вместо `getMemberDataByUuid()` — facedesign-специфичен проблем, извън scope) | Документирай (engine е по-напред; запази) |
| 5 | `src/Auth/LoginInterface.php` + `src/Auth/DefaultLoginHandler.php` | Никога не са инстанцирани в engine (1.4), и нямат аналог в facedesign — изцяло недовършена абстракция без consumer | ✅ DONE — премахнати заедно с `'handler'` ключа в `config.php` (виж Секция 0) |
| 6 | `src/translator.php` (дублиран `__()`) | Дубликат на `src/helpers/translator.php`, потвърдено мъртъв и в двата проекта | Премахни (покрива P4/2.8) |

---

## 4. Редът на изпълнение

1. **P0 — Счупена функционалност** (блокира production)
   1. ~~`3.13` — оправи Vite entry~~ ✅ DONE (виж Секция 0)
   2. ~~`1.1` — db config~~ ✅ DONE — пренасочено към `config/db.php` (виж Секция 0)
   3. `1.3` — добави `routes/terms.php` (тънка обвивка по facedesign модела) + нов `pages/terms.blade.php` с engine-собствен правен текст (не копирай MBX/Швейцария текста). За `profile`/`single`/`subscribe` — реши дали да се премахнат от `router.php`/`config/nav.php` или да се изградят истински (нужно е решение от потребителя)
   4. `1.4` — решение: или довърши `DefaultLoginHandler` + POST handling в `routes/login.php`, или премахни мъртвата username/password UI (форма + `LoginInterface`/`DefaultLoginHandler`) и остани само с MSISDN/UUID flow — нужно е решение от потребителя

2. **P1 — Сигурност**
   1. `1.2` — замени `die()` с правилен error handling (логване, generic user-facing съобщение, без DSN/credentials в response)
   2. `3.2` — мини auth входа (`public_uuid`/`msisdn`) през `input()` + базова формат-валидация
   3. `4.1` — документирай RCE риска на `renderString()`; ако не се ползва никъде активно — обмисли ограничаване/премахване на `eval()` пътя
   4. `4.3` — реши кой front controller е "истинският" (`/.htaccess`+root `index.php` vs `public/`), премахни/коригирай другия и `routes/assets.php` ако е dead code

3. **Портване от facedesign** (Секция 2)
   1. Поправи `SessionManager::destroy()` коментар (3.12) — копирай кирилския текст от facedesign
   2. Портирай `site_var()`/`@sitevar` механизма + schema на `config/site_vars.php` (без facedesign данни)

4. **P2 — Тихи бъгове** (1.5, 2.6, 3.1, 3.4, 3.5, 3.6, 3.9, 3.10, 3.11)

5. **P3 — Архитектурно документиране** (2.1, 2.2, 2.4, 2.5, 2.7, 3.3, 3.8, 4.2, 4.4) — без структурни промени без отделно одобрение

6. **P4 — Почистване**
   - Премахни `src/translator.php` (2.8)
   - Документирай/реши съдбата на `__globals.php` (2.9), `views/pages/home.blade.php` (3.7), `NthMember` page getters (2.3), `LoginInterface`/`DefaultLoginHandler` (зависи от 1.4)

---

## Бележки извън scope (само за информация, не пипаме facedesign)

- facedesign-ският `globals.php`/`init.php` ползва hardcoded `const SK = 'very_very_secret_key'` като JWT secret (не env-driven) — реален security проблем, но е facedesign-специфичен.
- facedesign-ската `AuthService.php:74` вика стар `getMemberData($uuid)` вместо `getMemberDataByUuid()` — латентен bug, facedesign-специфичен.

**Чакам одобрение на този план преди да правя промени по кода.**
