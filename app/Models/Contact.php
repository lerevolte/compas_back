<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FieldValue, App\Traits\ModelActions, App\Traits\ColorGenerator;

class Contact extends Model
{
    use FieldValue, ModelActions, ColorGenerator, SoftDeletes;

    protected $table = 'contacts';
    protected $guarded = ['id'];

    public const B24_PUSH_FIELDS = ['name', 'emails', 'phones', 'contact_type'];
    public const TYPE_FIELD_TITLE = 'Тип контакта';
    public const TYPE_BY_ENTITY = ['deals' => 'Клиент', 'supplier_orders' => 'Поставщик'];

    public static function addType($contactIds, string $label): int
    {
        return \App\Services\TypeTagService::add('contacts', self::TYPE_FIELD_TITLE, $contactIds, $label, function (int $id, string $field) {
            if (!class_exists(\Modules\Bitrix24\Services\B24EntitySync::class) || \Modules\Bitrix24\Services\B24EntitySync::$muted) {
                return;
            }
            $contact = self::find($id);
            if (!$contact || !$contact->b24_id) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24EntitySync::make()?->pushContact($contact, [$field]);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('contact type push failed', ['contact_id' => $id, 'error' => $e->getMessage()]);
            }
        });
    }

    public static function tagFrom(string $slug, $contactIds): int
    {
        $label = self::TYPE_BY_ENTITY[$slug] ?? null;
        return $label ? self::addType($contactIds, $label) : 0;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = \Auth::user();
            if (!$model->user_id && $user) {
                $model->user_id = $user->id;
            }
        });

        static::saving(function ($model) {
            foreach (['emails', 'phones', 'company_id', 'contact_type'] as $col) {
                if (is_array($model->{$col})) {
                    $model->{$col} = json_encode(array_values(array_filter(
                        $model->{$col},
                        fn ($v) => $v !== null && $v !== ''
                    )), JSON_UNESCAPED_UNICODE);
                }
            }
        });

        static::saved(function ($model) {
            $changed = array_intersect(array_keys($model->getChanges()), self::B24_PUSH_FIELDS);
            if (!count($changed) || !$model->b24_id) {
                return;
            }
            if (!class_exists(\Modules\Bitrix24\Services\B24EntitySync::class)) {
                return;
            }
            if (\Modules\Bitrix24\Services\B24EntitySync::$muted) {
                return;
            }
            try {
                \Modules\Bitrix24\Services\B24EntitySync::make()?->pushContact($model, $changed);
            } catch (\Throwable $e) {
                \Log::channel('bitrix24')->warning('contact push failed', ['contact_id' => $model->id, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine(), 'changed' => $changed]);
            }
        });
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_contact');
    }

    public function deals()
    {
        return $this->belongsToMany(Deal::class, 'contact_deal');
    }

    public function sync_history($field, $new_value)
    {
        \App\Models\History::saveForObject('contacts', [['id' => $this->id, $field => $new_value]], false);
    }
}
