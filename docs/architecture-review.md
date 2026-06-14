# Архитектурен Review

> Дата: 2026-06-12
> Обхват: целият codebase (PHP backend, Blade views/themes, routing, auth, i18n)
> Статус: **само документация на проблеми — без предложени решения**

---

## 1. КРИТИЧНИ — Липсваща/счупена production функционалност

### 1.1 Production auth path е счупен (fatal при инстанциране)
**Файл:** `src/Models/NthMember.php:25`, `src/Auth/Providers/NthProvider.php`, `src/Middleware/AuthMiddleware.php`

В production (`config['env'] !== 'development'`) `AuthMiddleware` инстанцира `NthProvider`, чийто конструктор създава `NthMember`, който в конструктора прави `DatabaseFactory::getConnection(__DIR__ . '/../assets/config/face.php')`. Файлът `src/assets/config/face.php` **не съществува никъде в репото**. `require` на несъществуващ файл е fatal error → целият auth middleware (а значи и всеки route с `'middleware' => ['auth']`) гръмва веднага в production.

### 1.2 `die()` вместо error handling в Model слоя
**Файл:** `src/Models/NthMember.php:24-28`

```php
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```
Освен че `die()` е напълно неконсистентен начин за грешка (вижда се по-долу, че другите DB методи връщат `'error'` стринг, а не умират), `$e->getMessage()` за PDO connection грешка обикновено съдържа DSN/host/credentials детайли — изтичане на чувствителна информация директно в response тялото.

### 1.3 Регистрираните routes без файлове / nav линкове без routes
**Файлове:** `router.php`, `config/nav.php`

- `router.php` регистрира `'profile' => routes/profile.php`, `'single' => routes/single.php`, `'terms' => routes/terms.php`, `'subscribe' => landing/mobixon/index.php` — **никой от тези файлове не съществува**. `matchStaticRoute()` ще извика `notFound()` (за регистрираните, но липсващи файлове) или ще гръмне с "Routing error" (за special routes варианта).
- `config/nav.php` сочи и към `/settings` и `/billing`, които **дори не са регистрирани** в `$routes` в `router.php` — друг вид "счупен линк" (генерален 404 вместо специфичен).

Резултат: половината навигация на сайта води до 404/грешки.

### 1.4 Username/Password login flow е напълно нереализиран
**Файлове:** `views/pages/login.blade.php`, `routes/login.php`, `config.php:10`, `src/Auth/DefaultLoginHandler.php`, `src/Auth/LoginInterface.php`

- `config.php` декларира `'handler' => '\App\Auth\DefaultLoginHandler'`, съществува `LoginInterface` и `DefaultLoginHandler::attempt()` — но **нищо в кода не четe тази config стойност и не инстанцира handler-а**. Грепнах целия проект — `DefaultLoginHandler` се появява само в собствения си файл и в интерфейса.
- `views/pages/login.blade.php` (десктоп форма с username/password) прави `POST /login`, но `routes/login.php` само показва формата (винаги GET-логика, без проверка на `$_SERVER['REQUEST_METHOD']`) — POST заявка просто ще пререндира същата страница без да направи нищо.
- В резултат тази форма е "dead end" — цяла фийча, за която съществуват interface/handler/view, но няма свързваща логика.

### 1.5 `NavItem::isActive()` никога не връща `true`
**Файлове:** `src/Nav/NavItem.php:21-24`, `init.php:46`, `src/Nav/Router.php:134-141`, `views/partials/header.blade.php:19,31,47`

`isActive()` прави `rtrim($currentUrl,'/') === rtrim($this->url,'/')`. Но `$currentUrl`, който се подава на `NavBuilder`, е:
- от `init.php`: `$fullUrl` → пълен `"https://host"` (без path),
- от `Router::refreshNavAndAuth()`: `$this->route` → slug без водещ `/`, например `"profile"`.

Nav items имат `url => '/profile'`. Нито `"https://host" === "/profile"`, нито `"profile" === "/profile"` могат да бъдат истина — `active` CSS клас никога не се поставя, независимо от текущата страница.

---

