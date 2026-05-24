<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HealthService
{
    public function check(): array
    {
        $now = Carbon::now(config('app.timezone'));

        return [
            'time_iso'        => $now->toIso8601String(),
            'time_unix'       => $now->getTimestamp(),
            'laravel_version' => app()->version(),
            'database'        => $this->checkDatabase(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'up'];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'error'  => $e->getMessage(),
            ];
        }
    }
}
