<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Task;
use App\Models\Car;
use App\Models\Employee;

class Route extends Model
{

    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $guarded = ['id'];
    
    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;

            // Подстраховка для CrudService.batch: при создании маршрута через
            // /objects/routes/batch иногда первый Route::create(['name'=>''])
            // не получает car_id/employee_id, и они подцепляются только
            // последующим save(). Если в request пришли эти поля — заполним
            // их прямо здесь, чтобы не зависеть от внешней логики.
            $request = request();
            if ($request) {
                $extractId = function ($v) {
                    if ($v === null || $v === '') return null;
                    if (is_array($v)) {
                        if (isset($v['value'])) return is_array($v['value']) ? ($v['value'][0] ?? null) : $v['value'];
                        return $v[0] ?? null;
                    }
                    return $v;
                };
                // batch посылает {rows: [{car_id, employee_id, ...}]} — берём первую строку.
                $rows = $request->input('rows');
                $row = (is_array($rows) && count($rows)) ? $rows[0] : null;
                if (!$model->car_id) {
                    $car = $row && isset($row['car_id']) ? $extractId($row['car_id']) : null;
                    if (!$car) $car = $extractId($request->input('car_id'));
                    if ($car) $model->car_id = (int) $car;
                }
                if (!$model->employee_id) {
                    $emp = $row && isset($row['employee_id']) ? $extractId($row['employee_id']) : null;
                    if (!$emp) $emp = $extractId($request->input('employee_id'));
                    if ($emp) $model->employee_id = (int) $emp;
                }
            }

            if (!$model->company_id) {
                // 1. Пытаемся взять из сотрудника
                if ($model->employee_id) {
                    $employee = Employee::find($model->employee_id);
                    if ($employee && $employee->company_id) {
                        $model->company_id = $employee->company_id;
                    }
                }

                // 2. Если все еще пусто, пытаемся взять из автопарка (машины)
                if (!$model->company_id && $model->car_id) {
                    $car = Car::find($model->car_id);
                    if ($car && $car->company_id) {
                        $model->company_id = $car->company_id;
                    }
                }
            }

