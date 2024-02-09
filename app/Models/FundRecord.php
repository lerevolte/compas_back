<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;


class FundRecord extends Model
{

    use FieldValue, ModelActions, SoftDeletes;

    protected $guarded = ['id'];
    protected $table = 'emergencyfund';
    
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
            if($model->isDirty('emergency_fund_start_day') || $model->isDirty('emergency_fund_end_day')) {
                if($model->isDirty('emergency_fund_end_day')) {
                    $next_records = FundRecord::whereDate('date', '>', $model->date)->get();
                    $data = array();
                    $prev_record_end_day = $model->emergency_fund_end_day;
                    foreach ($next_records as $record) {
                        $data[] = array(
                            'id' => $record->id,
                            'emergency_fund_start_day' => $prev_record_end_day,
                            'emergency_fund_end_day' => $prev_record_end_day + $record->emergency_fund_day
                        );
                        $prev_record_end_day = $prev_record_end_day + $record->emergency_fund_day;
                    };
                    FundRecord::upsert($data, 'id', ['emergency_fund_start_day', 'emergency_fund_end_day']);
                };
                $model->emergency_fund_day = $model->emergency_fund_end_day - $model->emergency_fund_start_day;
                $model->saveQuietly();
            }
        });
    }
    
}
