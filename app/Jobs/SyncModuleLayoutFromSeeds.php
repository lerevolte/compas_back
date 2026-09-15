<?php

namespace App\Jobs;

use App\Services\ModuleLayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncModuleLayoutFromSeeds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 900;
    public $tries = 1;

    public function __construct(
        public string $module,
        public string $page
    ) {
    }

    public function handle(): void
    {
        $report = ModuleLayoutService::syncFromSeeds($this->module, $this->page);
        $errors = array_filter($report, fn ($r) => isset($r['error']));
        if (count($errors)) {
            Log::warning('module layout: ошибки синхронизации из seeds', ['module' => $this->module, 'page' => $this->page, 'errors' => $errors]);
        }
    }
}
