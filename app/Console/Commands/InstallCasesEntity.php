<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Устанавливает сущность «Кейсы» (cases) в центральную БД (admin_compas_main) —
 * полный клон сущности «Статьи» (articles), но без категорий.
 *
 * Что ставит:
 *   - таблицу cases (CREATE TABLE ... LIKE articles);
 *   - data_types        — сущность cases (модель App\Models\Cases);
 *   - field_sections    — клоны разделов articles (page = cases);
 *   - data_rows         — клоны полей articles, кроме category_id;
 *   - field_values      — клоны вариантов статусов/палитры по каждому полю;
 *   - sidebar_items     — пункт «Кейсы» (если таблица есть);
 *   - settings(menu)    — меню вкладок карточки (клон articles).
 *
 * Команда ИДЕМПОТЕНТНА. Запуск на сервере центрального портала:
 *   php artisan entity:install-cases
 */
class InstallCasesEntity extends Command
{
    protected $signature = 'entity:install-cases
        {--recreate-table : пересоздать таблицу cases (DROP + CREATE), иначе только создать если нет}';

    protected $description = 'Установить сущность «Кейсы» (cases) в центральную БД по образцу «Статей»';

    public function handle(): int
    {
        $db = \DB::connection();

        $src = $db->table('data_types')
            ->where('slug', 'articles')->orWhere('name', 'articles')
            ->first();
        if (!$src) {
            $this->error('Сущность articles не найдена в текущей БД — команда рассчитана на центральную БД (admin_compas_main)');
            return self::FAILURE;
        }

        if ($this->option('recreate-table')) {
            $db->statement('DROP TABLE IF EXISTS `cases`');
        }
        $db->statement('CREATE TABLE IF NOT EXISTS `cases` LIKE `articles`');

        $oldTypeIds = $db->table('data_types')
            ->where('name', 'cases')->orWhere('slug', 'cases')
            ->pluck('id');
        if ($oldTypeIds->isNotEmpty()) {
            $oldRowIds = $db->table('data_rows')->whereIn('data_type_id', $oldTypeIds)->pluck('id');
            if ($oldRowIds->isNotEmpty()) {
                $db->table('field_values')->whereIn('field_id', $oldRowIds)->delete();
            }
            $db->table('data_rows')->whereIn('data_type_id', $oldTypeIds)->delete();
            $db->table('data_types')->whereIn('id', $oldTypeIds)->delete();
        }
        $db->table('field_sections')->where('page', 'cases')->delete();
        if (\Schema::hasTable('sidebar_items')) {
            $db->table('sidebar_items')->where('slug', 'cases')->delete();
        }
        $db->table('settings')->where('entity', 'cases')->where('type', 'menu')->delete();

        $now = now();

        $typeRow = (array) $src;
        unset($typeRow['id']);
        $typeRow['name'] = 'cases';
        $typeRow['slug'] = 'cases';
        $typeRow['slug_singular'] = 'case';
        $typeRow['title_singular'] = 'Кейс';
        $typeRow['title_plural'] = 'Кейсы';
        $typeRow['model_name'] = 'App\\Models\\Cases';
        $typeRow['created_at'] = $now;
        $typeRow['updated_at'] = $now;
        $typeId = $db->table('data_types')->insertGetId($typeRow);

        $sectionMap = [];
        $sections = $db->table('field_sections')->where('page', 'articles')->orderBy('id')->get();
        foreach ($sections as $section) {
            $arr = (array) $section;
            $oldId = $arr['id'];
            unset($arr['id']);
            $arr['page'] = 'cases';
            $arr['created_at'] = $now;
            $arr['updated_at'] = $now;
            $sectionMap[$oldId] = $db->table('field_sections')->insertGetId($arr);
        }
        foreach ($sectionMap as $oldId => $newId) {
            $parentId = $sections->firstWhere('id', $oldId)->parent_id ?? null;
            if ($parentId && isset($sectionMap[$parentId])) {
                $db->table('field_sections')->where('id', $newId)->update(['parent_id' => $sectionMap[$parentId]]);
            }
        }

        $rowMap = [];
        $rows = $db->table('data_rows')->where('data_type_id', $src->id)->orderBy('id')->get();
        foreach ($rows as $row) {
            if ($row->field == 'category_id' || $row->relation_table == 'blog_categories') {
                continue;
            }
            $arr = (array) $row;
            $oldId = $arr['id'];
            unset($arr['id']);
            $arr['data_type_id'] = $typeId;
            if ($arr['section_id'] && isset($sectionMap[$arr['section_id']])) {
                $arr['section_id'] = $sectionMap[$arr['section_id']];
            }
            $newId = $db->table('data_rows')->insertGetId($arr);
            $rowMap[$oldId] = $newId;

            $values = $db->table('field_values')->where('field_id', $oldId)->orderBy('id')->get();
            foreach ($values as $value) {
                $varr = (array) $value;
                unset($varr['id']);
                $varr['field_id'] = $newId;
                $db->table('field_values')->insert($varr);
            }
        }
        foreach ($rows as $row) {
            if (isset($rowMap[$row->id]) && $row->group_id && isset($rowMap[$row->group_id])) {
                $db->table('data_rows')->where('id', $rowMap[$row->id])->update(['group_id' => $rowMap[$row->group_id]]);
            }
        }

        if (\Schema::hasTable('sidebar_items')) {
            $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
            $db->table('sidebar_items')->insert([
                'created_at' => $now, 'updated_at' => $now,
                'name' => 'Кейсы', 'slug' => 'cases',
                'sort' => 0, 'link' => '/objects/cases',
                '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                'is_hidden' => 0, 'enabled' => 1,
            ]);
        }

        $menu = $db->table('settings')
            ->where('entity', 'articles')->where('type', 'menu')
            ->orderByRaw('user_id is not null')->orderBy('id')
            ->first();
        if ($menu) {
            $db->table('settings')->insert([
                'key' => $menu->key,
                'display_name' => $menu->display_name,
                'value' => $menu->value,
                'type' => 'menu',
                'entity' => 'cases',
                'user_id' => null,
            ]);
        }

        if (class_exists(\App\Models\Settings::class)) {
            try { \App\Models\Settings::clear_cache(); } catch (\Throwable $e) {}
        }
        Cache::forget(\App\Http\Controllers\SpaController::ALLOWED_URLS_CACHE_KEY);

        $this->info("Готово: data_type={$typeId}, полей ".count($rowMap).', разделов '.count($sectionMap));

        return self::SUCCESS;
    }
}