## 2. АРХИТЕКТУРНИ ПРОБЛЕМИ

### 2.1 Прекомерна употреба на `global` / `$GLOBALS`
**Файлове:** `init.php`, `src/BladeEngine.php:57-58`, `src/Nav/Router.php` (навсякъде `global $config`), `src/Middleware/AuthMiddleware.php:15`, `routes/assets.php:2` (`require __DIR__.'/../config.php'` директно), `routes/set-lang.php:2` (`global $detector`), `src/helpers/translator.php` (`global $translator`)

`BladeEngine` — клас от view слоя — има странична задача да сетва `$GLOBALS['translator']` и `$GLOBALS['current_theme']`, които после се консумират от helper функции (`__()`, `theme_asset()`) напълно извън неговия контрол. `App\Nav\Router` чете `global $config` вътре в private метод `resolveRoute()`. Това прави всеки компонент скрито зависим от глобално мутируемо състояние, инициализирано в строго определен ред в `init.php` — невъзможно тестване в изолация, лесно за разпад при промяна на реда.

### 2.2 `App\Nav\Router` — God class
**Файл:** `src/Nav/Router.php`

Един клас отговаря за: route resolution + regex/special routes + static routes + middleware execution + презареждане на nav/auth state + 404 рендиране + директно `require` на конфигурационни файлове с hardcoded relative пътища (`__DIR__ . '/../../config/nav.php'` и `__DIR__ . '/../routes/map.php'`). Смесва routing logic с presentation logic (`$this->blade->assign('user', $this->route . '/account/')` — виж 3.9) и с конфигурационно зареждане.

### 2.3 `NthMember` — God object с тотален микс на конвенции
**Файл:** `src/Models/NthMember.php`

Един клас комбинира: PDO connection management, CMS-съдържание (privacy policy, terms, about, contact, subscription management — 5 различни "page" getter-а), member lookup (по msisdn/uuid), action logging, и redirect helper (`redirectNotmember`). Имената на методите смесват три конвенции в един файл: `GetPrivacyPolicy` (PascalCase), `_get_page_by_name_and_country_id` (snake_case private), `getNthMemberByPublicUuid` (camelCase public). Освен това всичките "CMS page" методи (`GetPrivacyPolicy`, `GetTermsAndConditionsPage`, `GetContactUsPage`, `GetAboutPage`, `GetSubscriptionManagementPage`, `GetTermsAndConditions`) се грепват **нула пъти** извън дефиницията си — мъртъв код, който прилича на жива функционалност.

### 2.4 Два различни "layout контракта" между `views/` и `themes/{theme}/`
**Файлове:** `views/layout/default.blade.php` (включва `@include('partials.header')`), `themes/default/layout/default.blade.php` (НЕ включва header — очаква страницата сама да го направи), `themes/default/pages/home.blade.php` (сам включва header), `themes/default/errors/404.blade.php` (extends layout.default, НЕ включва header)

Заради `FileViewFinder` приоритета (`themes/{theme}` преди `views/`), `layout.default` резолвва различно съдържание според кой layout файл "победи", и двете версии имат различен контракт спрямо partial-а за header. `themes/default/errors/404.blade.php` extend-ва `layout.default` (→ theme версията, без header) и сам не включва header → 404 страницата излиза без navigation, докато home страницата го има. Несъгласуваност в UI, скрита зад routing на view resolver-а.

### 2.5 Inconsistent достъп до сесия
**Файлове:** `src/SessionManager.php` (абстракция), но `src/LanguageDetector.php:25,34-36`, `src/Translations/GettextTranslator.php:20-21`, `src/Models/NthMember.php:160`, `src/App.php:14` пишат/четат `$_SESSION` директно

Има `SessionManager` като единна точка за сесия, но половината класове го игнорират и пипат `$_SESSION` суперглобала директно — две паралелни конвенции за едно и също нещо.

### 2.6 Translator класа мутира глобална сесия като side-effect
**Файл:** `src/Translations/GettextTranslator.php:18-23`

