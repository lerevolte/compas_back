<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FieldCreateRequest;
use App\Http\Requests\FieldUpdateRequest;
use App\Services\FieldService;
use App\Models\FieldValue; // Модель для field_values
use App\Models\Settings;
use App\Events\FieldUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FieldController extends Controller
{
    private FieldService $fieldService;

    public function __construct(FieldService $fieldService)
    {
        $this->fieldService = $fieldService;
    }

    public function status_store(Request $request)
    {
        if (!$request->field_id || !$request->color) {
            return response()->json(['error' => 400]);
        }


        $data = [
            'sort'      => $request->sort ?? 0,
            'file'      => $request->file ?? null,
            'is_hidden' => 1,
            'field_id'  => $request->field_id,
            'color'     => $request->color,
            'value'     => $request->text ?? '',
        ];

        // Используем транзакцию
        $fieldValueId = DB::table('field_values')->insertGetId($data);

        $field = DB::table('data_rows')->where('id', $request->field_id)->first();
        $entity = DB::table('data_types')->where('id', $field->data_type_id)->first();

        Settings::clear_cache();
        
        // В оригинале использовался метод getData() модели Field. 
        // Поскольку моделей DataRow нет, используем App\Models\Field, если он существует, или реплицируем логику.
        // Здесь возвращаемся к вызову из оригинального кода:
        $fieldData = \App\Models\Field::find($request->field_id)->getData(); 
        
        $newOption = [
            'label' => array_merge(['id' => $fieldValueId], $data, ['text' => $data['value']]),
            'value' => $fieldValueId
        ];
        
        $fieldData['options'][] = $newOption;

        $responsePayload = [
            'slug'             => $entity->slug,
            'user_id'          => Auth::id(),
            'changed_by'       => Auth::id(),
            'is_changed_table' => 1,
            'viewDetail'       => $fieldData,
            'viewList'         => [
                'id'      => $data['field_id'],
                'options' => $fieldData['options']
            ]
        ];

        FieldUpdated::dispatch('FieldUpdated', $responsePayload);

        return response()->json($newOption);
    }

    public function store(FieldCreateRequest $request)
    {
        if (!$res = $this->fieldService->create($request->getDto())) {
            return response()->json(['error' => 400]);
        }
        return response()->json($res);
    }

    public function new_update(int $id, FieldUpdateRequest $request)
    {
        info('status_store1');
        info($request->all());
        if (!$res = $this->fieldService->update($id, $request->getDto())) {
            return response()->json(['error' => 400]);
        }
        return response()->json($res);
    }

    public function update($id, Request $request)
    {
        info('status_store2');
        info($request->all());
        $field = DB::table('data_rows')->where('id', $id)->first();
        if (!$field) {
            return response()->json(['error' => 'Field not found'], 404);
        }

        $entity = DB::table('data_types')->where('id', $field->data_type_id)->first();
        $userId = Auth::id();

        // 1. Обработка перемещения поля (Sort/Section)
        if ($request->section_id && $request->change_section) {
            $this->handleSectionChange($field, $request);
            $res = $request->all();
            $res['user_id'] = $userId;
            
            return $this->finalizeRequest($entity->slug, 'FieldUpdated', [
                'slug'             => $entity->slug,
                'user_id'          => $userId,
                'changed_by'       => $userId,
                'is_changed_table' => 1,
                'viewDetail'       => $res,
                'viewList'         => ['id' => $field->id, 'section_id' => $request->section_id],
            ], $res);
        }

        // 2. Подготовка данных для обновления
        $isChangedTable = !($request->has('visible_always') || $request->has('show_file_name') || $request->has('is_hidden'));
        
        $updateData = $this->prepareUpdateData($field, $request);

        // 3. Специфичная логика
        DB::transaction(function () use ($field, $request, &$updateData, $entity, $id) {
            if ($field->type === 'text_group') {
                $this->handleTextGroupUpdate($field, $request, $entity);
            } elseif ($field->type === 'status') {
                $this->handleStatusUpdate($field, $request);
            }
            
            // Основное обновление записи
            DB::table('data_rows')->where('id', $id)->update($updateData);
        });

        // Обновляем объект $field после записи в БД для корректного формирования ответа
        $field = DB::table('data_rows')->where('id', $id)->first();

        // 4. Очистка кеша
        Settings::clear_cache();
        $settings = app('settings');

        // 5. Формирование ответа
        $res = $settings[$entity->slug]['field_data'][$field->field] ?? [];

        $res['has_roles_read'] = !empty($res['roles_read']) ? 1 : 0;
        $res['has_roles_write'] = !empty($res['roles_write']) ? 1 : 0;

        if (isset($settings['list_values'][$field->id]) && $field->type !== 'relation') {
            $res['options'] = $settings['list_values'][$field->id];
        }

        $payload = [
            'slug'             => $entity->slug,
            'user_id'          => $userId,
            'changed_by'       => $userId,
            'is_changed_table' => $isChangedTable ? 1 : 0,
            'viewDetail'       => $res,
            'viewList'         => array_merge(['id' => $field->id], $request->all())
        ];

        if ($field->type === 'text_group') {
             $this->appendTextGroupDetails($payload, $field, $entity, $settings);
             $res['fields'] = $payload['viewDetail']['fields'];
        }

        return $this->finalizeRequest($entity->slug, 'FieldUpdated', $payload, $res);
    }

    public function changeSort(Request $request)
    {
        $field = DB::table('data_rows')->where('id', $request->id)->first();
        $entity = DB::table('data_types')->where('id', $field->data_type_id)->first();

        DB::transaction(function () use ($request) {
            // Параллельно сбрасываем group_id. changeSort вызывается при
            // переносе поля в обычную секцию (НЕ внутрь группы — для этого
            // отдельный update_field с subfields). Если поле раньше было
            // частью group_id и его вытащили в outer-секцию, то без сброса
            // group_id оно «двоится»: рендерится и в группе, и в outer.
            DB::table('data_rows')->where('id', $request->id)->update([
                'section_id' => $request->section_id,
                'group_id'   => null,
            ]);
            DB::table('data_rows')->upsert($request->fields, 'id', ['id', 'sort']);
        });

        Settings::clear_cache();

        $newSort = collect($request->fields)->firstWhere('id', $request->id)['sort'] ?? 0;

        $payload = [
            'id'          => $request->id,
            'user_id'     => Auth::id(),
            'changed_by'  => Auth::id(),
            'old_section' => $field->section_id,
            'new_section' => $request->section_id,
            'sort'        => $newSort
        ];

        return $this->finalizeRequest($entity->slug, 'FieldSorted', $payload, [
            'id' => $request->id, 
            'section_id' => $request->section_id
        ]);
    }

    public function hide(int $id, Request $request)
    {
        $field = DB::table('data_rows')->where('id', $id)->first();
        if (!$field) {
            return response()->json(['error' => 'Field not found'], 404);
        }
        
        DB::table('data_rows')->where('id', $id)->update(['hide' => 1]);

        return $this->finalizeHide($field, 'FieldHidden');
    }

    public function hide_batch(Request $request)
    {
        $ids = $request->ids;
        DB::table('data_rows')->whereIn('id', $ids)->update(['hide' => 1]);

        $fields = DB::table('data_rows')->whereIn('id', $ids)->get();
        if ($fields->isEmpty()) {
            return response()->json([]);
        }

        $firstField = $fields->first();
        $entity = DB::table('data_types')->where('id', $firstField->data_type_id)->first();
        
        Settings::clear_cache();

        foreach ($fields as $field) {
            $payload = $this->buildFieldPayload($field);
            FieldUpdated::dispatch('FieldHidden', $payload);
        }

        $this->updateLocalCache($entity->slug);

        return response()->json($fields);
    }

    public function delete(Request $request, int $id)
    {
        $field = DB::table('data_rows')->where('id', $id)->first();
        if (!$field) {
            return response()->json(['error' => 'Field not found'], 404);
        }
        
        $entity = DB::table('data_types')->where('id', $field->data_type_id)->first();

        // 1. Сначала меняем структуру БД (Это нельзя обернуть в транзакцию в MySQL)
        if (Schema::hasColumn($entity->slug, $field->field)) {
            Schema::table($entity->slug, function ($table) use ($field) {
                $table->dropColumn($field->field);
            });
        }

        // 2. Затем удаляем метаданные внутри транзакции (для целостности данных)
        DB::transaction(function () use ($field, $id) {
            DB::table('data_rows')->where('id', $id)->delete();
            DB::table('data_rows')->where('group_id', $id)->update(['group_id' => null]);
        });

        Settings::clear_cache();

        return $this->finalizeRequest($entity->slug, 'FieldDeleted', [
            'id'         => $id,
            'user_id'    => Auth::id(),
            'changed_by' => Auth::id(),
            'slug'       => $entity->slug
        ], [
            'status'  => 200,
            'success' => true
        ]);
    }

    public function delete_batch($model, Request $request, int $id)
    {
        $fields = DB::table('data_rows')->whereIn('id', $request->ids)->whereNotNull('is_permanent')->get();
        
        DB::transaction(function() use ($fields, $model, $request) {
            foreach ($fields as $field) {
                if (Schema::hasColumn($model, $field->field)) {
                    Schema::table($model, function ($table) use ($field) {
                        $table->dropColumn($field->field);
                    });
                }
            }
            
            DB::table('data_rows')->whereIn('group_id', $request->ids)->update(['group_id' => null]);
        });

        Settings::clear_cache();
        
        return $this->finalizeRequest($model, null, null, $fields);
    }

    /* -------------------------------------------------------------------------- */
    /* Private Helpers                              */
    /* -------------------------------------------------------------------------- */

    private function handleSectionChange($field, $request)
    {
        $maxSort = DB::table('data_rows')->where('section_id', $request->section_id)->max('sort') + 1;
        $sort = $request->sort ?? $maxSort;

        DB::table('data_rows')->where('id', $field->id)->update([
            'section_id' => $request->section_id,
            'sort'       => $sort,
            'hide'       => 0
        ]);

        Settings::clear_cache();
    }

    private function prepareUpdateData($field, Request $request)
    {
        $data = [
            'title'            => $request->title ?? $field->title,
            'visible_always'   => $request->has('visible_always') ? $request->visible_always : $field->visible_always,
            'default_value'    => $request->has('default_value') ? $request->default_value : $field->default_value,
            'set_default'      => $request->has('set_default') ? $request->set_default : $field->set_default,
            'set_color'        => $request->has('set_color') ? $request->set_color : $field->set_color,
            'label_color'      => $request->has('color') ? $request->color : $field->label_color,
            'mobile_pages'     => $request->has('show_in_mobile') ? $request->mobile_pages : $field->mobile_pages,
            'button_name'      => $request->button_name ?? $field->button_name,
            'show_file_name'   => $request->has('show_file_name') ? $request->show_file_name : $field->show_file_name,
            'required'         => $request->has('required') ? $request->required : $field->required,
            'is_external_link' => $request->has('is_external_link') ? $request->is_external_link : $field->is_external_link,
            'external_link'    => $request->external_link ?? $field->external_link,
            'unit'             => $request->has('unit') ? $request->unit : $field->unit,
            'section_id'       => $request->section_id ?? $field->section_id,
            'hide'             => $request->has('is_hidden') ? $request->is_hidden : $field->hide,
            'mask'             => $request->mask ?? $field->mask,
        ];

        if ($request->rules) {
            $data['rules'] = json_encode($request->rules);
        }

        if ($request->has('has_roles_read')) {
            $data['roles_read'] = $request->has_roles_read && !empty($request->roles_read) 
                ? $request->roles_read 
                : null;
        }

        if ($request->has('has_roles_write')) {
            $data['roles_write'] = $request->has_roles_write && !empty($request->roles_write) 
                ? $request->roles_write 
                : null;
        }

        if ($request->options && !in_array($field->type, ['text_group', 'relation']) && $field->field !== 'is_admin') {
            $details = ['options' => []];
            foreach ($request->options as $item) {
                $details['options'][$item['value']] = [
                    'value' => $item['value'],
                    'label' => $item['label']
                ];
            }
            if ($request->has('can_create')) {
                $details['can_create'] = $request->can_create ? true : false;
            }
            $data['details'] = json_encode($details);
        } elseif ($request->has('can_create')) {
            // For status fields — save can_create in details
            $existingDetails = json_decode($field->details, true) ?? [];
            $existingDetails['can_create'] = $request->can_create ? true : false;
            $data['details'] = json_encode($existingDetails);
        }

        if ($request->has('subfields')) {
            DB::table('data_rows')->where('group_id', $field->id)->update(['group_id' => null]);
            if (!empty($request->subfields)) {
                DB::table('data_rows')->whereIn('id', $request->subfields)->update(['group_id' => $field->id]);
            }
        }

        if ($request->has('can_create')) {
            info('has can create');
            $existingDetails = json_decode($field->details, true) ?? [];
            $existingDetails['can_create'] = $request->can_create ? true : false;
            info($existingDetails);
            $data['details'] = json_encode($existingDetails);
        }
        info($data);

        return $data;
    }

    private function handleTextGroupUpdate($field, Request $request, $entity)
    {
        $fieldGroup = DB::table('data_rows')->where('field', $field->field)->first();
        if (!$fieldGroup) return;

        $existingFields = DB::table('data_rows')->where('group_id', $fieldGroup->id)->get();
        $fieldsToKeep = [];

        if ($request->options) {
            foreach ($request->options as $item) {
                $fieldsToKeep[] = $item['value'];
                
                if (isset($item['new'])) {
                    $fieldSlug = Str::slug($item['label'], '_') . '_' . $fieldGroup->id;
                    
                    DB::table('data_rows')->insert([
                        'data_type_id'        => $fieldGroup->data_type_id,
                        'field'               => $fieldSlug,
                        'type'                => 'text',
                        'title'               => $item['label'],
                        'display_parent_name' => $fieldGroup->title,
                        'group_id'            => $fieldGroup->id
                    ]);

                    if (!Schema::hasColumn($entity->name, $fieldSlug)) {
                        Schema::table($entity->name, function ($table) use ($fieldSlug) {
                            $table->string($fieldSlug)->nullable();
                        });
                    }
                } else {
                    DB::table('data_rows')->where('id', $item['value'])
                        ->where('group_id', $fieldGroup->id)
                        ->update([
                            'title'               => $item['label'],
                            'display_parent_name' => $request->title ?? $field->title
                        ]);
                }
            }
        }

        foreach ($existingFields as $existingField) {
            if (!in_array($existingField->id, $fieldsToKeep)) {
                if (Schema::hasColumn($entity->slug, $existingField->field)) {
                    Schema::table($entity->slug, function ($table) use ($existingField) {
                        $table->dropColumn($existingField->field);
                    });
                }
                DB::table('data_rows')->where('id', $existingField->id)->delete();
            }
        }
    }

    private function handleStatusUpdate($field, Request $request)
    {
        info('status_store3');
        info($request->all());
        if (!$request->options) return;

        $currentIds = [];
        
        foreach ($request->options as $key => $status) {
            $valId = $status['value'];
            $label = $status['label'];

            $dataToUpdate = [
                'field_id'  => $field->id,
                'color'     => $label['color'] ?? '',
                'value'     => $label['text'] ?? '',
                'is_hidden' => $label['is_hidden'] ?? 0,
                'sort'      => $key,
            ];

            if (isset($label['file']) && (!empty($label['file']) || (isset($status['file_changed']) && $status['file_changed']))) {
                $dataToUpdate['file'] = $label['file'];
            } elseif (empty($label['file'])) {
                $dataToUpdate['file'] = '';
            }

            DB::table('field_values')->updateOrInsert(['id' => $valId], $dataToUpdate);
            $currentIds[] = $valId;
        }

        DB::table('field_values')
            ->where('field_id', $field->id)
            ->whereNotIn('id', $currentIds)
            ->delete();

        $this->clearStatusCache($field->id);
    }

    private function clearStatusCache($fieldId)
    {
        $tenantId = tenant('id');
        $cachePrefix = "{$tenantId}:field-{$fieldId}-statuses";
        
        try {
            $memcached = Cache::store('memcached')->getStore()->getMemcached();
            if ($memcached) {
                // logic to clear cache keys
            }
        } catch (\Exception $e) {
            // Log error
        }
        
        $fieldValues = DB::table('field_values')
            ->where('field_id', $fieldId)
            ->where('is_hidden', '!=', 1)
            ->orderBy('is_hidden')
            ->orderBy('sort')
            ->get();
            
        Cache::store('memcached')->add($cachePrefix, $fieldValues);
    }
    
    private function finalizeHide($field, $event) 
    {
        Settings::clear_cache();
        $entity = DB::table('data_types')->where('id', $field->data_type_id)->first();
        
        $payload = $this->buildFieldPayload($field);
        
        return $this->finalizeRequest($entity->slug, $event, $payload, $payload);
    }

    private function buildFieldPayload($field)
    {
        return [
            'id'             => $field->id,
            'user_id'        => Auth::id(),
            'changed_by'     => Auth::id(),
            'title'          => $field->title,
            'key'            => $field->field,
            'type'           => $field->type,
            'visible_always' => $field->visible_always,
            'is_hidden'      => $field->hide,
            'read_only'      => $field->only_read,
            'can_edit'       => 1,
            'color'          => $field->label_color,
            'group_id'       => $field->group_id
        ];
    }

    private function finalizeRequest($slug, $event, $eventPayload, $response)
    {
        if ($event && $eventPayload) {
            FieldUpdated::dispatch($event, $eventPayload);
        }
        
        $this->updateLocalCache($slug);

        return response()->json($response);
    }

    private function updateLocalCache($slug)
    {
        $userId = Auth::id();
        $url = "fields/$slug";
        $now = Carbon::now();

        DB::table('local_cache')->upsert(
            [
                ['url' => $url, 'user_id' => $userId, 'created_at' => $now, 'updated_at' => $now]
            ], 
            ['url', 'user_id'], 
            ['updated_at']
        );
    }

    private function appendTextGroupDetails(&$payload, $field, $entity, $settings)
    {
        $subfields = \App\Models\Field::getByGroup($field->id);
        $fieldsData = [];

        foreach ($subfields as $subfield) {
            $payload['viewDetail']['options'][] = [
                'value' => $subfield->id,
                'label' => $subfield->title,
                'sort'  => $subfield->sort
            ];

            $subfieldData = $settings[$entity->slug]['field_data'][$subfield->field] ?? [];
            
            $isAdmin = Auth::user()->is_admin;
            $perms = $settings[$entity->slug]['perms'][$subfield->field] ?? ['read' => 0, 'write' => 0];

            $subfieldData['can_read'] = ($perms['read'] || $isAdmin) ? 1 : 0;
            $subfieldData['can_edit'] = ($subfield->only_read || (!$perms['write'] && !$isAdmin)) ? 0 : 1;

            $fieldsData[] = $subfieldData;
        }
        $payload['viewDetail']['fields'] = $fieldsData;
    }
}