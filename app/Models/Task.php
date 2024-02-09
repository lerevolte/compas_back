<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Providers\CRest;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class Task extends Model
{
    use FieldValue, ModelActions, SoftDeletes;

    //protected $fillable = ['store_name', 'is_store', 'is_supply', 'is_tc', 'is_address'];
    protected $guarded = ['id'];
    protected $table = 'logistic_tasks';

    public static function boot()
    {
       parent::boot();
       static::creating(function($model)
       {
            $user = Auth::user();
            if(!$model->user_id && $user)
                $model->user_id = $user->id;
       });
       static::updating(function($model)
        {

            if($model->getOriginal('client_id') != $model->client_id) {
                if($model->getOriginal('client_id')) {
                    if(is_array($model->getOriginal('client_id')))
                        $client_ids = $model->getOriginal('client_id');
                    else
                        $client_ids = json_decode($model->getOriginal('client_id'), true);
                    $clients = Client::whereIntegerInRaw('id', $client_ids)->get();
                    if(count($clients)) {
                        foreach ($clients as $client) {
                            if(is_array($client->task_id))
                                $client_tasks = $client->task_id;
                            else
                                $client_tasks = json_decode($client->task_id, true);
                            
                            if(is_array($client_tasks)) {
                                $k = array_search($model->id, $client_tasks);
                                unset($client_tasks[$k]);
                                $client->saveRelations('task_id', $client_tasks);
                                $client->task_id = json_encode($client_tasks);
                                $client->saveQuietly();
                            }
                        }
                    }
                }

                if($model->client_id) {
                    if(is_array($model->client_id))
                        $client_ids = $model->client_id;
                    else
                        $client_ids = json_decode($model->client_id, true);
                    if(is_array($client_ids)) {
                        $clients = Client::whereIntegerInRaw('id', $client_ids)->get();
                        if(count($clients)) {
                            foreach ($clients as $client) {
                                $client_tasks = array();
                                if($client->task_id) {
                                    if(is_array($client->task_id))
                                        $client_tasks = $client->task_id;
                                    else
                                        $client_tasks = json_decode($client->task_id, true);
                                }
                                if(!in_array($model->id, $client_tasks)) {
                                    $client_tasks[] = $model->id;
                                    $client->saveRelations('task_id', $client_tasks);
                                    $client->task_id = json_encode($client_tasks);
                                    $client->saveQuietly();
                                }
                            }
                        }
                    }
                }
            }
        });
        static::deleting(function($model){ 
            info('DELETING tasks');
            $model->clients()->sync([]);
            return true; // let the delete go through
        });
       // static::updating(function($model)
       // {
       //     $user = Auth::user();
       //     $model->updated_by = $user->id;
       // });
   }

    public function status() {
        return \DB::table('field_values')->where('id', $this->point_status)->first();
    }
    public function generateLink() {
        $random_link = substr(md5(microtime()),rand(0,26),12);
        if (self::where('link', $random_link)->count() > 0) self::generateLink();
        $this->link = $random_link;
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'logistic_task_client');
    }

}
