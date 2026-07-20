<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelAdminPanel\Traits\Cropper;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as InImage;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Salary\Entities\Salary;
use Modules\EmergencyFund\Entities\FundRecord;


class Employee extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    //protected $fillable = ['name','carrier_id','phone'];
    protected $guarded = ['id'];
    
    public static function boot()
    {
        parent::boot();
        static::creating(function($model)
        {
            $user = \Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
        });
        static::updating(function($model)
        {   
            
            // if($model->driver_license && !$model->carsmonitoring_id) {
            //     $res = \Modules\Gibdd\Entities\Module::addDriver([
            //         'licensenum' => $model->driver_license,
            //         'title' => $model->name,
            //         'id' => $model->id
            //     ]);
            //     info($res);
            //     if(isset($res['id'])) {
            //         $model->carsmonitoring_id = $res['id'];
            //     }
            // }
        });
        static::deleting(function($model){ 
            //$model->cars()->sync([]);
            if($model->carsmonitoring_id) {

                \Modules\Gibdd\Entities\Module::deleteDriver($model->carsmonitoring_id);
                $model->carsmonitoring_id = null;
                $model->saveQuietly();
            }
            return true; // let the delete go through
        });
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // public function cars()
    // {
    //     return $this->hasMany(Car::class, 'car_id')->orderBy('id', 'asc');
    // }

    public function fines()
    {
        return $this->hasMany(GibddFine::class, 'employee_id');
    }

    public function fines_gibdd()
    {
        return $this->hasMany(GibddFine::class, 'employee_id');
    }

    public function cars()
    {
        return $this->belongsToMany(Car::class, 'car_employee');
    }

    public function routes()
    {
        return $this->belongsToMany(Route::class, 'route_employee');
    }

    public function logistic_tasks()
    {
        return $this->belongsToMany(Task::class, 'logistic_task_employee', 'employee_id', 'logistic_task_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'employee_id');
    }

    public static function idsForUser($user): array
    {
        if (!$user || !$user->id) {
            return [];
        }

        static $cache = [];
        if (isset($cache[$user->id])) {
            return $cache[$user->id];
        }

        $ids = [];
        if ((int) ($user->employee_id ?: 0)) {
            $ids[] = (int) $user->employee_id;
        }

        $column = static::userLinkColumn();
        if ($column) {
            $rows = \DB::table('employees')
                ->whereNull('deleted_at')
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->pluck($column, 'id');
            foreach ($rows as $employeeId => $raw) {
                if (in_array((int) $user->id, static::parseIdValue($raw), true)) {
                    $ids[] = (int) $employeeId;
                }
            }
        }

        return $cache[$user->id] = array_values(array_unique($ids));
    }

    protected static function userLinkColumn(): ?string
    {
        $fields = \DB::table('data_rows')
            ->join('data_types', 'data_types.id', '=', 'data_rows.data_type_id')
            ->where('data_types.slug', 'employees')
            ->where('data_rows.type', 'relation')
            ->where('data_rows.relation_table', 'users')
            ->where('data_rows.is_remove', 0)
            ->pluck('data_rows.field');
        foreach ($fields as $field) {
            if (\Schema::hasColumn('employees', $field)) {
                return $field;
            }
        }

        return \Schema::hasColumn('employees', 'related_user_id') ? 'related_user_id' : null;
    }

    protected static function parseIdValue($raw): array
    {
        if (is_numeric($raw)) {
            return (int) $raw ? [(int) $raw] : [];
        }
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        if (array_key_exists('value', $decoded)) {
            $decoded = is_array($decoded['value']) ? $decoded['value'] : [$decoded['value']];
        }
        $ids = [];
        foreach ($decoded as $v) {
            if (is_numeric($v) && (int) $v) {
                $ids[] = (int) $v;
            }
        }

        return $ids;
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class, 'car_id')->orderBy('id', 'asc');
    }

    public function emergency_fund_records()
    {
        return $this->hasMany(FundRecord::class, 'car_id')->orderBy('id', 'asc');
    }
    
    public function sync_history($field, $new_value)
    {
        $objects = \App\Models\History::saveForObject('employees', array(['id' => $this->id, $field => $new_value]), false);
    }

}
