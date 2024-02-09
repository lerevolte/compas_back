<?php

namespace Modules\Wialon\Entities;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Config extends Model
{
    protected $table = 'wialon_config';
    protected $guarded = ['id'];

    public function getParams()
    {
        $config = json_decode($this->config, true);

        return $config;
    }

    public function setParams($new_config)
    {
        
        $config = json_decode($this->config, true);
        // if(isset($config['params'])) {
        //     foreach ($config['params'] as $key => $value) {
        //         if(isset($new_config['params'][$key]))
        //             $config['params'][$key] = $new_config['params'][$key];
        //     }
        //     if(isset($new_config['values'])) {
        //         $config['values'] = $new_config['values'];
        //     }
        // } else {
            $config = $new_config;
        //}
        $this->config = json_encode($config, JSON_UNESCAPED_UNICODE);
        $this->save();

        return;
    }

}
