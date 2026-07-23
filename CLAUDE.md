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

## Команды (обновлено)

- Тесты: `php artisan test` или `./vendor/bin/phpunit`
- Линт: `./vendor/bin/pint` (если установлен)
- **Central-миграции**: `php artisan migrate`
- **Tenant-миграции**: `php artisan tenants:migrate`
- **Откат тенантских миграций**: `php artisan tenants:migrate-rollback`
- **Список тенантов**: `php artisan tenants:list`
- **Команда для конкретного тенанта**: `php artisan tenants:run [command] --tenants=UUID`
- Локально: `php artisan serve`
- Очереди: `php artisan queue:work`
- Vite dev: `npm run dev`, Vite билд: `npm run build`
- Очистка: `php artisan optimize:clear`
- Роуты: `php artisan route:list`

## ⚠️ Multi-tenant архитектура (stancl/tenancy)

Это multi-tenant приложение. КАЖДЫЙ клиент = отдельная БД (тенант).

### Два набора миграций

1. **database/migrations/** — central БД (одна на всё приложение)
   Содержит: users (админы), tenants, domains, voyager-таблицы,
   permissions, settings, общие справочники.

2. **database/migrations/tenant/** — тенантские БД (одна на каждого клиента)
   Содержит: бизнес-данные клиента — chat_messages, journal_records,
   employees, cars, mileages, products, и т.д.

### Команды миграций — РАЗНЫЕ для разных БД

- `php artisan migrate` → ТОЛЬКО для central БД
  Применяет миграции из database/migrations/ (не из tenant/)

- `php artisan tenants:migrate` → для ВСЕХ тенантов
  Применяет миграции из database/migrations/tenant/ ко всем существующим тенантам

- `php artisan tenants:migrate --tenants=UUID` → для одного тенанта

- Новый тенант создаётся через регистрацию портала, и при создании
  автоматически применяются миграции из database/migrations/tenant/

### ⚠️ Куда класть новые миграции

Если фича работает с данными клиента (счета, журналы, машины, чаты,
сотрудники, что угодно "про работу клиента") → миграция в
**database/migrations/tenant/**

Если фича работает с central-данными (новый тип админа, общий справочник,
конфиг приложения) → миграция в **database/migrations/**

В сомнениях — СПРОСИТЬ. По умолчанию: tenant.

### ⚠️ deploy.sh и миграции

В deploy.sh `php artisan migrate --force` гонит ТОЛЬКО central-миграции.
Это безопасно, но обычно НЕ НУЖНО (central-миграции почти не меняются).

Для тенантских миграций после деплоя — отдельная команда вручную:
ssh server "php artisan tenants:migrate --force"

Запускать только если знаешь, что добавил миграции в tenant/.

### Модели тенантов

При работе с tenant-данными в коде — нужно быть внутри tenant-контекста.
Обычно это делается через middleware или через tenancy()->initialize($tenant).
Без инициализации тенанта запросы к tenant-таблицам пойдут не туда или упадут.

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

## Динамическая система сущностей (ядро приложения)

Почти все бизнес-сущности (routes, logistic_tasks, cars, employees, companies,
accounts и т.д.) описаны ДАННЫМИ, а не кодом:

- `data_types` — сущности (slug, model_name, title_singular/plural, enable)
- `data_rows` — поля сущностей (field, type, title, is_plural, roles_read/roles_write,
  relation_table, related_field, details JSON и т.д.)
- `field_values` — варианты статусов/палитры (привязаны к data_rows.id)
- `field_sections` — разделы полей в деталке (page = slug сущности)
- `settings` — пер-пользовательские настройки (меню, колонки таблиц, секции логистики)

ВАЖНО: у поля из data_rows должна существовать физическая колонка в таблице
сущности (`Schema::hasColumn`) — иначе поле выпадает из настроек и не рендерится.
Новые поля добавляются и в data_rows, и колонкой (обычно `text nullable`).

Универсальный CRUD-поток:
- `ObjectController::compose_list/compose_show` — выдача таблицы/деталки
  (внутри `Table::get` и `EntityObject`); здесь же проверка прав (403 Forbidden)
- `CrudService::batch($slug, $rows)` — универсальное создание/обновление
  с автозаписью истории и синхронизацией relation-полей в обе стороны
- Кастомные контроллеры поверх этого: RouteController (страница /logistic),
  LogisticController (секции), AnalyticsController

### Кэш настроек (критично)

`get_settings()` / `Settings::get()` — пер-пользовательский слепок всех полей,
опций, прав; кэшируется в memcached (`{tenant}:settings-{userId}`, gzcompress).
После ЛЮБОЙ мутации data_rows / field_values / ролей обязателен
`Settings::clear_cache()` — иначе пользователи видят стейл (поля, опции, права).

### Права

- Ролевые: таблица `permissions` (role_id + entity_id из data_types,
  колонки read_p/create_p/update_p/delete_p/export_p/import_p со значениями
  N/Y/A/E). Строки автосоздаются при обращении. Паттерн проверки —
  `ObjectController::getPermissions()` (admin → всё 'A').
- Страница «Логистика» гейтится middleware-алиасом `logistic.read`
  (EnsureLogisticReadAccess, entity slug `logistic`) — подключён в
  RouteController, LogisticController и logistics_*-методах AnalyticsController.
- Полевые: data_rows.roles_read/roles_write (JSON-массив ID ролей, allow-list).
  Вычисляются в Settings::get → `$settings[slug]['perms'][field]['read'|'write']`
  (0/1, учитывается только первичная role_id). Table/EntityObject/RouteController
  фильтруют выдачу по ним. НЕ фильтровать `$settings[slug]['fields']` по ролям —
  на этом списке держится страница настроек полей.

## История и лента событий

Одна таблица `histories` (entity, entity_id, event, text, field,
old_value/new_value, is_relation, color). События: OBJECT_CREATED/COPIED/
DELETED/RESTORED, FIELD_UPDATED, RELATION_ADDED/DELETED/CHANGED.
- «История изменений» = event NULL или FIELD_UPDATED; «Лента событий» =
  остальные (History::list, параметр filter=events).
- Центральная точка автозаписи — `History::saveForObject($slug, $rows)`:
  сравнивает с текущими значениями в БД, поэтому вызывать ДО mass-update.
- Конвенция: история связей пишется В ОБЕ стороны (и владельцу, и связанному
  объекту). Для маршрутов зеркало реализовано в Route::boot (saved) и
  RouteController::update_tasks; в текстах связей использовать
  `<span data-slug='{slug}' data-id='{id}'>{name}</span>` — фронт делает ссылку.
- «Дата изменения» (updated_at) должна меняться только вместе с записью в
  историю: технические пересчёты (Route::recalculateTotals, деривативные поля)
  идут через saveQuietly()/`$model->timestamps = false`.

## Логистика (страница /logistic)

- Кастомные эндпоинты RouteController: GET/PUT `routes/{id}/tasks`,
  `map_data`, `task_filter`, `route-tasks-view/fields` (общий конфиг вкладки
  «Маршрут списком», user_id=null — виден и во внешней ссылке).
- Пробег/время маршрута считает OSRM (http://compas-osrm.ru:5000) с
  поправкой TRAFFIC_COEFFICIENT; время прибытия пишется в task.plan_time.
- Геокодинг адресов — DaData (`MapController::geocode`, ключи захардкожены);
  обратный геокодинг подставляет адрес только при точности до дома
  (data.house), иначе координаты.
- Сотрудники: у маршрута и задачи это JSON-колонка employee_id (массив ID) +
  pivot-таблицы route_employee / logistic_task_employee. При привязке задачи
  к маршруту сотрудники маршрута копируются в задачу (Task::boot), при
  откреплении — снимаются (update_tasks).

## Создание портала

`RegistrationController` → `TenantService::create`: создаёт Tenant + домен
`{id}.compas.pro`, клонирует справочники из БД-шаблона (connection `seeds`,
БД admin_seeds), создаёт админа (user id=1) в тенантской БД и запись в
central-таблице `accounts` (name, tariff, tenant_id, owner_type, phone, email),
шлёт письмо AccountRegistered. Central админ-портал — admin_compas_main.

Каталоги миграций помимо основных: `database/migrations/tenant_updates{,2}` —
точечные догоняющие миграции (гонятся TenantService::syncDatabase),
`tenant_delete` — архив, не применяется.

## Realtime

Изменения объектов/истории пушатся через event `\App\Events\ObjectUpdated`
(ObjectCreated/ObjectUpdated/ObjectDeleted/HistoryUpdated) — фронт слушает
сокеты и показывает плашки обновления таблиц.

## Прикладные artisan-команды

- `statuses:backfill {seeds|all-tenants|<tenant>}` — дефолт вместо NULL у статусов
- `accounts:backfill-email {--force}` — заполнить accounts.email из тенантов
- `logistic:sync-module-fields {target}` — поля вкладки «Модули → Логистика»
- `seeds:migrate {--pretend}` — тенантские миграции на БД-шаблоне admin_seeds
- `seeds:sync-permanent-fields {--dry-run}` — синк постоянных полей в seeds
- `entity:install-addresses / entity:install-warehouses {--recreate-table}` —
  установка сущностей «Библиотека задач» / «Быстрые задачи»
- `files:fix-orientation {--dry-run}`, `import:rebuild-file-fields`,
  `gibdd:update`, `gps:fetch`, `bitrix24:sync-payment`, `yandex:route-stats`

## Локальная среда (важно)

Локально НЕТ vendor/ и НЕТ БД: artisan и тесты не запустить. Проверка кода —
`php -l`, логика проверяется на сервере после деплоя. tests/ фактически пустые
(ExampleTest) — на прогон тестов не рассчитывать.

## Соглашение по комментариям

Новые комментарии в код НЕ добавлять (существующие исторические не трогать
массово). Пояснения — в коммит/PR, не в код.