            // Если пользователь не выбрал цвет маршрута — берём цвет машины.
            // У route.color и car.color хранятся ID записей field_values
            // (палитра у каждой сущности своя), поэтому ID напрямую переносить
            // нельзя — создаём в field_values новую запись для палитры маршрутов
            // с тем же hex-значением и подставляем её ID.
            if (!$model->color && $model->car_id) {
                $model->color = static::resolveColorFromCar((int) $model->car_id);
            }
        });

        static::updating(function($model)
        {
            if ($model->isDirty('date')) {
                // Используем update() для массового обновления (эффективнее по производительности)
                $model->logistic_tasks()->update(['delivery_date' => $model->date]);
            }

            // 2. Привязка машины: копируем employee_requirements из Car в car_requirements Маршрута
            if ($model->isDirty('car_id')) {
                if ($model->car_id) {
                    $car = Car::find($model->car_id);
                    if ($car) {
                        $model->car_requirements = $car->employee_requirements;
                    }
                } else {
                    $model->car_requirements = null; 
                }
            }

            // 3. Привязка сотрудника: копируем requirements из Employee в employee_requirements Маршрута
            if ($model->isDirty('employee_id')) {
                if ($model->employee_id) {
                    $employee = Employee::find($model->employee_id);
                    if ($employee) {
                        $model->employee_requirements = $employee->requirements;
                    }
                } else {
                    $model->employee_requirements = null;
                }
            }
        });
    }

    /**
     * Получить цвет машины и завести соответствующую запись в палитре
     * маршрутов (field_values для routes.color), вернуть её ID.
     *
     * «Цвет машины», который видит пользователь, лежит в car.color_status
     * (это ID записи field_values для статус-цвета машины). Поле car.color
     * самостоятельно не заполняется и используется как запасной источник.
     *
     * Возвращает null, если у машины нет валидного цвета или у сущности
     * routes не объявлено поле color — тогда оставляем route.color пустым.
     */
    protected static function resolveColorFromCar(int $carId): ?int
    {
        $car = Car::find($carId);
        if (!$car) return null;

        $hex = static::resolveHexFromCarColumn($car->color_status)
            ?: static::resolveHexFromCarColumn($car->color);
        if (!$hex) return null;

        // Поле color сущности routes
        $routeColorField = \DB::table('data_rows')
            ->where('field', 'color')
            ->whereIn('data_type_id', function ($q) {
                $q->select('id')->from('data_types')->where('slug', 'routes');
            })
            ->first();
        if (!$routeColorField) return null;

        // Если такая палитровая запись для маршрутов уже есть — переиспользуем,
        // чтобы не плодить дубликаты в field_values при каждом создании маршрута.
        $existingRouteValue = \DB::table('field_values')
            ->where('field_id', $routeColorField->id)
            ->where('color', $hex)
            ->orderBy('id')
            ->first();
        if ($existingRouteValue) return (int) $existingRouteValue->id;

        $newId = \DB::table('field_values')->insertGetId([
            'field_id'  => $routeColorField->id,
            'color'     => $hex,
            'file'      => null,
            'value'     => '',
            'sort'      => 0,
            'is_hidden' => 1,
        ]);

        // Кэш статусов поля держится в memcached — сбрасываем, чтобы новая
        // запись была видна в селекторе цветов на фронте без перезагрузки.
        try {
            $cacheName = tenant('id') . ':field-' . $routeColorField->id . '-statuses';
            cache()->getMemcached()->delete($cacheName);
        } catch (\Throwable $e) {}

        return (int) $newId;
    }

    /**
     * Распаковать значение колонки car.color_status / car.color в hex.
     * Может быть либо числовым ID записи field_values, либо уже строкой
     * с hex/линейным градиентом.
     */
    protected static function resolveHexFromCarColumn($value): ?string
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            $fv = \DB::table('field_values')->where('id', (int) $value)->first();
            return $fv && $fv->color ? (string) $fv->color : null;
        }
        return (string) $value;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }


    public function tasks()
    {
        return $this->hasMany(Task::class, 'route_id')->orderBy('sort');
    }

    public function logistic_tasks()
    {
        return $this->hasMany(Task::class, 'route_id')->orderBy('sort');
    }

    /**
     * 1. Метод для пересчета суммарного веса и объема маршрута
     * Вызывается из модели Task при изменениях
     */
    public function recalculateTotals()
    {
        // Считаем суммы отдельными легкими запросами
        $totalWeight = $this->tasks()->sum('weight');
        $totalVolume = $this->tasks()->sum('volume');
        // Добавьте другие поля, если нужно (например, count)
        // $totalCount = $this->tasks()->count();

        // Обновляем текущую модель Route
        $this->update([
            'weight' => $totalWeight, // замените на ваши названия полей в таблице routes
            'volume' => $totalVolume,
        ]);
    }
    

    public function getTaskFilters()
    {
        $car = $this->car;
        $settings = get_settings();
        
        // В getTaskFilters — car_requirements
        $carReqsVal = $this->car_requirements;
        if (is_string($carReqsVal)) {
            $decoded = json_decode($carReqsVal, true);
            $carReqsVal = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [$carReqsVal];
        }
        if (!$carReqsVal) $carReqsVal = [];

        $settings = get_settings();
        $carReqLabels = [];
        $carReqField = null;
        foreach ($settings['logistic_tasks']['fields'] as $f) {
            if ($f->field === 'car_requirements') { $carReqField = $f; break; }
        }
        if ($carReqField && isset($settings['list_values'][$carReqField->id])) {
            foreach ($carReqsVal as $val) {
                $opt = $settings['list_values'][$carReqField->id][$val] ?? null;
                $carReqLabels[] = $opt ? ($opt['label'] ?? $opt['text'] ?? $val) : $val;
            }
        }

        // employee_requirements — аналогично
        $empReqsVal = $this->employee_requirements;
        if (is_string($empReqsVal)) {
            $decoded = json_decode($empReqsVal, true);
            $empReqsVal = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [$empReqsVal];
        }
        if (!$empReqsVal) $empReqsVal = [];

        $empReqLabels = [];
        $empReqField = null;
        foreach ($settings['logistic_tasks']['fields'] as $f) {
            if ($f->field === 'employee_requirements') { $empReqField = $f; break; }
        }
        if ($empReqField && isset($settings['list_values'][$empReqField->id])) {
            foreach ($empReqsVal as $val) {
                $opt = $settings['list_values'][$empReqField->id][$val] ?? null;
                $empReqLabels[] = $opt ? ($opt['label'] ?? $opt['text'] ?? $val) : $val;
            }
        }

        return [
            [
                'title' => 'Требования к машине',
                'key'   => 'car_requirements',
                'value' => $carReqsVal,
                'labels' => $carReqLabels
            ],
            [
                'title' => 'Требования к водителю',
                'key'   => 'employee_requirements',
                'value' => $empReqsVal,
                'labels' => $empReqLabels
            ],
            [
                'title' => 'Вес от/до',
                'key'   => 'weight',
                'value' => [$car ? $car->weight_min : null, $car ? $car->weight_max : null]
            ],
            [
                'title' => 'Объем от/до',
                'key'   => 'volume',
                'value' => [$car ? $car->volume_min : null, $car ? $car->volume_max : null]
            ]
        ];
    }

}