Конструктор на `GettextTranslator` (чисто "translation service") при липсваща локализация пише `$_SESSION['app_locale'] = 'en_US'; $_SESSION['app_language'] = 'en';` — скрит side-effect, който променя глобалното състояние на потребителската сесия от вътрешността на нещо, което изглежда like a pure read-only service.

### 2.7 Дублирано зареждане на `config/nav.php` с fragile relative paths
**Файлове:** `init.php:46`, `src/Nav/Router.php:135`

`config/nav.php` се прочита и парсва два пъти на всеки заявка с middleware/static route — веднъж в `init.php` (резултатът после се презаписва), и пак в `Router::refreshNavAndAuth()`. Втория require използва `__DIR__ . '/../../config/nav.php'` от `src/Nav/Router.php` — крехка релативна пътека, обвързана с точната дълбочина на директорийната структура, без `file_exists` проверка (fatal при евентуално преместване на файла).

### 2.8 Дублиран/мъртъв код за `__()` helper-а
**Файлове:** `src/helpers/translator.php`, `src/translator.php`

Идентично съдържание (`function __(...)`), но само `src/helpers/translator.php` се `require_once`-ва от `src/App.php`. `src/translator.php` е изцяло мъртъв дубликат.

### 2.9 `__globals.php` — orphan файл с конфликтна конфигурация
**Файл:** `__globals.php` (root, untracked)

Не се `require`-ва никъде, но дефинира `const SK = 'very_very_secret_key'` — различна стойност от `define('SK', $_ENV['JWT_SECRET'])` в `init.php`. Ако някога бъде включен, `const SK` отново → fatal "Cannot redeclare constant". Освен това съдържа собствена host-detection/multisite логика (`$our`, `$bp`, `$current_site`), която overlap-ва концептуално с `LanguageDetector` — неясно дали е изоставен експеримент или предстояща миграция.

---

## 3. ПРОБЛЕМИ В РЕАЛИЗАЦИЯТА

### 3.1 `Router::notFound()` подава аргумент, който `BladeEngine::render()` не приема
**Файлове:** `src/Nav/Router.php:148`, `src/BladeEngine.php:74-77`

```php
// Router.php
echo $this->blade->render('errors.404', ['title' => __('Page Not Found')]);
// BladeEngine.php
public function render(string $template): string {
    return $this->factory->make($template, $this->data)->render();
}
```
`render()` приема само 1 параметър — вторият масив тихо се игнорира (PHP не хвърля грешка за extra args). `'title' => __('Page Not Found')` никога не достига до Blade данните → `@yield('title', ...)` пада на layout default-а ("Engine !"), не на "Page Not Found". Тих, незабележим bug.

### 3.2 Неконсистентна санитизация на auth входа
**Файл:** `src/Auth/AuthService.php:43,49`

```php
if (!empty($_REQUEST['public_uuid'])) {
    $this->loginWithUuid($_REQUEST['public_uuid']);
}
if (!empty($_REQUEST['msisdn'])) {
    $this->loginWithMsisdn($_REQUEST['msisdn']);
}
```
Чете суров `$_REQUEST` директно, без `input()` helper-а (който е специално написан за това и се ползва другаде). Никаква type/format валидация на `msisdn`/`public_uuid` преди да отидат в DB lookup методи.

### 3.3 `input()` helper-ът прилага "sanitize-on-input" с `htmlspecialchars`
**Файл:** `src/helpers/functions.php:50-59`

```php
if ($sanitize) {
    $item = htmlspecialchars(trim($item), ENT_QUOTES, 'UTF-8');
}
```
Това е sanitize-on-input антипатерн: легитимни стойности съдържащи `&`, `'`, `<` биват HTML-encode-нати ПРЕДИ да стигнат до бизнес логиката/DB. Тъй като Blade `{{ }}` вече escape-ва на output, резултатът е double-encoding (`&amp;#039;`), а данните, записани в DB, са вече "замърсени" с HTML entities.

### 3.4 Mixed return types в `NthMember` DB методите
**Файл:** `src/Models/NthMember.php:132-156, 179-218`

