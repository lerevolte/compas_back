<?php

namespace App\Console\Commands;

use App\Models\ObjectRelation;
use App\Models\Tenant;
use Illuminate\Console\Command;

class InstallPickupsEntity extends Command
{
    protected $signature = 'entity:install-pickups
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Установить сущность «Самовывозы» (pickups): клон задач логистики без полей доставки, вкладки «Товары и услуги», «Связанные документы», «Печать документов»';

    public const SLUG = 'pickups';
    public const MODEL = 'App\\Models\\Pickup';
    public const TITLE_SINGULAR = 'Самовывоз';
    public const TITLE_PLURAL = 'Самовывозы';

    public const EXCLUDE_FIELDS = [
        'route_id', 'employee_id', 'address', 'plan_time', 'service_time', 'car_type',
        'car_requirements', 'employee_requirements', 'delivery_price', 'point_status',
        'kommentarii_k_statusu_voditelem_3570', 'saby_waybills', 'na_priemke', 'sort', 'b24_id', 'priority',
    ];

    public const EXCLUDE_TITLES = ['Время прибытия', 'План. время прибытия', 'Планируемое время прибытия', 'На приемке', 'Комментарий к статусу водителем'];

    public const RENAME_TITLES = ['delivery_date' => 'Дата самовывоза'];

