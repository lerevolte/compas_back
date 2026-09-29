<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\SiteProductSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiteSyncPullProducts extends Command
{
    protected $signature = 'site-sync:pull-products
        {target=avixo : <tenant_id>}
        {--ids= : id товаров Компаса через запятую (по умолчанию все с артикулом)}
        {--dry-run : показать, что будет заполнено, без записи}';

    protected $description = 'Однократно заполнить пустые характеристики товаров Компаса значениями с сайта (заполненные в Компасе не трогаются)';

    private const NUMBER_FIELDS = ['length', 'width', 'height', 'volume', 'weight', 'pallet_quantity', 'replenishment_period'];
    private const TEXT_FIELDS = ['supplier_barcode'];
    private const SELECT_FIELDS = ['storage_unit', 'replenishment_method'];
    private const MULTI_FIELDS = ['storage_conditions'];

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('target'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }
        $tenant->run(function () use ($tenant) {
            if (!SiteProductSync::ready()) {
                $this->error("  ✗ {$tenant->id}: синхронизация с сайтом не настроена");
                return;
            }
            $dry = (bool) $this->option('dry-run');
            $columns = array_values(array_filter(array_merge(self::NUMBER_FIELDS, self::TEXT_FIELDS, self::SELECT_FIELDS, self::MULTI_FIELDS), fn ($c) => Schema::hasColumn('products', $c)));
            $query = DB::table('products')->whereNull('deleted_at')->whereNotNull('article')->where('article', '!=', '');
            $ids = trim((string) $this->option('ids'));
            if ($ids !== '') {
                $query->whereIn('id', array_map('intval', explode(',', $ids)));
            }
            $stat = ['products' => 0, 'updated' => 0, 'fields' => 0, 'missing' => 0, 'errors' => 0];
            $unknown = [];
            $query->orderBy('id')->select(array_merge(['id', 'article'], $columns))->chunk(200, function ($rows) use (&$stat, &$unknown, $columns, $dry) {
                $byArticle = [];
                foreach ($rows as $row) {
                    if (ctype_digit(trim((string) $row->article))) {
                        $byArticle[(int) $row->article][] = $row;
                    }
                }
                $result = SiteProductSync::read(array_keys($byArticle));
                if ($result['error']) {
                    $stat['errors']++;
                    $this->error('    ' . $result['error']);
                    return;
                }
                $stat['missing'] += count($result['missing']);
                foreach ($result['items'] as $item) {
                    foreach ($byArticle[(int) ($item['id'] ?? 0)] ?? [] as $row) {
                        $stat['products']++;
                        $update = [];
                        foreach ($columns as $field) {
                            $current = $row->{$field};
                            $isEmpty = $current === null || $current === '' || $current === '[]' || (is_numeric($current) && (float) $current == 0.0 && in_array($field, self::NUMBER_FIELDS, true));
                            if (!$isEmpty) {
                                continue;
                            }
                            $value = $item[$field] ?? null;
                            if ($field === 'weight' && ($value === null || $value === '') && isset($item['catalog_weight'])) {
                                $value = $item['catalog_weight'];
                            }
                            if ($value === null || $value === '' || $value === []) {
                                continue;
                            }
                            if (in_array($field, self::NUMBER_FIELDS, true)) {
                                $number = self::number($value);
                                if ($number === null || $number <= 0) {
                                    continue;
                                }
                                $update[$field] = rtrim(rtrim(number_format($number, 3, '.', ''), '0'), '.');
                            } elseif (in_array($field, self::SELECT_FIELDS, true)) {
                                $value = is_array($value) ? (array_values($value)[0] ?? '') : $value;
                                $option = SiteProductSync::optionValue($field, (string) $value);
                                if ($option === null) {
                                    $unknown[$field][(string) $value] = true;
                                    continue;
                                }
                                $update[$field] = (string) $option;
                            } elseif (in_array($field, self::MULTI_FIELDS, true)) {
                                $options = [];
                                foreach ((array) $value as $label) {
                                    $option = SiteProductSync::optionValue($field, (string) $label);
                                    if ($option === null) {
                                        $unknown[$field][(string) $label] = true;
                                        continue;
                                    }
                                    $options[] = $option;
                                }
                                if (!count($options)) {
                                    continue;
                                }
                                $update[$field] = json_encode(array_values(array_unique($options)));
                            } else {
                                $update[$field] = trim((string) (is_array($value) ? implode(', ', $value) : $value));
                            }
                        }
                        if (!count($update)) {
                            continue;
                        }
                        $stat['updated']++;
                        $stat['fields'] += count($update);
                        if ($dry) {
                            if ($stat['updated'] <= 20) {
                                $this->line("    #{$row->id} (сайт {$row->article}): " . json_encode($update, JSON_UNESCAPED_UNICODE));
                            }
                            continue;
                        }
                        DB::table('products')->where('id', $row->id)->update($update);
                    }
                }
            });
            foreach ($unknown as $field => $labels) {
                $this->warn("    {$field}: нет в Компасе вариантов " . implode(', ', array_slice(array_keys($labels), 0, 30)));
            }
            $this->info("  ✓ {$tenant->id}: найдено на сайте {$stat['products']}, заполнено товаров {$stat['updated']}, полей {$stat['fields']}, нет на сайте {$stat['missing']}, ошибок {$stat['errors']}" . ($dry ? ' (dry-run)' : ''));
        });

        return self::SUCCESS;
    }

    private static function number($value): ?float
    {
        if (is_array($value)) {
            $value = array_values($value)[0] ?? null;
        }
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (preg_match('/-?\d+(?:[.,]\d+)?/', str_replace(' ', '', (string) $value), $m)) {
            return (float) str_replace(',', '.', $m[0]);
        }

        return null;
    }
}
