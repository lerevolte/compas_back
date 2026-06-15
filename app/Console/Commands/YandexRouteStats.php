<?php

namespace App\Console\Commands;

use App\Models\YandexRouteLog;
use Illuminate\Console\Command;

class YandexRouteStats extends Command
{
    /**
     * php artisan yandex:route-stats --days=7 --top=20
     *
     * @var string
     */
    protected $signature = 'yandex:route-stats {--days=7 : За сколько последних дней считать} {--top=20 : Сколько IP показать}';

    /**
     * @var string
     */
    protected $description = 'Статистика запросов к Яндекс-маршрутизатору: по ключам и по IP';

    public function handle()
    {
        $days = max(1, (int) $this->option('days'));
        $top = max(1, (int) $this->option('top'));
        $since = now()->subDays($days);

        $base = YandexRouteLog::where('created_at', '>=', $since);

        $total = (clone $base)->count();
        $this->info("Период: последние {$days} дн. (с {$since->format('Y-m-d H:i')}). Всего записей: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('=== По API-ключам ===');
        $byKey = (clone $base)
            ->selectRaw('api_key, event, count(*) as cnt')
            ->groupBy('api_key', 'event')
            ->get()
            ->groupBy('api_key');

        $keyRows = [];
        foreach ($byKey as $key => $events) {
            $counts = $events->pluck('cnt', 'event');
            $keyRows[] = [
                $key ?: '(нет)',
                $counts->sum(),
                (int) $counts->get('route_ok', 0),
                (int) $counts->get('route_fail', 0),
                (int) $counts->get('script_ok', 0),
                (int) $counts->get('script_fail', 0),
            ];
        }
        usort($keyRows, fn ($a, $b) => $b[1] <=> $a[1]);
        $this->table(['API-ключ', 'Всего', 'route_ok', 'route_fail', 'script_ok', 'script_fail'], $keyRows);

        $this->newLine();
        $this->line("=== Топ {$top} IP ===");
        $byIp = (clone $base)
            ->selectRaw('ip, count(*) as cnt')
            ->groupBy('ip')
            ->orderByDesc('cnt')
            ->limit($top)
            ->get()
            ->map(fn ($r) => [$r->ip ?: '(нет)', $r->cnt])
            ->toArray();
        $this->table(['IP', 'Запросов'], $byIp);

        return self::SUCCESS;
    }
}
