<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

/**
 * Точечные правки CRM-сущностей (8873/8877):
 *  - удаляет из «Сделок» поля «Время прибытия» (plan_time), «Склад отгрузки»
 *    и «Дата доставки» (delivery_date);
 *  - делает поле «Ответственный» (user_id) обязательным у deals/contacts/companies;
 *  - проставляет маски multi_text-полям Телефон/Email (8883);
 *  - проставляет «Тип контакта» = Клиент всем контактам без типа.
 *
 * Команда идемпотентна.
 *   php artisan crm:fix-fields avixo
 */
class FixCrmFields extends Command
{
    protected $signature = 'crm:fix-fields
        {target=avixo : seeds | all-tenants | <tenant_id>}';

    protected $description = 'Убрать лишние поля сделок, сделать «Ответственный» обязательным, заполнить «Тип контакта» (8873/8877)';

    public function handle(): int
    {
        $target = $this->argument('target');

        if ($target === 'seeds') {
            $this->applyTo(\DB::connection('seeds'), 'admin_seeds');
            return self::SUCCESS;
        }

        if ($target === 'all-tenants') {
            foreach (Tenant::get() as $tenant) {
                try {
                    $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $tenant->id));
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
        $tenant->run(fn () => $this->applyTo(\DB::connection(), (string) $target));
        $this->info("Готово: {$target}");
        return self::SUCCESS;
    }

    private function applyTo($db, string $label): void
    {
        $sb = $db->getSchemaBuilder();

        $dealTypeIds = $db->table('data_types')->where('slug', 'deals')->pluck('id');
        if ($dealTypeIds->isNotEmpty()) {
            $deleted = $db->table('data_rows')
                ->whereIn('data_type_id', $dealTypeIds)
                ->where(function ($q) {
                    $q->whereIn('field', ['plan_time', 'delivery_date'])
                        ->orWhereIn('title', ['Время прибытия', 'План. время прибытия', 'Планируемое время прибытия', 'Склад отгрузки', 'Дата доставки']);
                })
                ->delete();
            $this->line("  {$label}: удалено полей сделок — {$deleted}");
        }

        $crmTypeIds = $db->table('data_types')->whereIn('slug', ['deals', 'contacts', 'companies'])->pluck('id');
        if ($crmTypeIds->isNotEmpty()) {
            $required = $db->table('data_rows')
                ->whereIn('data_type_id', $crmTypeIds)
                ->where('field', 'user_id')
                ->where('required', 0)
                ->update(['required' => 1]);
            $this->line("  {$label}: «Ответственный» стал обязательным — {$required}");
        }

        $phoneMasks = $db->table('data_rows')
            ->where('type', 'multi_text')
            ->where('field', 'phones')
            ->where(function ($q) {
                $q->whereNull('mask')->orWhere('mask', '');
            })
            ->update(['mask' => '+7 (###) ###-##-##']);
        $emailMasks = $db->table('data_rows')
            ->where('type', 'multi_text')
            ->where('field', 'emails')
            ->where(function ($q) {
                $q->whereNull('mask')->orWhere('mask', '');
            })
            ->update(['mask' => 'email']);
        $this->line("  {$label}: маски multi_text — телефоны {$phoneMasks}, email {$emailMasks}");

        if ($sb->hasTable('contacts') && $sb->hasColumn('contacts', 'contact_type')) {
            $filled = $db->table('contacts')
                ->where(function ($q) {
                    $q->whereNull('contact_type')->orWhereIn('contact_type', ['', '[]', 'null']);
                })
                ->update(['contact_type' => '[0]']);
            $this->line("  {$label}: «Тип контакта» = Клиент проставлен — {$filled}");
        }

        if ($label !== 'admin_seeds') {
            try {
                \App\Models\Settings::clear_cache();
            } catch (\Throwable $e) {
            }
        }
    }
}