Методите връщат `array` (success), `false`/`null` (no rows), или **string `'error'`** (PDOException) — три различни "失败" семантики в едно `mixed` връщано значение. `NthProvider::getMemberDataByUuid()` (`src/Auth/Providers/NthProvider.php:28`) проверява `$member === 'error'`, но всеки друг caller трябва да помни да направи същата string-сравнение — лесно за пропускане, особено защото `'error'` стрингът минава и проверки като `if ($member)` (truthy непразен string).

### 3.5 Недовършен/stub метод, изложен публично
**Файл:** `src/Models/NthMember.php:36-40`

```php
public function processSubscribedNthMember(string $msisdn): void {
    throw new \RuntimeException('processSubscribedNthMember() is not implemented yet.');
}
```
Публичен метод, който при извикване гарантирано хвърля uncaught exception (никой не го catch-ва никъде в кода) → 500.

### 3.6 `$errors` променлива, използвана в шаблоните, никога не се присвоява
**Файлове:** `views/pages/login.blade.php:14`, `views/pages/mobile-login.blade.php:14`, `routes/login.php`

И двата login темплейта правят `@if(!empty($errors)) ... @endif`, но `routes/login.php` (единственото място, което рендира тези view-та) никога не присвоява `errors` чрез `$blade->assign(...)`. Блокът е винаги "мъртъв" (тих, защото `empty()` на undefined variable не хвърля warning) — индикация за наполовина изградена error-display фийча.

### 3.7 `views/pages/home.blade.php` е недостижим при default theme
**Файлове:** `views/pages/home.blade.php`, `themes/default/pages/home.blade.php`, `routes/home.php`

И двата файла дефинират `pages.home`. Заради `FileViewFinder` приоритета (`themes/default` преди `views`), и понеже `config.php`-default theme **е** `default`, `themes/default/pages/home.blade.php` ("Home Page - theme") винаги печели. `views/pages/home.blade.php` ("Default Home Page - when no theme!" + отделен `console.log`) е напълно недостижим мъртъв код, който визуално подсказва, че трябва да е fallback — но никога не се рендира при текущата конфигурация.

### 3.8 Два различни начина за достъп до JWT секрета
**Файлове:** `init.php:11` (`define('SK', $_ENV['JWT_SECRET'])`), `routes/logout.php:7` (`new AuthJwt(SK)`), `src/Middleware/AuthMiddleware.php:21` (`new AuthJwt(env('JWT_SECRET'))`)

И двете трябва да сочат към същата стойност, но кодът поддържа два паралелни механизма за получаване на "същия" секрет — constant vs helper function — без явна причина.

### 3.9 Странно/dead assignment в `Router::matchStaticRoute()`
**Файл:** `src/Nav/Router.php:113-114`

```php
$this->blade->assign('siteURL', $this->route . '/');
$this->blade->assign('user', $this->route . '/account/');
```
`siteURL` тук презаписва глобалната пълна URL (зададена в `init.php` като `https://host`) със стойност като `"profile/"` — семантично несъвместимо с името `siteURL` навсякъде другаде. `user` присвоено на стринг като `"profile/account/"` (не е реален user обект/масив) изглежда като placeholder/leftover код.

### 3.10 `AuthJwt` catch-ва само `\Exception`, но `JWT::decode(null, ...)` хвърля `\TypeError`
**Файл:** `src/Auth/AuthJwt.php:74-77, 89-98, 100-130`

`decode()`, `isExpired()`, `getInfo()` всички разчитат, че `$this->jwt` вече е string (зададен от `_getJwt()` чрез `haveJwt()`). Те `catch (\Exception $e)`, но ако `decode()` бъде извикан, докато `$this->jwt === null`, `JWT::decode(null, ...)` хвърля `TypeError` (extends `\Error`, не `\Exception`) — НЕ се хваща от тези catch блокове → uncaught fatal. В момента се избягва само заради конвенцията "винаги викай `haveJwt()` първо", което не е enforced от типа на класа.

### 3.11 `Router::resolveRoute()` записва `next_page` за всеки non-special URI
**Файл:** `src/Nav/Router.php:43-46`

