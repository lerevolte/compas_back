# Project: Compas — Laravel Backend

## Stack
- Laravel Framework 9.52
- PHP 8.2.15
- БД: MySQL
- Очереди: database driver
- Тесты: PHPUnit

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

### Структура public/ (КРИТИЧНО для понимания)

public/
├── index.php              ← Laravel, НЕ трогать
├── .htaccess              ← Laravel, НЕ трогать
├── robots.txt, favicon.ico
├── landing/               ← рендерится Laravel через Blade, ТРОГАТЬ через resources/views
├── index2.html            ← мастер-шаблон SPA от Nuxt (генерится при деплое)
├── _nuxt/                 ← бандлы Nuxt, ПЕРЕЗАПИСЫВАЮТСЯ при деплое — НЕ ТРОГАТЬ
├── auth/index.html        ← SPA-точка входа (копия index2.html)
├── logistic/index.html    ← SPA-точка входа
├── roles/index.html       ← ...
├── profile/, settings/, analytics/, tariffs/, trash/, users/, external/


Все `index.html` в SPA-папках идентичны (копии index2.html), Nuxt сам
маршрутизирует по URL уже на клиенте.

### Что отсюда следует для Laravel-роутов

routes/web.php должен:
- Отдавать Blade-шаблоны ТОЛЬКО для лендинга (/, /о-нас, /цены и т.п.)
- НЕ перехватывать URL-ы /auth, /logistic, /roles, /profile, /settings,
  /analytics, /tariffs, /trash, /users, /external — это территория Nuxt
- Возможен fallback на index.html для глубоких SPA-роутов (если есть Route::fallback)

routes/api.php:
- ВСЕ эндпоинты для фронта
- Префикс /api автоматический

## Команды
- Тесты: `php artisan test` или `./vendor/bin/phpunit`
- Линт: `./vendor/bin/pint` (если установлен — иначе скажи в ответе)
- Миграции: `php artisan migrate`, `php artisan migrate:rollback`
- Локально: `php artisan serve`
- Очереди: `php artisan queue:work`
- Очистка: `php artisan optimize:clear`, `php artisan route:clear`
- Список роутов: `php artisan route:list`

## Конвенции (применять для НОВОГО кода, существующий не переписываем без задачи)

Сейчас в проекте смешанные подходы — это исторически. Для всего нового:

1. **API-ответы — через API Resources** (app/Http/Resources/).
   Не возвращать модели напрямую из контроллера. Resource даёт контракт
   с фронтом и контроль над тем, что утекает наружу.

2. **Валидация — через FormRequest** (app/Http/Requests/).
   Не использовать $request->validate([...]) в контроллере для чего-то
   сложнее одного поля.

3. **Бизнес-логика — в Service-классы** (app/Services/).
   Контроллер: принять запрос → вызвать сервис → вернуть Resource.
   Никакой бизнес-логики и работы с БД напрямую в контроллере.

4. **При работе с существующим контроллером**, который не следует этим
   правилам: НЕ рефакторить попутно. Сделать задачу в текущем стиле файла
   и спросить, нужен ли отдельный рефакторинг-PR.

## Что НЕ делать

- НЕ редактировать файлы в public/ кроме: index.php, .htaccess, robots.txt, favicon.ico
- НЕ трогать public/_nuxt/, public/index2.html, public/{auth,logistic,roles,profile,settings,analytics,tariffs,trash,users,external}/ — перезаписывается deploy.sh из фронт-репо
- НЕ удалять public/landing/ — это Laravel-территория, но через Blade в resources/views
- НЕ применять синтаксис Laravel 10+
- НЕ запускать migrate:fresh, db:wipe, migrate --force на проде
- НЕ менять JSON-форму API без явного указания, что фронт обновлён согласованно
- НЕ коммитить .env, storage/logs/*, storage/framework/cache/*

## Перед коммитом

1. `./vendor/bin/pint` (если есть) или хотя бы PSR-12 вручную
2. `php artisan test`
3. Conventional commits: feat/fix/refactor/chore/docs

## История: SPA-роуты, которые знает Nuxt

При добавлении нового SPA-раздела на фронте нужно ОБНОВИТЬ deploy.sh
во фронт-репо (там захардкоден список папок). И, возможно, обновить
Route::fallback в этом репо.

Текущий список: auth, logistic, roles, profile, settings, analytics,
tariffs, trash, users, external.