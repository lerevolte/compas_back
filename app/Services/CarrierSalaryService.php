<?php

namespace App\Services;

use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CarrierSalaryService
{
    public const PAY_TITLES = ['Стоимость доставки', 'Цена доставки', 'Цена доставки, руб'];
    public const PAY_FALLBACK = 'delivery_price';
    public const PAID_TITLE = 'Оплата перевозчику';

    private ?array $fields = null;
    private array $rows = [];

    public function period(Request $request): array
    {
        $from = $this->day($request->from ?? ($request->period['start'] ?? null));
        $to = $this->day($request->to ?? ($request->period['end'] ?? null));
        $from = $from ?? now()->format('Y-m-d');
        $to = $to ?? $from;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    public function fields(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }
        $result = ['pay' => null, 'pay_title' => 'Стоимость доставки', 'paid' => null, 'paid_title' => null, 'paid_ids' => []];
        $typeId = DB::table('data_types')->where('slug', 'routes')->value('id');
        $rows = $typeId
            ? DB::table('data_rows')->where('data_type_id', $typeId)->where('is_remove', 0)->orderBy('id')->get(['id', 'field', 'title', 'type'])
            : collect();
        foreach (self::PAY_TITLES as $title) {
            $row = $rows->first(fn ($r) => mb_strtolower(trim((string) $r->title)) === mb_strtolower($title) && Schema::hasColumn('routes', $r->field));
            if ($row) {
                $result['pay'] = $row->field;
                $result['pay_title'] = trim((string) $row->title);
                break;
            }
        }
        if (!$result['pay'] && Schema::hasColumn('routes', self::PAY_FALLBACK)) {
            $result['pay'] = self::PAY_FALLBACK;
        }
        $paid = $rows->first(fn ($r) => $r->type === 'status' && str_starts_with(mb_strtolower(trim((string) $r->title)), mb_strtolower(self::PAID_TITLE)) && Schema::hasColumn('routes', $r->field));
        if ($paid) {
            $result['paid'] = $paid->field;
            $result['paid_title'] = trim((string) $paid->title);
            $result['paid_ids'] = DB::table('field_values')->where('field_id', $paid->id)->get(['id', 'value'])
                ->filter(function ($v) {
                    $label = mb_strtolower(trim((string) $v->value));

                    return str_contains($label, 'оплачен') && !str_starts_with($label, 'не ');
                })
                ->map(fn ($v) => (string) $v->id)
                ->values()
                ->all();
        }

        return $this->fields = $result;
    }

    public function companies(string $from, string $to): array
    {
        $groups = [];
        foreach ($this->rows($from, $to) as $row) {
            foreach ($row->companies as $companyId) {
                $groups[$companyId][] = $row;
            }
        }
        $names = $groups ? DB::table('companies')->whereIn('id', array_keys($groups))->pluck('name', 'id') : collect();
        $list = [];
        foreach ($groups as $companyId => $items) {
            $list[] = ['id' => $companyId, 'name' => $this->name($names[$companyId] ?? null, 'Компания #' . $companyId)] + $this->aggregate($items);
        }
        usort($list, fn ($a, $b) => mb_strtolower($a['name']) <=> mb_strtolower($b['name']));

        return ['from' => $from, 'to' => $to, 'meta' => $this->meta(), 'companies' => $list];
    }

    public function cars(string $from, string $to, int $companyId): array
    {
        $items = $this->companyRows($from, $to, $companyId);
        $groups = [];
        $none = [];
        foreach ($items as $row) {
            if (!$row->cars) {
                $none[] = $row;
                continue;
            }
            foreach ($row->cars as $carId) {
                $groups[$carId][] = $row;
            }
        }
        $names = $groups ? DB::table('cars')->whereIn('id', array_keys($groups))->pluck('name', 'id') : collect();
        $list = [];
        foreach ($groups as $carId => $rows) {
            $list[] = ['id' => $carId, 'name' => $this->name($names[$carId] ?? null, 'Машина #' . $carId)] + $this->aggregate($rows);
        }
        usort($list, fn ($a, $b) => mb_strtolower($a['name']) <=> mb_strtolower($b['name']));

        return [
            'meta' => $this->meta(),
            'all' => $this->aggregate($items),
            'none' => $this->aggregate($none),
            'cars' => $list,
        ];
    }

    public function routeIds(string $from, string $to, int $companyId, string $car): array
    {
        $ids = [];
        foreach ($this->companyRows($from, $to, $companyId) as $row) {
            if ($car === 'none' ? !$row->cars : ($car === 'all' || $car === '' || in_array((int) $car, $row->cars, true))) {
                $ids[] = (int) $row->id;
            }
        }

        return $ids;
    }

    private function companyRows(string $from, string $to, int $companyId): array
    {
        return array_values(array_filter($this->rows($from, $to), fn ($row) => in_array($companyId, $row->companies, true)));
    }

    private function rows(string $from, string $to): array
    {
        $key = $from . '|' . $to;
        if (isset($this->rows[$key])) {
            return $this->rows[$key];
        }
        $fields = $this->fields();
        $columns = array_values(array_unique(array_filter(['id', 'company_id', 'car_id', 'mileage', 'weight', $fields['pay'], $fields['paid']])));
        $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn('routes', $c)));
        $result = [];
        $routes = DB::table('routes')
            ->whereNull('deleted_at')
            ->whereRaw('DATE(`date`) BETWEEN ? AND ?', [$from, $to])
            ->orderBy('date')
            ->orderBy('id')
            ->get($columns);
        foreach ($routes as $route) {
            $companies = array_values(array_filter(Route::parseIdList($route->company_id ?? null)));
            if (!$companies) {
                continue;
            }
            $pay = $fields['pay'] ? (float) ($route->{$fields['pay']} ?? 0) : 0.0;
            $paidValue = $fields['paid'] ? $this->statusId($route->{$fields['paid']} ?? null) : null;
            $result[] = (object) [
                'id' => (int) $route->id,
                'companies' => array_map('intval', $companies),
                'cars' => array_map('intval', array_values(array_filter(Route::parseIdList($route->car_id ?? null)))),
                'mileage' => (float) ($route->mileage ?? 0),
                'weight' => (float) ($route->weight ?? 0),
                'pay' => $pay,
                'paid' => $paidValue !== null && in_array($paidValue, $fields['paid_ids'], true),
            ];
        }

        return $this->rows[$key] = $result;
    }

    private function aggregate(array $rows): array
    {
        $paid = 0.0;
        $total = 0.0;
        $mileage = 0.0;
        $weight = 0.0;
        foreach ($rows as $row) {
            $total += $row->pay;
            $mileage += $row->mileage;
            $weight += $row->weight;
            if ($row->paid) {
                $paid += $row->pay;
            }
        }

        return [
            'routes_count' => count($rows),
            'mileage' => round($mileage, 2),
            'weight' => round($weight, 2),
            'pay_sum' => round($total, 2),
            'paid_sum' => round($paid, 2),
            'unpaid_sum' => round($total - $paid, 2),
        ];
    }

    private function meta(): array
    {
        $fields = $this->fields();

        return ['pay_title' => $fields['pay_title'], 'has_paid' => (bool) $fields['paid'], 'paid_title' => $fields['paid_title']];
    }

    private function statusId($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_string($raw) && str_starts_with(trim($raw), '{')) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? ($decoded['value'] ?? null) : $raw;
        }
        if (is_array($raw)) {
            $raw = $raw[0] ?? null;
        }

        return $raw === null || $raw === '' ? null : (string) $raw;
    }

    private function name($raw, string $fallback): string
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && array_key_exists('value', $decoded)) {
                $raw = $decoded['value'];
            }
        }
        $raw = is_string($raw) ? trim($raw) : '';

        return $raw !== '' ? $raw : $fallback;
    }

    private function day($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse(is_array($value) ? ($value[0] ?? null) : $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