    public function handle(): int
    {
        $target = (string) $this->argument('target');

        if ($target === 'seeds') {
            $this->installInto(\DB::connection('seeds'), 'admin_seeds', false);
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->installInto(\DB::connection(), (string) $tenant->id, true));
                    $this->info("  ✓ {$tenant->id}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ {$tenant->id}: " . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        $tenant = Tenant::find($target);
        if (!$tenant) {
            $prefix = (string) config('tenancy.database.prefix', '');
            if ($prefix !== '' && str_starts_with($target, $prefix)) {
                $tenant = Tenant::find(substr($target, strlen($prefix)));
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->installInto(\DB::connection(), $target, true));
        $this->info("Готово: {$target}");

        return self::SUCCESS;
    }

    private function installInto($db, string $label, bool $inTenant): void
    {
        $sb = $db->getSchemaBuilder();
        if (!$sb->hasTable('logistic_tasks')) {
            throw new \RuntimeException('нет таблицы logistic_tasks — эталон для pickups');
        }
        $src = $db->table('data_types')->where('slug', 'logistic_tasks')->first();
        if (!$src) {
            throw new \RuntimeException('нет сущности logistic_tasks');
        }

        $db->statement('CREATE TABLE IF NOT EXISTS `' . self::SLUG . '` LIKE `logistic_tasks`');
        $now = now();

        $type = $db->table('data_types')->where('slug', self::SLUG)->first();
        $typeAttrs = [
            'name' => self::SLUG,
            'slug' => self::SLUG,
            'title_singular' => self::TITLE_SINGULAR,
            'title_plural' => self::TITLE_PLURAL,
            'model_name' => self::MODEL,
            'generate_permissions' => 1,
            'server_side' => 0,
            'updated_at' => $now,
            'color' => '#5DAE8B',
            'enable' => 1,
            'slug_singular' => 'pickup',
            'hidden' => 0,
        ];
        if ($type) {
            $db->table('data_types')->where('id', $type->id)->update($typeAttrs);
            $typeId = (int) $type->id;
        } else {
            $typeId = (int) $db->table('data_types')->insertGetId($typeAttrs + ['created_at' => $now]);
        }

        $infoSecId = $db->table('field_sections')
            ->where('page', self::SLUG)
            ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
            ->orderBy('sort')
            ->value('id');
        if (!$infoSecId) {
            $infoSecId = $db->table('field_sections')->insertGetId([
                'sort' => 0, 'name' => 'Информация', 'page' => self::SLUG,
                'created_at' => $now, 'updated_at' => $now, 'account_id' => 1, 'hide' => 0,
                'column_id' => 1, '_lft' => 0, '_rgt' => 0,
            ]);
        }

        $this->cloneDataRows($db, (int) $src->id, $typeId, (int) $infoSecId, $label);

        if (!$db->table('settings')->where(['type' => 'menu', 'entity' => self::SLUG])->exists()) {
            $db->table('settings')->insert([
                'key' => 'menu', 'display_name' => null,
                'value' => json_encode([
                    ['title' => 'Общие', 'tab' => 'order', 'sort' => 0, 'enabled' => 1, 'id' => 0],
                    ['title' => 'Товары и услуги', 'tab' => 'products', 'sort' => 1, 'enabled' => 1, 'id' => 1],
                    ['title' => 'История изменений', 'tab' => 'history', 'sort' => 2, 'enabled' => true, 'id' => 2, 'has_roles_read' => false, 'roles_read' => null],
                ], JSON_UNESCAPED_SLASHES),
                'type' => 'menu', 'entity' => self::SLUG, 'user_id' => null,
            ]);
        }

        try {
            ObjectRelation::ensureTab(self::SLUG, $db);
        } catch (\Throwable $e) {
        }

        if ($sb->hasTable('sidebar_items')) {
            $existingItem = $db->table('sidebar_items')->where('slug', self::SLUG)->first();
            if ($existingItem) {
                $db->table('sidebar_items')->where('slug', self::SLUG)
                    ->update(['enabled' => 1, 'is_hidden' => 0, 'name' => self::TITLE_PLURAL, 'updated_at' => $now]);
            } else {
                $maxRgt = (int) $db->table('sidebar_items')->max('_rgt');
                $db->table('sidebar_items')->insert([
                    'created_at' => $now, 'updated_at' => $now,
                    'name' => self::TITLE_PLURAL, 'slug' => self::SLUG,
                    'sort' => 0, 'link' => '/objects/' . self::SLUG,
                    '_lft' => $maxRgt + 1, '_rgt' => $maxRgt + 2, 'parent_id' => null,
                    'is_hidden' => 0, 'enabled' => 1,
                ]);
            }
        }

        try {
            if ($sb->hasTable('local_cache')) {
                $db->table('local_cache')->where('url', 'fields/' . self::SLUG)->update(['updated_at' => $now]);
            }
        } catch (\Throwable $e) {
        }
        if ($inTenant) {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }

        $this->line("    [{$label}] " . self::SLUG . ": data_type={$typeId}, section={$infoSecId}");
    }

    private function cloneDataRows($db, int $srcTypeId, int $typeId, int $infoSecId, string $label): void
    {
        $existing = $db->table('data_rows')->where('data_type_id', $typeId)->pluck('field')->all();

        $rows = $db->table('data_rows')
            ->where('data_type_id', $srcTypeId)
            ->where('is_remove', 0)
            ->whereNotIn('field', self::EXCLUDE_FIELDS)
            ->whereNotIn('title', self::EXCLUDE_TITLES)
            ->orderByRaw('group_id IS NULL DESC')
            ->orderBy('sort')
            ->get();

        $idMap = [];
        $added = 0;
        foreach ($rows as $row) {
            if ($row->type === 'status') {
                continue;
            }
            $arr = (array) $row;
            $oldId = $arr['id'];
            unset($arr['id']);
            if (!empty($arr['group_id'])) {
                if (!isset($idMap[$arr['group_id']])) {
                    continue;
                }
                $arr['group_id'] = $idMap[$arr['group_id']];
            }
            if (in_array($arr['field'], $existing, true)) {
                $idMap[$oldId] = (int) $db->table('data_rows')->where('data_type_id', $typeId)->where('field', $arr['field'])->value('id');
                continue;
            }
            $arr['data_type_id'] = $typeId;
            $arr['section_id'] = $infoSecId;
            $arr['module'] = '';
            $arr['module_section_id'] = null;
            $arr['related_field'] = null;
            if ($arr['field'] === 'user_id') {
                $arr['required'] = 1;
            }
            if (isset(self::RENAME_TITLES[$arr['field']])) {
                $arr['title'] = self::RENAME_TITLES[$arr['field']];
            }
            if (!empty($arr['subfields'])) {
                $sub = json_decode($arr['subfields'], true);
                $arr['subfields'] = is_array($sub)
                    ? json_encode(array_values(array_filter(array_map(fn ($i) => $idMap[$i] ?? null, $sub))))
                    : null;
            }
            $idMap[$oldId] = $db->table('data_rows')->insertGetId($arr);
            $added++;
        }

        $this->line("    [{$label}] " . self::SLUG . ": полей добавлено {$added}");
    }
}
