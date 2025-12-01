<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Http\Request;
use App\Helpers\ValueHelper;

class BalanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $slug, $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function headings(): array
    {
        $data = array(
            'Дата операции',
            'Тип операции',
            'Сумма операции',
            'Комментарий'
        );
        
        return $data;
    }

    public function map($item): array
    {
        $data = array(
            'created_at',
            'type',
            'sum',
            'comment'
        );
        
        return $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $this->params['per_page'] = 0;
        $request = new Request($this->params);
        if($this->params['date_from'] && $this->params['date_to'])
            $items  = App\Models\BalanceOperation::select(['created_at', 'type', 'sum', 'comment'])->whereBetween('created_at', [$this->params['date_from'], $this->params['date_to']])->get();
        
        return collect($objects);
    }
}
