<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SabyConfig extends Model
{
    protected $table = 'saby_config';
    protected $guarded = ['id'];

    public function getParams(): array
    {
        $config = json_decode((string) $this->config, true);

        return is_array($config) ? $config : [];
    }

    public function setParams(array $params): void
    {
        $this->config = json_encode($params, JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    public function param(string $key, $default = null)
    {
        return $this->getParams()[$key] ?? $default;
    }

    public function mergeParams(array $patch): void
    {
        $this->setParams(array_merge($this->getParams(), $patch));
    }
}
