<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Journal\Entities\Record;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Car extends Model
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

            
        });
        static::updated(function($model)
        {
        });
        static::updating(function($model)
        {
            $choosed_objects = \DB::table('choosed_objects')->get()->keyBy('entity');
            // array(
            //     'employees' => array(
            //         [
            //             'object_id',
            //             'entity',
            //             'relations'
            //         ],
            //         [
            //             'object_id',
            //             'entity',
            //             'relations'
            //         ],
            //     )
            // )
            if($model->getOriginal('employee_id') != $model->employee_id) {
                /* init choosed objects */
                $choosed_objects = \DB::table('choosed_objects')->where('entity', 'employees')->get();
                /* init choosed objects */
                
                if($model->getOriginal('employee_id')) {
                    if(is_array($model->getOriginal('employee_id')))
                        $employee_ids = $model->getOriginal('employee_id');
                    else
                        $employee_ids = json_decode($model->getOriginal('employee_id'), true);
                    
                    /* remove choosed objects */
                    foreach($employee_ids as $employee_id) {
                        $choosed_object = $choosed_objects->firstWhere('object_id', $employee_id);
                        if($choosed_object) {
                            $related_entities = json_decode($choosed_object->relation, true);

                            if(isset($related_entities[$model->getTable()])) {
                                unset($related_entities[$model->getTable()]);
                                \DB::table('choosed_objects')->where('id', $choosed_object->id)->update([
                                    'relation' => json_encode($related_entities)
                                ]);
                            }
                        }
                    }
                    /* remove choosed objects */

                    $employees = Employee::whereIntegerInRaw('id', $employee_ids)->get();
                    if(count($employees)) {
                        foreach ($employees as $employee) {
                            if(is_array($employee->car_id))
                                $employee_cars = $employee->car_id;
                            else
                                $employee_cars = json_decode($employee->car_id, true);
                            
                            if(is_array($employee_cars)) {
                                $k = array_search($model->id, $employee_cars);
                                unset($employee_cars[$k]);
                                $employee->saveRelations('car_id', $employee_cars);
                                $employee->car_id = json_encode($employee_cars);
                                $employee->saveQuietly();
                            }
                        }
                    }
                }

                if($model->employee_id) {

                    /* init choosed objects */
                    $choosed_objects = \DB::table('choosed_objects')->where('entity', 'employees')->get();
                    /* init choosed objects */
                    if(is_array($model->employee_id))
                        $employee_ids = $model->employee_id;
                    else
                        $employee_ids = json_decode($model->employee_id, true);
                    foreach($employee_ids as $k => $employee_id) {
                        if(!$employee_id) {
                            unset($employee_ids[$k]);
                        }
                    }
                    $model->employees()->sync($employee_ids);
                    $employees = array();
                    $related_entities = array();
                    /*add choosed objects*/
                    foreach($employee_ids as $employee_id) {
                        $choosed_object = $choosed_objects->firstWhere('object_id', $employee_id);
                        if($choosed_object) {
                            $related_entities = json_decode($choosed_object->relation, true);
                            if(!isset($related_entities[$model->getTable()])) {
                                $related_entities[$model->getTable()] = $model->getTable();

                                \DB::table('choosed_objects')->where('id', $choosed_object->id)->update([
                                    'relation' => json_encode($related_entities)
                                ]);
                            }
                        } else {
                            $related_entities[$model->getTable()] = $model->getTable();
                            \DB::table('choosed_objects')->insert([
                                'object_id' => $employee_id,
                                'entity' => 'employees',
                                'relation' => json_encode($related_entities)
                            ]);
                        }
                    }
                    /*add choosed objects*/
                    if(is_array($employee_ids)) {
                        $employees = Employee::whereIntegerInRaw('id', $employee_ids)->get();
                        if(count($employees)) {
                            foreach ($employees as $employee) {
                                $employee_cars = array();
                                if($employee->car_id) {
                                    if(is_array($employee->car_id))
                                        $car_employees = $employee->car_id;
                                    else
                                        $car_employees = json_decode($employee->car_id, true);
                                }
                                if(!in_array($model->id, $employee_cars)) {
                                    $employee_cars[] = $model->id;
                                    $employee->saveRelations('car_id', $employee_cars);
                                    $employee->car_id = json_encode($employee_cars);
                                    $employee->saveQuietly();
                                }
                            }
                        }
                    }
                }
            }

            if($model->getOriginal('company_id') != $model->company_id) {
                /* init choosed objects */
                $choosed_objects = \DB::table('choosed_objects')->where('entity', 'companies')->get();
                /* init choosed objects */

                if($model->getOriginal('company_id')) {
                    /* remove choosed objects */
                    $choosed_object = $choosed_objects->firstWhere('object_id', $model->getOriginal('company_id'));
                    if($choosed_object) {
                        $related_entities = json_decode($choosed_object->relation, true);
                        if(isset($related_entities[$model->getTable()])) {
                            unset($related_entities[$model->getTable()]);
                            \DB::table('choosed_objects')->where('id', $choosed_object->id)->update([
                                'relation' => json_encode($related_entities)
                            ]);
                        }
                    }
                    /* remove choosed objects */
                    $company = Company::find($model->getOriginal('company_id'));
                    if(is_array($company->car_id))
                        $company_cars = $company->car_id;
                    else
                        $company_cars = json_decode($company->car_id, true);
                    if(is_array($company_cars)) {
                        $k = array_search($model->id, $company_cars);
                        unset($company_cars[$k]);
                        $company->saveRelations('car_id', $company_cars);
                        $company->car_id = json_encode($company_cars);
                        $company->saveQuietly();
                    }
                }

                if($model->company_id) {
                    /*add choosed objects*/
                    $related_entities = array();
                    $choosed_object = $choosed_objects->firstWhere('object_id', $model->company_id);
                    if($choosed_object) {
                        $related_entities = json_decode($choosed_object->relation, true);
                        if(!isset($related_entities[$model->getTable()])) {
                            $related_entities[$model->getTable()] = $model->getTable();
                            \DB::table('choosed_objects')->where('id', $choosed_object->id)->update([
                                'relation' => json_encode($related_entities)
                            ]);
                        }
                    } else {
                        $related_entities[$model->getTable()] = $model->getTable();
                        \DB::table('choosed_objects')->insert([
                            'object_id' => $model->company_id,
                            'entity' => 'companies',
                            'relation' => json_encode($related_entities)
                        ]);
                    }
                    /*add choosed objects*/
                    $company = $model->company;
                    if(is_array($company->car_id))
                        $company_cars = $company->car_id;
                    else
                        $company_cars = json_decode($company->car_id, true);
                    if(is_array($company_cars)) {
                        if(!in_array($model->id, $company_cars)) {
                            $company_cars[] = $model->id;
                            $company->saveRelations('car_id', $company_cars);
                            $company->car_id = json_encode($company_cars);
                            $company->saveQuietly();
                        }
                    } else {
                        $company->saveRelations('car_id', [$model->id]);
                        $company->car_id = json_encode([$model->id]);
                        $company->saveQuietly();
                    }
                }
            }


            if($model->sts_number && $model->number && !$model->carsmonitoring_id/* && $model->getOriginal('sts_number') != $model->sts_number && $model->getOriginal('number') != $model->number*/) {
                $res = \Modules\Gibdd\Entities\Module::addCar([
                    'stsnum' => $model->sts_number,
                    'regnum' => $model->number
                ]);
                if(isset($res['id'])) {
                    $model->carsmonitoring_id = $res['id'];
                }
            }
        });
        static::deleting(function($model){ 
            $model->employees()->sync([]);
            return true; // let the delete go through
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }



    // public function carrier()
    // {
    //     return $this->belongsTo(\App\Models\Carrier::class);
    // }
    
    public function journal_records()
    {
        return $this->hasMany(Record::class, 'car_id');
    }

    public function mileages()
    {
        return $this->hasMany(Mileage::class, 'car_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'car_employee');
    }

}
