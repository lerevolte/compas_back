<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Journal\Entities\Record;


class Client extends Model
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
            if($model->getOriginal('task_id') != $model->task_id) {
                if($model->getOriginal('task_id')) {
                    if(is_array($model->getOriginal('task_id')))
                        $task_ids = $model->getOriginal('task_id');
                    else
                        $task_ids = json_decode($model->getOriginal('task_id'), true);
                    $tasks = Task::whereIntegerInRaw('id', $task_ids)->get();
                    if(count($tasks)) {
                        foreach ($tasks as $task) {
                            if(is_array($task->client_id))
                                $task_clients = $task->client_id;
                            else
                                $task_clients = json_decode($task->client_id, true);
                            
                            if(is_array($task_clients)) {
                                $k = array_search($model->id, $task_clients);
                                unset($task_clients[$k]);
                                $task->saveRelations('client_id', $task_clients);
                                $task->client_id = json_encode($task_clients);
                                $task->saveQuietly();
                            }
                        }
                    }
                }

                if($model->task_id) {
                    if(is_array($model->task_id))
                        $task_ids = $model->task_id;
                    else
                        $task_ids = json_decode($model->task_id, true);
                    if(is_array($task_ids)) {
                        $tasks = Task::whereIntegerInRaw('id', $task_ids)->get();
                        if(count($tasks)) {
                            foreach ($tasks as $task) {
                                $task_clients = array();
                                if($task->client_id) {
                                    $task_clients = is_array($task->client_id) ? $task->client_id : json_decode($task->client_id, true);
                                }
                                if(!in_array($model->id, $task_clients)) {
                                    $task_clients[] = $model->id;
                                    $task->saveRelations('client_id', $task_clients);
                                    $task->client_id = json_encode($task_clients);
                                    $task->saveQuietly();
                                }
                            }
                        }
                    }
                }
            }
        });
        static::deleting(function($model){ 
            info('DELETING');
            $model->logistic_tasks()->sync([]);
            return true; // let the delete go through
        });
    }
    
    public function logistic_tasks()
    {
        return $this->belongsToMany(Task::class, 'logistic_task_client');
    }

    


}
