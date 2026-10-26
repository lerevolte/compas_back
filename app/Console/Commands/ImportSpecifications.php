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
        {file : JSON-выгрузка из 1С (naim, id_1c, idgp_1c, sostav[id_1c, B24_ID, kolvo])}
        {target=avixo : <tenant_id>}
        {--dry-run : только показать сопоставление}
        {--report= : путь к CSV с несопоставленными позициями}';

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
                'idgp_1c' => trim((string) ($item['idgp_1c'] ?? '')),
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

        $productColumns = array_values(array_filter(['id', 'name', 'id_b24', 'id_1c', 'price', 'purchase_price', 'weight', 'volume', 'nds', 'nds_included'], fn ($c) => Schema::hasColumn('products', $c)));
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
            $name = $this->key($this->text($product->name ?? ''));
            if ($name !== '' && !isset($byName[$name])) {
                $byName[$name] = $product;
            }
        }

        $by1c = [];
        if (Schema::hasColumn('products', 'id_1c')) {
            $q = DB::table('products')->whereNotNull('id_1c')->where('id_1c', '!=', '')->orderBy('id');
            if (Schema::hasColumn('products', 'deleted_at')) {
                $q->whereNull('deleted_at');
            }
            foreach ($q->get($productColumns) as $product) {
                $by1c[trim((string) $product->id_1c)] ??= $product;
            }
        }
        $sync = class_exists(\Modules\Bitrix24\Services\B24ProductSync::class) ? \Modules\Bitrix24\Services\B24ProductSync::make() : null;
        if (!$sync) {
            $this->warn('Битрикс24 не настроен — недостающие товары не уточнить');
        }
        $by1c = array_merge($by1c, $this->resolveMissing1c($sync, $specs, $by1c, $byB24, $dryRun));
        $resolved = $this->resolveMissing($sync, $specs, $byB24, $by1c, $byName);
        foreach ($resolved as $b24 => $product) {
            $byB24[$b24] = $product;
        }
        $material = function (array $row) use ($by1c, $byB24) {
            return $by1c[trim((string) ($row['id_1c'] ?? ''))] ?? $byB24[trim((string) ($row['B24_ID'] ?? ''))] ?? null;
        };

        $existing = Schema::hasColumn(self::SLUG, self::FIELD)
            ? DB::table(self::SLUG)->whereNotNull(self::FIELD)->where(self::FIELD, '!=', '')->pluck('id', self::FIELD)->all()
            : [];

        $created = $updated = 0;
        $noProduct = [];
        $missing = [];
        foreach ($specs as $spec) {
            $finished = ($spec['idgp_1c'] !== '' ? ($by1c[$spec['idgp_1c']] ?? null) : null) ?? ($byName[$this->key($spec['naim'])] ?? null);
            if (!$finished) {
                $noProduct[] = $spec['naim'];
            } elseif (!is_object($finished)) {
                $finished = null;
            }
            $lines = [];
            $seen = [];
            foreach ($spec['sostav'] as $row) {
                $product = $material($row);
                if (!$product) {
                    $missing[$this->rowKey($row)][] = $spec['naim'];
                    continue;
                }
                if (!is_object($product)) {
                    continue;
                }
                $dup = $product->id . '|' . (is_numeric($row['kolvo'] ?? null) ? $row['kolvo'] + 0 : 0);
                if (isset($seen[$dup])) {
                    continue;
                }
                $seen[$dup] = true;
                $lines[] = $this->productLine($product, $row['kolvo'] ?? 0);
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
        $this->line('Без готовой продукции (нет товара ни по idgp_1c, ни по названию): ' . count($noProduct));
        foreach (array_slice($noProduct, 0, 30) as $name) {
            $this->line('  - ' . $name);
        }
        $this->line('Материалов не найдено ни по id_1c, ни по B24_ID: ' . count($missing));
        foreach (array_slice($missing, 0, 30, true) as $key => $where) {
            $this->line("  - {$key} (в " . count($where) . ' спецификац.)');
        }

        $report = (string) $this->option('report');
        if ($report !== '') {
            $fh = fopen($report, 'w');
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, ['Проблема', 'Спецификация', 'Код 1С спецификации', 'Код 1С товара', 'B24_ID материала'], ';');
            foreach ($specs as $spec) {
                if (in_array($spec['naim'], $noProduct, true)) {
                    fputcsv($fh, ['Нет товара «Готовая продукция»', $spec['naim'], $spec['id_1c'], $spec['idgp_1c'], ''], ';');
                }
                foreach ($spec['sostav'] as $row) {
                    if (!$material($row)) {
                        fputcsv($fh, ['Материал не найден', $spec['naim'], $spec['id_1c'], trim((string) ($row['id_1c'] ?? '')), trim((string) ($row['B24_ID'] ?? ''))], ';');
                    }
                }
            }
            fclose($fh);
            $this->line("Отчёт: {$report}");
        }
        $this->line('Спецификаций с неполным составом: ' . count(array_filter($specs, function ($spec) use ($material) {
            foreach ($spec['sostav'] as $row) {
                if (!$material($row)) {
                    return true;
                }
            }
            return false;
        })));

        if (!$dryRun) {
            DB::table('local_cache')->where('url', 'like', 'objects/' . self::SLUG . '%')->update(['updated_at' => now()]);
        }
    }

    private function resolveMissing1c($sync, array $specs, array $by1c, array $byB24, bool $dryRun): array
    {
        $missing = [];
        foreach ($specs as $spec) {
            if ($spec['idgp_1c'] !== '' && !isset($by1c[$spec['idgp_1c']])) {
                $missing[$spec['idgp_1c']] = $spec['naim'];
            }
            foreach ($spec['sostav'] as $row) {
                $code = trim((string) ($row['id_1c'] ?? ''));
                if ($code !== '' && !isset($by1c[$code])) {
                    $missing[$code] ??= '';
                }
            }
        }
        if (!$missing || !$sync) {
            return [];
        }
        $this->line('Кодов 1С нет в товарах Компаса: ' . count($missing) . ', ищем в Битрикс24 по XML_ID');
        $result = [];
        foreach ($missing as $code => $title) {
            $row = $sync->fetchProductRowByXmlId((string) $code);
            if (!$row) {
                $this->line("  1С {$code}" . ($title !== '' ? " (готовая продукция «{$title}»)" : '') . ': в Битрикс24 не найден');
                continue;
            }
            $b24 = (string) ($row['ID'] ?? '');
            $name = trim((string) ($row['NAME'] ?? ''));
            if (isset($byB24[$b24])) {
                $result[$code] = $byB24[$b24];
                $this->line("  1С {$code} → B24 {$b24} «{$name}» → товар Компаса #{$byB24[$b24]->id}");
            } elseif (\Modules\Bitrix24\Services\B24ProductSync::isInactive($row)) {
                $this->line("  1С {$code} → B24 {$b24} «{$name}»: товар неактивен в Битрикс24");
            } elseif ($dryRun) {
                $result[$code] = true;
                $this->line("  1С {$code} → B24 {$b24} «{$name}»: нет в Компасе, будет подтянут из Битрикс24");
            } elseif ($product = $sync->pullProductById($b24)) {
                $result[$code] = $product;
                $this->line("  1С {$code} → B24 {$b24} «{$name}»: подтянут в Компас #{$product->id}");
            } else {
                $this->line("  1С {$code} → B24 {$b24} «{$name}»: не удалось подтянуть");
            }
        }

        return $result;
    }

    private function resolveMissing($sync, array $specs, array $byB24, array $by1c, array $byName): array
    {
        $missing = [];
        foreach ($specs as $spec) {
            foreach ($spec['sostav'] as $row) {
                $b24 = trim((string) ($row['B24_ID'] ?? ''));
                if ($b24 !== '' && !isset($byB24[$b24]) && !isset($by1c[trim((string) ($row['id_1c'] ?? ''))])) {
                    $missing[$b24] = true;
                }
            }
        }
        if (!$missing || !$sync) {
            return [];
        }
        $result = [];
        foreach (array_keys($missing) as $b24) {
            $row = $sync->fetchProductRow($b24);
            if (!$row) {
                $this->line("  B24_ID {$b24}: в Битрикс24 не найден");
                continue;
            }
            $xml = trim((string) ($row['XML_ID'] ?? ''));
            $name = trim((string) ($row['NAME'] ?? ''));
            $product = ($xml !== '' ? ($by1c[$xml] ?? null) : null) ?? ($byName[$this->key($name)] ?? null);
            if ($product) {
                $result[(string) $b24] = $product;
                $this->line("  B24_ID {$b24} «{$name}» → товар Компаса #{$product->id} (B24 {$product->id_b24})");
            } else {
                $this->line("  B24_ID {$b24} «{$name}»: в Компасе нет ни по коду 1С, ни по названию");
            }
        }

        return $result;
    }

    private function rowKey(array $row): string
    {
        return '1С ' . trim((string) ($row['id_1c'] ?? '')) . ' / B24_ID ' . trim((string) ($row['B24_ID'] ?? ''));
    }

    private function productLine(object $product, $count): array
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

    private function key(string $name): string
    {
        $name = str_replace('ё', 'е', mb_strtolower(trim($name)));
        $name = preg_replace('/\s*([,()\-])\s*/u', '$1', $name);

        return (string) preg_replace('/\s+/u', ' ', $name);
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