```php
if (!in_array($uri, $special)) {
    SessionManager::set($next, $uri);
}
```
Това включва пътища, започващи с `themes/...` (asset requests, ако изобщо стигнат до PHP) — `next_page` може да бъде презаписан с asset URL преди да се прецени route='assets'. Edge case без explicit изключение за asset routes.

### 3.12 `SessionManager::destroy()` съдържа mojibake коментар
**Файл:** `src/SessionManager.php:19`

```php
// ????????? ?? ???????? cookie, ??? ??????????
```
Индикация за encoding inconsistency на файла спрямо останалата (валидна) кирилица в проекта — потенциален проблем при по-нататъшна обработка/diff на файла.

### 3.13 Vite build entry не съществува
**Файл:** `vite.config.js:15`

`rollupOptions.input` сочи `path.resolve('./svelte/svelte-all.js')` — директорията `svelte/` не съществува в repo-то. `npm run build` / `npm run dev` ще гръмнат веднага.

---

## 4. DESIGN ПРОБЛЕМИ

### 4.1 `BladeEngine::renderString()` използва `eval()`
**Файл:** `src/BladeEngine.php:78-106`

```php
$php = $bladeCompiler->compileString($string);
ob_start();
extract($data, EXTR_SKIP);
eval('?>' . $php);
```
Самата конструкция е fragile (грешка по средата → `ob_end_clean()` и rethrow, но output buffer state управление около eval е чувствително). По-важно — методът е проектиран да рендира динамичен (вероятно от БД, напр. CMS текстове) Blade string. В комбинация с `NthMember`-ите "page" getter методи (2.3, виж по-горе — макар да са мъртви в момента), ако някога съдържание от БД мине през `renderString()`, всеки `@php ... @endphp` блок в съхраненото съдържание би се изпълнил като PHP — потенциален RCE вектор, ако content management даде на нетехнически потребители контрол върху тия полета.

### 4.2 Route файловете смесват контролер логика, view рендиране и redirect-и
**Файлове:** `routes/*.php`

Всеки route файл е "all-in-one" скрипт: глобален достъп до `$blade`/`$config`/`$detector`, директни `header()` redirect-и, inline бизнес логика (напр. `routes/set-lang.php`, `routes/signin.php`). Няма разделение между "контролер" решение и "view" рендиране — всяка промяна на flow изисква редактиране на смесен procedural script.

### 4.3 Два паралелни front controller-а с различни rewrite правила
**Файлове:** `.htaccess` (root), `public/.htaccess`, `index.php` (root), `public/index.php`

Root `.htaccess` изключва статични extension-и (`css|js|png|...`) от rewrite и проверява `-f`/`-d` преди да подаде към `index.php`. `public/.htaccess` няма тази extension-изключваща секция и вместо `[QSA,L]` rewrite към `index.php`, прави `index.php?page=$1` (a `$_GET['page']` параметър, който никъде не се чете от router-а — router-ът разчита на `REQUEST_URI`, не на `$_GET['page']`). Двата входни пътя водят до `router.php`, но с различна семантика на rewrite — неясно кой е "истинският" production entry point, и `routes/assets.php` (PHP-базирано сервиране на theme assets с MIME map и path-traversal проверки) може да се окаже изцяло dead code under Apache, защото root `.htaccess` вече сервира статичните файлове директно.

### 4.4 Hardcoded relative paths навсякъде вместо конфигурируеми базови пътища
**Файлове:** `src/Nav/Router.php:68,135`, `routes/assets.php:5` (`realpath(__DIR__ . '/../themes/' . $theme)`), `src/BladeEngine.php:48` (`__DIR__ . "/../themes/{$theme}"`)

Множество класове изчисляват пътя до `themes/`/`config/` чрез верига от `__DIR__ . '/../...'`, всеки базиран на собствената си позиция в директорийното дърво. Преместване на който и да е от тези файлове е silent breaking change (без compile-time проверка) — `config.php` вече дефинира `views_dir`/`cache_dir`/`lang_dir` като абсолютни пътища, но `themes_dir` и `config_dir` не са централизирани по същия начин.
