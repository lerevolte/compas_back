<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class FixRelationHistoryLinks extends Command
{
    protected $signature = 'histories:fix-relation-links
        {target=avixo : seeds | all-tenants | <tenant_id>}
        {--dry-run : показать план без изменений}';

    protected $description = 'Заменить в истории нерасшифрованные ссылки вида data-id=\'[1]\' на имена связанных объектов';

    private const PATTERN = "/<span data-slug='([^']+)' data-id='\[([0-9,\s]*)\]'>(.*?)<\/span>/u";

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->fix(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->fix(\DB::connection(), (string) $tenant->id));
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
                $stripped = substr($target, strlen($prefix));
                $tenant = Tenant::find($stripped);
                if ($tenant) {
                    $target = $stripped;
                }
            }
        }
        if (!$tenant) {
            $this->error("Портал '{$target}' не найден");
            return self::FAILURE;
        }

        $tenant->run(fn () => $this->fix(\DB::connection(), (string) $target));

        return self::SUCCESS;
    }

    private function fix(ConnectionInterface $db, string $label): void
    {
        if (!$db->getSchemaBuilder()->hasTable('histories')) {
            return;
        }

        $dry = (bool) $this->option('dry-run');
        $names = [];
        $found = 0;
        $fixed = 0;
        $samples = [];

        $db->table('histories')
            ->where('text', 'LIKE', "%data-id='[%")
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($db, $dry, &$names, &$found, &$fixed, &$samples) {
                foreach ($rows as $row) {
                    $found++;
                    $text = preg_replace_callback(self::PATTERN, function ($m) use ($db, &$names) {
                        $slug = $m[1];
                        $ids = array_values(array_filter(array_map('trim', explode(',', $m[2])), fn ($v) => $v !== ''));
                        if (!count($ids)) {
                            return '';
                        }
                        $parts = [];
                        foreach ($ids as $id) {
                            $name = $this->resolveName($db, $slug, (int) $id, $names);
                            if ($name === null) {
                                return $m[0];
                            }
                            $parts[] = "<span data-slug='{$slug}' data-id='{$id}'>{$name}</span>";
                        }

                        return implode(', ', $parts);
                    }, (string) $row->text);

                    if ($text === $row->text) {
                        continue;
                    }

                    $update = ['text' => $text];
                    foreach (['old_value', 'new_value'] as $column) {
                        $value = (string) ($row->{$column} ?? '');
                        if ($value !== '' && preg_match('/^\[([0-9,\s]*)\]$/', $value, $m)) {
                            $update[$column] = trim($m[1]);
                        }
                    }

                    if (count($samples) < 3) {
                        $samples[] = mb_strimwidth(strip_tags((string) $row->text), 0, 70, '…') . ' → ' . mb_strimwidth(strip_tags($text), 0, 70, '…');
                    }

                    if (!$dry) {
                        $db->table('histories')->where('id', $row->id)->update($update);
                    }
                    $fixed++;
                }
            });

        if (!$found) {
            return;
        }

        $verb = $dry ? 'будет исправлено' : 'исправлено';
        $this->info("[{$label}] найдено записей: {$found}, {$verb}: {$fixed}");
        foreach ($samples as $sample) {
            $this->line('    ' . $sample);
        }
    }

    private function resolveName(ConnectionInterface $db, string $slug, int $id, array &$cache): ?string
    {
        $key = $slug . ':' . $id;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $name = null;
        try {
            $sb = $db->getSchemaBuilder();
            if ($sb->hasTable($slug)) {
                $columns = ['name'];
                if ($sb->hasColumn($slug, 'display_name')) {
                    $columns[] = 'display_name';
                }
                foreach (['last_name', 'middle_name'] as $extra) {
                    if ($sb->hasColumn($slug, $extra)) {
                        $columns[] = $extra;
                    }
                }
                $row = $db->table($slug)->where('id', $id)->first($columns);
                $raw = trim((string) ($row->name ?? '')) !== '' ? $row->name : ($row->display_name ?? null);
                if ($raw !== null) {
                    $name = (string) $raw;
                    if ($name !== '' && ($name[0] === '{' || $name[0] === '[')) {
                        $decoded = json_decode($name, true);
                        if (is_array($decoded)) {
                            $name = (string) ($decoded['value'] ?? reset($decoded));
                        }
                    }
                    foreach (['last_name', 'middle_name'] as $extra) {
                        $part = trim((string) ($row->{$extra} ?? ''));
                        if ($part !== '') {
                            $name .= ' ' . $part;
                        }
                    }
                    $name = trim($name);
                    if ($name === '') {
                        $name = null;
                    }
                }
            }
        } catch (\Throwable $e) {
            $name = null;
        }

        $cache[$key] = $name;

        return $name;
    }
}
