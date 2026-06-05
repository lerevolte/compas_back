# Как добавить новую сущность (entity) в портал

Памятка-инструкция. Если попросили «добавить сущность X» — следуй этому порядку.
Эталонный пример реализации — сущность **addresses** («Справочник адресов»),
сделанная по аналогии с **logistic_tasks**.

## Как устроены сущности (контекст)

Сущность («entity» = строка в таблице `data_types`, модель `App\Models\Entity`)
описывается набором **метаданных**, которые лежат в каждой тенант-БД:

| Таблица           | Что хранит                                                            |
|-------------------|----------------------------------------------------------------------|
| `data_types`      | саму сущность (slug, model_name, title, color, enable …)             |
| `field_sections`  | разделы полей карточки (обычно «Информация» + модульные разделы)     |
| `data_rows`       | поля сущности (field, type, title, section_id, module_section_id …)  |
| `sidebar_items`   | пункт левого меню                                                     |
| `settings(menu)`  | вкладки карточки объекта (Общие / Модули / История / …)              |
| `modules`         | (опц.) привязка сущности к модулю (entities-массив в config модуля)  |

Плюс **бизнес-таблица** под данные (например `addresses`) — это уже схема БД,
её заводит миграция.

### Откуда метаданные попадают в новые порталы

При регистрации портала `App\Services\TenantService::create()` копирует из
базы-шаблона **admin_seeds** (connection `seeds`) таблицы:
`data_rows, settings, sidebar_items, field_sections, field_values, data_types,
section_fields_sort, modules, …` (с сохранением id).

Поэтому, чтобы сущность появлялась у **новых** порталов «из коробки», её
метаданные обязательно надо положить в **admin_seeds**.

Бизнес-таблицы (`addresses` и пр.) в новые порталы попадают НЕ копированием, а
тенантскими миграциями `database/migrations/tenant/` (применяются при создании
портала и командой `php artisan tenants:migrate`).

## Порядок действий

### 1. Бизнес-таблица — тенантская миграция

`database/migrations/tenant/<date>_create_<slug>_table.php`.

- Покрывает новые порталы (применяется автоматически) и существующие
  (`php artisan tenants:migrate`).
- «Если есть — перезаписать»: можно `Schema::dropIfExists()` + `Schema::create()`
  (осторожно: уничтожает данные; для уже используемых таблиц так не делать).
- Набор колонок должен включать все `field` из `data_rows`, иначе поле не
  отрисуется (`Settings` проверяет `Schema::hasColumn`).

Пример: `..._create_addresses_table.php`.

### 2. Метаданные сущности — идемпотентная artisan-команда

Самый надёжный способ — команда, которая ставит метаданные в нужное соединение,
вычисляя свежие id (через `insertGetId`, без хардкода id → нет коллизий между
порталами) и **переустанавливая** при повторном запуске.

Эталон: `app/Console/Commands/InstallAddressesEntity.php`
(`php artisan entity:install-addresses`). Что делает команда:

1. гарантирует бизнес-таблицу (`CREATE TABLE IF NOT EXISTS`);
2. чистит прошлую установку сущности (идемпотентность);
3. вставляет `data_types` → получает `data_type_id`;
4. вставляет `field_sections` (например «Информация» + «Используемые поля в модуле»)
   → получает `section_id` и `module_section_id`;
5. вставляет `data_rows` — **клонируя поля из эталонной сущности** (у addresses —
   из `logistic_tasks`), перенаправляя `data_type_id` / `section_id` /
   `module_section_id` на новые id. Это исключает ручные ошибки в десятках флагов.
   На случай отсутствия эталона есть hardcoded-fallback;
6. вставляет `sidebar_items`;
7. вставляет `settings(type=menu, entity=<slug>)`.

Важные нюансы по полям:
- `section_id` — id раздела «Информация» из `field_sections`;
- `module_section_id` — это **JSON-массив**, например `[<id модульного раздела>]`,
  а НЕ просто число;
- `module` у `data_rows` и `field_sections` — slug модуля (например `logistic`),
  чтобы поля показывались в разделе «Используемые поля в модуле».

### 3. Фронтенд-метаданные (репозиторий ../compas)

- `entities.json` — добавить/проверить запись `{slug, title_singular,
  title_plural, color}` (title_plural идёт в заголовок таблицы `/objects/<slug>`).
- `meta.json` — заполнить `title`.

### 4. Выкатка (строго по шагам)

```bash
# 1) база-шаблон (чтобы появлялось у новых порталов)
php artisan entity:install-addresses seeds

# 2) тестовый портал — проверить руками
# ВАЖНО: указывается id портала (имя без префикса), напр. test4, а НЕ имя БД
# admin_test4. Имя БД = config('tenancy.database.prefix') + id ('admin_'+test4).
# Команда умеет отрезать префикс сама, но канонично передавать именно id.
php artisan entity:install-addresses test4

# --- ПРОВЕРКА: сущность видна, таблица открывается, карточка работает ---

# 3) все существующие порталы
php artisan tenants:migrate --force          # бизнес-таблица во всех тенантах
php artisan entity:install-addresses all-tenants
```

Команда идемпотентна: повторный прогон переустановит метаданные начисто.

## Чек-лист «ничего не потеряли»

- [ ] тенантская миграция бизнес-таблицы (новые порталы + `tenants:migrate`);
- [ ] `data_types` — сама сущность (slug, model_name, enable=1, color);
- [ ] `field_sections` — разделы («Информация» + модульный, если нужен модуль);
- [ ] `data_rows` — все поля; верные `section_id` и `module_section_id` (`[id]`);
- [ ] `sidebar_items` — пункт меню (slug, link `/objects/<slug>`, enabled=1);
- [ ] `settings(menu)` — вкладки карточки;
- [ ] модель `App\Models\<Name>` существует и `$table` совпадает со slug;
- [ ] фронт: `entities.json` + `meta.json`;
- [ ] поставить в admin_seeds → проверить на admin_test4 → раскатать на все.
