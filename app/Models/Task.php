<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Providers\CRest;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator, App\Traits\HasRelationFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use App\Models\Route;

class Task extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes, HasRelationFields;

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
            if (empty($model->number) && \Schema::hasColumn($model->getTable(), 'number')) {
                $model->number = \App\Services\DocumentNumber::next();
            }
       });

       static::saved(function($model)
       {
            $changes = $model->getChanges();
            if (array_key_exists('deal_id', $changes) || ($model->wasRecentlyCreated && $model->deal_id)) {
                try {
                    \App\Services\ShipmentService::syncDealLink($model->getTable(), (int) $model->id, $model->deal_id, $model->wasRecentlyCreated ? null : $model->getOriginal('deal_id'));
                } catch (\Throwable $e) {
                }
            }
            if (array_key_exists(\App\Services\ShipmentService::ACTION_FIELD, $changes) && !$model->wasRecentlyCreated) {
                \App\Services\ShipmentService::forgetLoading($model->getTable(), (int) $model->id);
                try {
                    \App\Services\ShipmentService::recalcForSource($model->getTable(), (int) $model->id);
                } catch (\Throwable $e) {
                }
            }
            if (array_key_exists('products', $changes) || ($model->wasRecentlyCreated && $model->products)) {
                try {
                    \App\Services\ShipmentService::recalcForSource('logistic_tasks', (int) $model->id);
                } catch (\Throwable $e) {
                }
                try {
                    $model->recalcServicesPrice();
                } catch (\Throwable $e) {
                }
                \App\Services\ProductPriceService::recalcFromChange($model->products, $model->wasRecentlyCreated ? null : $model->getOriginal('products'));
            }
       });

       static::saving(function($model)
       {
            if (\Schema::hasColumn($model->getTable(), 'deal_id')) {
                $model->deal_id = \App\Services\ShipmentService::normalizeDealId($model->deal_id);
            }
            if ($model->isDirty(\App\Services\ShipmentService::ACTION_FIELD) && $model->{\App\Services\ShipmentService::ACTION_FIELD} !== null) {
                $model->{\App\Services\ShipmentService::ACTION_FIELD} = \App\Services\ShipmentService::taskActionValue($model->{\App\Services\ShipmentService::ACTION_FIELD});
            }
            if (is_array($model->employee_id)) {
                $ids = array_values(array_map('intval', array_filter($model->employee_id, 'is_numeric')));
                $model->employee_id = json_encode($ids);
            }
       });

       static::saving(function($model)
       {
            if ($model->isDirty('route_id') && $model->route_id) {
                $route = Route::find($model->route_id);
                if ($route && $route->date) {
                    $model->delivery_date = $route->date;
                }
                if ($route) {
                    $routeEmployees = $route->employeeIds();
                    if (count($routeEmployees)) {
                        $model->employee_id = json_encode($routeEmployees);
                    }
                }
                if ($model->sort === null) {
                    $query = self::where('route_id', $model->route_id);
                    if ($model->id) {
                        $query->where('id', '!=', $model->id);
                    }
                    $model->sort = (int) $query->max('sort') + 1;
                }
            }
       });

       static::saved(function($model) {
            if ($model->isDirty('route_id') && $model->route_id && \Schema::hasTable('logistic_task_employee')) {
                $route = Route::find($model->route_id);
                $routeEmployees = $route ? $route->employeeIds() : [];
                if (count($routeEmployees)) {
                    $model->employees()->sync($routeEmployees);
                }
            }
       });

       static::saved(function($model) {

            if ($model->isDirty('route_id')) {
                $newRouteId = $model->route_id;
                $oldRouteId = $model->getOriginal('route_id');

                if ($oldRouteId) {
                    $oldRoute = Route::find($oldRouteId);
                    if ($oldRoute) $oldRoute->recalculateTotals();
                }

                if ($newRouteId) {
                    $newRoute = Route::find($newRouteId);
                    if ($newRoute) $newRoute->recalculateTotals();
                }
            } else {

                if ($model->route_id && ($model->isDirty('weight') || $model->isDirty('volume') || $model->isDirty('delivery_price'))) {
                    if ($model->route) {
                        $model->route->recalculateTotals();
                    }
                }
            }
       });

       static::deleted(function($model) {
           if ($model->route_id) {
               $route = Route::find($model->route_id);
               if ($route) $route->recalculateTotals();
           }
       });

       static::updating(function($model)
        {

            if($model->getOriginal('client_id') != $model->client_id) {

            }
        });
        static::deleting(function($model){

        });

    }

    public function status()
    {

        return $this->belongsTo(\App\Models\FieldValue::class, 'point_status', 'id');
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

    public function contacts()
    {
        return $this->belongsToMany(\App\Models\Contact::class, 'logistic_task_contact', 'logistic_task_id', 'contact_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'logistic_task_employee', 'logistic_task_id', 'employee_id');
    }

    public function recalcServicesPrice(): void
    {
        if (!\Schema::hasColumn('products', 'product_type') || !\Schema::hasColumn($this->getTable(), 'delivery_price')) {
            return;
        }
        $products = json_decode((string) $this->products, true);
        if (!is_array($products)) {
            return;
        }
        $products = array_values(array_filter($products, 'is_array'));
        $services = \App\Services\ShipmentService::serviceIds(array_map(fn ($p) => $p['id'] ?? 0, $products));
        $total = 0.0;
        foreach ($products as $product) {
            if (!in_array((int) ($product['id'] ?? 0), $services, true)) {
                continue;
            }
            $count = isset($product['count']) ? (float) $product['count'] : 0;
            $price = isset($product['price']) ? (float) $product['price'] : 0;
            $total += $count * $price;
        }
        $formatted = $total > 0 ? rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') : (count($products) ? '0' : null);
        if ($formatted === null || (string) $this->delivery_price === $formatted) {
            return;
        }
        try {
            History::saveForObject($this->getTable(), [['id' => $this->id, 'delivery_price' => $formatted]], true, [], [], true);
        } catch (\Throwable $e) {
        }
        $this->delivery_price = $formatted;
        $this->timestamps = false;
        $this->saveQuietly();
        $this->timestamps = true;
    }

    public function setProducts(array $products)
    {
        $this->products = json_encode($products);
        $totalWeight = 0;
        $totalVolume = 0;
        foreach ($products as $product) {
            $count = isset($product['count']) ? (float)$product['count'] : 0;
            $w = isset($product['weight']) ? (float)$product['weight'] : 0;
            $v = isset($product['volume']) ? (float)$product['volume'] : 0;
            $totalWeight += $count * $w;
            $totalVolume += $count * $v;
        }
        $this->weight = $totalWeight;
        $this->volume = $totalVolume;

        $objects = History::saveForObject(
            $this->getTable(),
            array(
                array(
                    'id' => $this->id,
                    'products' => $this->products,
                    'weight' => $this->weight,
                    'volume' => $this->volume,
                )
            )
        );
        $this->save();
        $data = $this->getData($objects['changed_fields']);
        \App\Events\ObjectUpdated::dispatch('ObjectUpdated', $data);
    }

    public function getHtmlProducts()
    {
        $html = '';
        if($this->products) {
            $products = json_decode($this->products, true);
            foreach($products as $product) {
                $html.= (is_array($product['name']) ? $product['name'][0] : $product['name']).' <b>'.$product['count'].' шт.</b><br>';
            }
        }

        return $html;
    }

    public function sync_history($field, $new_value)
    {
        info('sync_history task');
        info($field);
        info($new_value);
        $objects = \App\Models\History::saveForObject('logistic_tasks', array(['id' => $this->id, $field => $new_value]), false);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}