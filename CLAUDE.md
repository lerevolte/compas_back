# Project: Compas — Laravel Backend

## Stack
- Laravel Framework 9.52
- PHP 8.2.15
- БД: MySQL
- Очереди: database driver
- Тесты: PHPUnit
- Vite (laravel-vite-plugin) для сборки лендинга/Blade-части
- Модули: nWidart/laravel-modules

⚠️ ВАЖНО: это Laravel 9, НЕ 10/11.
- Middleware регистрируется в app/Http/Kernel.php (НЕ bootstrap/app.php)
- Старая структура bootstrap, есть HTTP/Console Kernel
- НЕ применять синтаксис Laravel 10+
- Анонимные миграции поддерживаются — используем их для новых

## Архитектура: гибрид Laravel + Nuxt SSG

Этот Laravel выполняет ТРИ роли:
1. JSON API для фронта (routes/api.php, под /api)
2. Server-side рендеринг ЛЕНДИНГА через Blade (routes/web.php)
3. Хост для статики Nuxt (закрытая часть приложения)

Фронт на Nuxt 3.18 живёт в отдельном репо:
https://github.com/lerevolte/compas_f
Локально: ../compas/

## Структура кода

### app/ — основное место для всего нового кода

Базовые контроллеры, сервисы, модели, ресурсы, FormRequest'ы:
- app/Http/Controllers/
- app/Http/Resources/
- app/Http/Requests/
- app/Services/
- app/Models/

ПРАВИЛО ПО УМОЛЧАНИЮ: новый код пишется в app/.

### Modules/ — legacy, в основном НЕ используется

В Modules/ исторически лежат папки разных модулей (Bitrix24, Logistic,
Wialon, Gibdd, Osago, Chat, Instructions, Journal, Products, и др.).

⚠️ Большинство этих модулей сейчас НЕАКТИВНЫ или не используются.
modules_statuses.json показывает только формальный статус — он НЕ
гарантирует, что код модуля реально подключён к работающей системе.

КАК ОПРЕДЕЛИТЬ, активен ли модуль:
- Если из app/ есть ссылки на классы модуля (use Modules\X\...) → активен
- Если только модуль ссылается сам на себя → скорее всего мёртвый код

ПРАВИЛО:
1. По умолчанию новый код кладётся в app/, НЕ в Modules/.
2. Прежде чем что-либо менять в Modules/[Name]/ или класть туда код:
   - Сначала проверить grep'ом, ссылается ли app/ на этот модуль
   - Если ссылок нет — модуль мёртвый, не трогаем
   - Если ссылки есть — модуль живой, можно работать
3. Не угадывать по названию задачи ("про ОСАГО → Modules/Osago"). Название
   модуля может совпадать с тематикой, но сам модуль может быть нерабочим.
4. В сомнениях — спросить.

Не создавать новые модули в Modules/.

## resources/
- views/ — Blade-шаблоны (лендинг, админка через Blade)
- css/app.css, js/app.js — точки входа Vite
- lang/ — переводы

## Структура public/ (КРИТИЧНО)

public/
├── index.php              ← Laravel, НЕ трогать
├── .htaccess              ← Laravel, НЕ трогать
├── robots.txt, favicon.ico
├── build/                 ← артефакт Vite-сборки (Blade), .gitignore
├── landing/               ← сторонняя статика, кладётся руками — НЕ ТРОГАТЬ
├── index2.html            ← мастер-шаблон SPA от Nuxt — НЕ ТРОГАТЬ
├── _nuxt/                 ← бандлы Nuxt из фронт-репо — НЕ ТРОГАТЬ
├── auth/, logistic/, roles/, profile/, settings/, analytics/,
    tariffs/, trash/, users/, external/  ← SPA-папки Nuxt — НЕ ТРОГАТЬ

ВАЖНО: public/build/ (от Vite) и public/_nuxt/ (от Nuxt-репо) — это
РАЗНЫЕ артефакты от разных сборок. Не путать.

### Что отсюда следует для Laravel-роутов

routes/web.php:
- Blade-шаблоны лендинга (/, /о-нас, /цены и т.п.)
- НЕ перехватывать /auth, /logistic, /roles, /profile, /settings,
  /analytics, /tariffs, /trash, /users, /external — это территория Nuxt
- Возможен Route::fallback

routes/api.php + Modules/[Name]/Routes/api.php:
- ВСЕ эндпоинты для фронта
- Префикс /api автоматический

## Команды
- Тесты: `php artisan test` или `./vendor/bin/phpunit`
- Линт: `./vendor/bin/pint` (если установлен)
- Миграции: `php artisan migrate`, `php artisan migrate:rollback`
- Миграции модуля: `php artisan module:migrate [Name]`
- Список модулей: `php artisan module:list`
- Локально: `php artisan serve`
- Очереди: `php artisan queue:work`
- Vite dev: `npm run dev`
- Vite билд: `npm run build`
- Очистка: `php artisan optimize:clear`
- Роуты: `php artisan route:list`

## Конвенции (для НОВОГО кода)

В существующем коде подходы смешанные — это исторически. Для всего нового:

1. **JSON-ответы — через `response()->json([...])` напрямую**, согласно
   стилю проекта. API Resources в `app/` не используются (папки
   `app/Http/Resources/` нет), новых не заводим без явной просьбы.
   Модели напрямую из контроллера всё равно не возвращаем — собираем
   нужную форму массивом и отдаём через `response()->json(...)`.

2. **Валидация — через FormRequest**
   (app/Http/Requests/ или Modules/[Name]/Http/Requests/).
   Не использовать $request->validate([...]) в контроллере для сложной валидации.

3. **Бизнес-логика — в Service-классы**
   (app/Services/ или Modules/[Name]/Services/).
   Контроллер: принять FormRequest → вызвать сервис → вернуть Resource.

4. **При работе с существующим контроллером**, который не следует этим
   правилам: НЕ рефакторить попутно. Сделать задачу в текущем стиле файла
   и спросить, нужен ли отдельный рефакторинг-PR.

## Что НЕ делать

- НЕ редактировать файлы в public/ кроме: index.php, .htaccess, robots.txt, favicon.ico
- НЕ трогать public/_nuxt/, public/index2.html, public/landing/,
  public/{auth,logistic,roles,profile,settings,analytics,tariffs,trash,users,external}/
- НЕ путать public/build/ (Vite, локальный артефакт) и public/_nuxt/ (от фронт-репо)
- НЕ создавать новые модули в Modules/ без явной просьбы
- НЕ угадывать к какому модулю относится задача — спрашивать
- НЕ применять синтаксис Laravel 10+
- НЕ запускать migrate:fresh, db:wipe, migrate --force
- НЕ менять JSON-форму API без проверки фронта (../compas/)
- НЕ рефакторить существующий код "попутно" без явной просьбы
- НЕ коммитить .env, storage/logs/*, nohup.out, worker.log, test.json
- НЕ класть новый код в Modules/ по умолчанию — основное место это app/
- НЕ полагаться на modules_statuses.json как на источник истины об активности модуля
- НЕ редактировать модули, на которые не ссылается app/

## Перед коммитом

1. `./vendor/bin/pint` (если есть) или PSR-12 вручную
2. `php artisan test`
3. Conventional commits: feat/fix/refactor/chore/docs

## SPA-роуты Nuxt (для справки)

deploy.sh во фронт-репо знает разделы:
auth, logistic, roles, profile, settings, analytics, tariffs, trash, users, external

При добавлении нового SPA-раздела на фронте — обновляется и deploy.sh,
и, возможно, Route::fallback здесь.
