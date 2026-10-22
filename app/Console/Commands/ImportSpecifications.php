<?php

namespace App\Console\Commands;

use App\Models\Specification;
use App\Models\Tenant;
use App\Services\ShipmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportSpecifications extends Command
{
    protected $signature = 'specifications:import
        {file : JSON-выгрузка из 1С (naim, id_1c, sostav[B24_ID, kolvo])}
        {target=avixo : <tenant_id>}
        {--dry-run : только показать сопоставление}';

    protected $description = 'Загрузить спецификации и их состав из выгрузки 1С; код 1С пишется в поле «Код спецификации»';

    public const SLUG = 'specifications';
    public const FIELD = 'code_1c';
    public const TITLE = 'Код спецификации';

    public function handle(): int
    {
        $specs = $this->parse((string) $this->argument('file'));
        if ($specs === null) {
            return self::FAILURE;
        }
        $this->line('В файле спецификаций: ' . count($specs) . ', строк состава: ' . array_sum(array_map(fn ($s) => count($s['sostav']), $specs)));

        $tenant = Tenant::find((string) $this->argument('target'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }
        $tenant->run(fn () => $this->import($specs, (bool) $this->option('dry-run')));

        return self::SUCCESS;
    }

    private function parse(string $file): ?array
    {
        if (!is_file($file)) {
            $this->error("Файл {$file} не найден");
            return null;
        }
        $raw = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($file)));
        $body = preg_replace('/,\s*(?="naim"\s*:)/u', '},{', substr($raw, 1, -1));
        $items = json_decode('[{' . $body . '}]', true);
        if (!is_array($items)) {
            $this->error('Не удалось разобрать файл: ' . json_last_error_msg());
            return null;
        }
        $specs = [];
        foreach ($items as $item) {
            if (!isset($item['naim'], $item['id_1c'])) {
                continue;
            }
            $specs[] = [
                'naim' => trim((string) $item['naim']),
                'id_1c' => trim((string) $item['id_1c']),
                'sostav' => is_array($item['sostav'] ?? null) ? $item['sostav'] : [],
            ];
        }

        return $specs;
    }

    private function import(array $specs, bool $dryRun): void
    {
        if (!Schema::hasTable(self::SLUG) || !DB::table('data_types')->where('slug', self::SLUG)->exists()) {
            $this->error('На портале нет сущности «Спецификации»');
            return;
        }
        if (!$dryRun) {
            $this->ensureField();
        }

        $b24Ids = [];
        $names = [];
        foreach ($specs as $spec) {
            $names[] = mb_strtolower($spec['naim']);
            foreach ($spec['sostav'] as $row) {
                $b24Ids[] = (string) ($row['B24_ID'] ?? '');
            }
        }
        $productColumns = array_values(array_filter(['id', 'name', 'id_b24', 'price', 'purchase_price', 'weight', 'volume', 'nds', 'nds_included'], fn ($c) => Schema::hasColumn('products', $c)));
        $query = DB::table('products')->orderBy('id');
        if (Schema::hasColumn('products', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        $byB24 = [];
        $byName = [];
        foreach ($query->get($productColumns) as $product) {
            $b24 = trim((string) ($product->id_b24 ?? ''));
            if ($b24 !== '' && !isset($byB24[$b24])) {
                $byB24[$b24] = $product;
            }
            $name = mb_strtolower(trim($this->text($product->name ?? '')));
            if ($name !== '' && !isset($byName[$name])) {
                $byName[$name] = $product;
            }
        }

        $existing = Schema::hasColumn(self::SLUG, self::FIELD)
            ? DB::table(self::SLUG)->whereNotNull(self::FIELD)->where(self::FIELD, '!=', '')->pluck('id', self::FIELD)->all()
            : [];

        $created = $updated = 0;
        $noProduct = [];
        $missing = [];
        foreach ($specs as $spec) {
            $finished = $byName[mb_strtolower($spec['naim'])] ?? null;
            if (!$finished) {
                $noProduct[] = $spec['naim'];
            }
            $lines = [];
            foreach ($spec['sostav'] as $row) {
                $b24 = trim((string) ($row['B24_ID'] ?? ''));
                $product = $byB24[$b24] ?? null;
                if (!$product) {
                    $missing[$b24][] = $spec['naim'];
                    continue;
                }
                $lines[] = $this->line($product, $row['kolvo'] ?? 0);
            }
            if ($dryRun) {
                continue;
            }

            $id = $existing[$spec['id_1c']] ?? null;
            $model = $id ? Specification::withTrashed()->find($id) : null;
            if ($model && $model->trashed()) {
                $model->restore();
            }
            $model = $model ?: new Specification();
            $isNew = !$model->exists;
            $model->name = $spec['naim'];
            $model->{self::FIELD} = $spec['id_1c'];
            if ($finished) {
                $model->finished_product_id = Schema::hasColumn(self::SLUG, 'finished_product_id') ? json_encode([(int) $finished->id]) : null;
            }
            $model->save();
            $model->setProducts($lines);
            $isNew ? $created++ : $updated++;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Создано: {$created}, обновлено: {$updated}");
        $this->line('Без готовой продукции (нет товара с таким названием): ' . count($noProduct));
        foreach (array_slice($noProduct, 0, 30) as $name) {
            $this->line('  - ' . $name);
        }
        $this->line('Материалов не найдено по B24_ID: ' . count($missing));
        foreach (array_slice($missing, 0, 30, true) as $b24 => $where) {
            $this->line("  - B24_ID {$b24} (в " . count($where) . ' спецификац.)');
        }

        if (!$dryRun) {
            DB::table('local_cache')->where('url', 'like', 'objects/' . self::SLUG . '%')->update(['updated_at' => now()]);
        }
    }

    private function line(object $product, $count): array
    {
        $count = is_numeric($count) ? $count + 0 : 0;
        $line = [
            'id' => (int) $product->id,
            'name' => $this->text($product->name ?? ''),
            'price' => $product->price ?? null,
            'purchase_price' => $product->purchase_price ?? null,
            'count' => $count,
            'weight' => $product->weight ?? null,
            'volume' => $product->volume ?? 0,
            'nds' => $this->first($product->nds ?? null),
            'nds_included' => $this->first($product->nds_included ?? null),
        ];
        $parts = ShipmentService::lineParts($line, 'price');
        $line['sum'] = round($parts['sum'], 2);
        $line['nds_sum'] = round($parts['vat'], 2);
        $line['total'] = round($parts['gross'], 2);

        return $line;
    }

    private function ensureField(): void
    {
        if (!Schema::hasColumn(self::SLUG, self::FIELD)) {
            DB::statement('ALTER TABLE `' . self::SLUG . '` ADD COLUMN `' . self::FIELD . '` VARCHAR(64) NULL');
        }
        $typeId = DB::table('data_types')->where('slug', self::SLUG)->value('id');
        $attrs = ['type' => 'text', 'title' => self::TITLE, 'is_remove' => 0, 'hide' => 0, 'only_read' => 0, 'is_plural' => 0];
        $row = DB::table('data_rows')->where('data_type_id', $typeId)->where('field', self::FIELD)->first();
        if ($row) {
            DB::table('data_rows')->where('id', $row->id)->update($attrs);
        } else {
            $src = DB::table('data_rows')->where('data_type_id', $typeId)->where('field', 'name')->first();
            $data = $src ? (array) $src : [];
            unset($data['id']);
            $sectionId = DB::table('field_sections')->where('page', self::SLUG)
                ->where(fn ($q) => $q->whereNull('module')->orWhere('module', ''))
                ->orderBy('sort')->value('id');
            $data = array_merge($data, $attrs, [
                'data_type_id' => $typeId,
                'field' => self::FIELD,
                'section_id' => $sectionId,
                'group_id' => null,
                'module' => '',
                'module_section_id' => null,
                'required' => 0,
                'is_permanent' => 0,
                'permanent_name' => 0,
                'permanent_required' => 0,
                'is_default' => 0,
                'details' => '{}',
                'relation_table' => null,
                'sort' => (int) DB::table('data_rows')->where('data_type_id', $typeId)->max('sort') + 1,
            ]);
            $data = array_intersect_key($data, array_flip(Schema::getColumnListing('data_rows')));
            DB::table('data_rows')->insert($data);
        }
        DB::table('local_cache')->where('url', 'fields/' . self::SLUG)->update(['updated_at' => now()]);
        \App\Models\Settings::clear_cache();
    }

    private function text($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded['value'] ?? ($decoded[0] ?? '');
            }
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function first($value)
    {
        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? ($decoded[0] ?? null) : $value;
        }

        return is_array($value) ? ($value[0] ?? null) : $value;
    }
}
