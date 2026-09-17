<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Tenant;
use App\Services\Saby\SabyValidationException;
use App\Services\Saby\SabyWaybillService;
use Illuminate\Console\Command;

class SabyWaybillXml extends Command
{
    protected $signature = 'saby:waybill-xml {tenant} {task : id задачи логистики} {--full : вывести весь титул, а не только грузополучателя}';

    protected $description = 'Сгенерировать титул грузоотправителя ЭТрН по задаче через СБИС.СгенерироватьВложение и показать XML (документ в Saby не создаётся)';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (!$tenant) {
            $this->error('Портал не найден');
            return self::FAILURE;
        }

        return (int) $tenant->run(function () {
            $task = Task::find((int) $this->argument('task'));
            if (!$task) {
                $this->error('Задача не найдена');
                return self::FAILURE;
            }
            $service = SabyWaybillService::make();
            if (!$service) {
                $this->error('Saby не настроен');
                return self::FAILURE;
            }

            try {
                $result = $service->generatePreview($task);
            } catch (SabyValidationException $e) {
                $this->error('Ошибки валидации: ' . implode('; ', $e->errors()));
                return self::FAILURE;
            }

            $this->line('Подстановка СвГП: ' . json_encode($result['document']['СодИнфГО']['СвГП'] ?? null, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('КоличествоОшибок: ' . ($result['errors'] ?? ''));

            $xml = $result['xml'];
            if (stripos(substr($xml, 0, 100), 'windows-1251') !== false) {
                $xml = @iconv('windows-1251', 'utf-8//IGNORE', $xml) ?: $xml;
            }
            if ($this->option('full')) {
                $this->line($xml);
            } elseif (preg_match('/<СвГП\b.*?<\/СвГП>/su', $xml, $match)) {
                $this->line('Сгенерированный XML СвГП: ' . $match[0]);
            } else {
                $this->warn('В сгенерированном титуле нет элемента СвГП');
            }

            return self::SUCCESS;
        });
    }
}
