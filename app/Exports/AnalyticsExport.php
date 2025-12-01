<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $analyticsData;
    protected $type;
    protected $hasGroupBy;

    public function __construct($type, Request $request)
    {
        $this->type = $type;
        $this->hasGroupBy = !empty($request->group_by);
        $service = new AnalyticsService(tenant('id'));
        $this->analyticsData = $this->getAnalyticsData($service, $request);
    }

    protected function getAnalyticsData(AnalyticsService $service, Request $request)
    {
        switch ($this->type) {
            case 'fines': return $service->index($request);
            case 'gibdd_queries': return $service->gibddQueries($request);
            case 'income': return $service->income($request);
            case 'income_moneta': return $service->incomeMoneta($request);
            case 'account_incomes': return $service->accountIncomes($request);
            case 'all_income': return $service->allIncome($request);
            case 'expense_moneta': return $service->expenseMoneta($request);
            default: return [];
        }
    }

    public function headings(): array
    {
        $baseHeadings = ['Дата', 'Сумма'];
        
        if ($this->hasGroupBy) {
            array_push($baseHeadings, 'Объект');
        }
        
        if ($this->type === 'fines') {
            array_push($baseHeadings, 'Статус');
        }
        
        return $baseHeadings;
    }

    public function map($item): array
    {
        $row = [
            $item['date'] ?? '',
            $item['amount'] ?? $item['sum'] ?? 0
        ];
        
        if ($this->hasGroupBy) {
            $row[] = $item['name'] ?? '';
        }
        
        if ($this->type === 'fines') {
            $row[] = $item['status'] ?? '';
        }
        
        return $row;
    }

    public function collection()
    {
        $data = [];
        $legends = $this->analyticsData['legend'] ?? [];

        foreach ($legends as $legend) {
            if (empty($legend['data'])) continue;
            
            foreach ($legend['data'] as $point) {
                $date = is_array($point) ? $point[0] : ($point->date ?? '');
                $amount = is_array($point) ? $point[1] : ($point->amount ?? $point->sum ?? 0);
                
                $item = [
                    'date' => $date,
                    'amount' => $amount,
                    'sum' => $amount
                ];
                
                if ($this->hasGroupBy) {
                    $item['name'] = $legend['name'] ?? '';
                }
                
                if ($this->type === 'fines') {
                    $item['status'] = $legend['status'] ?? '';
                }
                
                $data[] = $item;
            }
        }

        return collect($data);
    }
}