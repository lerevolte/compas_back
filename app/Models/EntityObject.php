<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FieldValue, App\Traits\ModelActions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use App\Helpers\ValueHelper;
use Illuminate\Support\Facades\Hash;

class EntityObject
{

    // Кэш проверок схемы на время запроса (Schema::hasTable/hasColumn ходят
    // в information_schema — не дёргаем их повторно для одной таблицы).
    protected static $relation_tables_cache = [];
    protected static $deleted_at_columns_cache = [];

    /**
     * Таблица, на которую смотрит relation-поле (details.table либо
     * relation_table), или null, если таблицы нет.
     */
    protected static function relationTableOf($field): ?string
    {
        $key = $field->id ?? ($field->field . ':' . ($field->relation_table ?? ''));
        if (array_key_exists($key, self::$relation_tables_cache)) {
            return self::$relation_tables_cache[$key];
        }
        $details = json_decode($field->details ?? '', true);
        $table = $details['table'] ?? ($field->relation_table ?: null);
        if (!$table || !\Schema::hasTable($table)) {
            $table = null;
        }
        return self::$relation_tables_cache[$key] = $table;
    }

    protected static function tableHasDeletedAt(string $table): bool
    {
        if (!array_key_exists($table, self::$deleted_at_columns_cache)) {
            self::$deleted_at_columns_cache[$table] = \Schema::hasColumn($table, 'deleted_at');
        }
        return self::$deleted_at_columns_cache[$table];
    }

    /**
     * Жива ли (не удалена) связанная запись одиночного relation-поля.
     * Проверяем по БД, а не по settings['list_values']: кэш настроек может
     * быть устаревшим (SettingsClearJob в очереди), и удалённая запись в нём
     * ещё числится живой.
     */
    protected static function relatedIsAlive($field, $id): bool
    {
        if (!$id || is_array($id)) return true;
        $table = self::relationTableOf($field);
        if (!$table) return true;
        $q = \DB::table($table)->where('id', $id);
        if (self::tableHasDeletedAt($table)) {
            $q->whereNull('deleted_at');
        }
        return $q->exists();
    }

    /**
     * Построить опцию (как элемент list_values) для связанной записи прямо
     * из таблицы (минуя SoftDeletes-scope и кэш настроек). Используется для
     * удалённых связей удалённого объекта и для живых записей, которых ещё
     * нет в (возможно устаревшем) кэше list_values.
     */
    protected static function listValueFromTable($field, $id)
    {
        $table = self::relationTableOf($field);
        if (!$table || !$id || is_array($id)) return null;

        $object = \DB::table($table)->where('id', $id)->first();
        if (!$object) return null;

        $text = $object->title
            ?? $object->display_name
            ?? $object->name
            ?? ('#' . $object->id);
        if (ValueHelper::isJson($text)) {
            $decoded = json_decode($text, true);
            $text = $decoded['value'] ?? $text;
        }
        if (isset($object->first_name)) {
            $text = trim($object->first_name . ' ' . ($object->last_name ?? ''));
        }

        $avatar = $object->avatar ?? ($object->photo ?? '');
        $avatar = $object->icon ?? $avatar;
        if ($avatar) {
            $decoded = json_decode($avatar, true);
            if (isset($decoded[0]['url'])) $avatar = $decoded[0]['url'];
        }

        $color = $object->color ?? '';
        if ($color !== '' && is_numeric($color)) {
            // Числовой color — ID записи field_values (палитра, см. routes)
            $fv = \DB::table('field_values')->where('id', (int) $color)->first();
            $color = $fv->color ?? '';
        }

        return [
            'label' => [
                'id' => $object->id,
                'sort' => 0,
                'file' => $avatar,
                'is_hidden' => 0,
                'field_id' => $field->id,
                'color' => $color,
                'text' => $text,
                'deleted' => (isset($object->deleted_at) && $object->deleted_at) ? 1 : 0,
            ],
            'value' => $object->id,
        ];
    }

    public static function detail($slug, $id, Request $request, $settings = null)
    {
        $settings = $settings ?: app('settings');
        $show_hints = Settings::hints();
        $guides = Settings::getGuidesWithCache();
        $data = [
            'title' => '',
            'deleted_at' => null,
            'columns' => [
                'column_1' => [],
                'column_2' => []
            ]
        ];

        $perms = [
            'read' => [],
            'write' => [],
        ];

        $entity = $settings['models'][$slug] ?? null;

        if (!$entity || !$entity->enable) {
            return [
                'error' => [
                    'message' => 'Entity not found',
                    'code' => 404
                ]
            ];
        }

        // Проверка аутентификации
        $isAuthenticated = auth()->check();
        $user = $isAuthenticated ? auth()->user() : null;
        $isAdmin = $isAuthenticated && $user->is_admin;
        $role = $isAuthenticated ? $user->role : null;

        $entity_class = $entity->model_name;
        $model_fields = $settings[$slug]['fields'];
        $fields_data = [];
        $permissions = [
            'read_p' => 'A',
            'create_p' => 'A',
            'update_p' => 'A',
            'delete_p' => 'A',
            'export_p' => 'A',
            'import_p' => 'A'
        ];
        if($isAuthenticated)
            $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $entity->id)->first()->toArray();
        // Получение текущего объекта
        if ($id) {
            $current = $entity_class::withTrashed()->where(['id' => $id])->first();
        } else {
            $current = new $entity_class;
            if ($isAuthenticated) {
                $current->user_id = $user->id;
            }
            if (!$id && $request->route_id && $slug == 'logistic_tasks') {
                $current->route_id = $request->route_id;
            }
            foreach ($model_fields as $mf) {
                if (in_array($mf->type, ['relation', 'file', 'text_group'])) continue;
                // Значение по умолчанию применяется только при активном чекбоксе
                // set_default (по аналогии с set_color): значение может быть
                // сохранено, но не участвовать в создании объектов (8461).
                if (empty($mf->set_default)) continue;
                if (!isset($mf->default_value) || $mf->default_value === null || $mf->default_value === '') continue;
                if ($current->{$mf->field} !== null && $current->{$mf->field} !== '') continue;
                $current->{$mf->field} = $mf->default_value;
            }

            // Специфичная логика для osago_polises
            if ($slug == 'osago_polises') {
                $local_module = \DB::table('modules')->where('slug', 'osago')->first();
                if ($local_module) {
                    $data_m = json_decode($local_module->config, true);
                    if (isset($data_m['fields'])) {
                        $config_fields = collect($data_m['fields'])->keyBy('key')->toArray();
                        $current->policyholder_name = $config_fields['policyholder_name']['value'] ?? null;
                        $current->policyholder_last_name = $config_fields['policyholder_last_name']['value'] ?? null;
                        $current->policyholder_patronymic = $config_fields['policyholder_patronymic']['value'] ?? null;
                        $current->policyholder_email = $config_fields['policyholder_email']['value'] ?? null;
                        $current->policyholder_phone = $config_fields['policyholder_phone']['value'] ?? null;
                        $current->policyholder_address = isset($config_fields['policyholder_address']['value']['text'])
                            ? json_encode($config_fields['policyholder_address']['value'])
                            : null;
                        $current->policyholder_passport_number = $config_fields['policyholder_passport_number']['value'] ?? null;
                        $current->policyholder_passport_series = $config_fields['policyholder_passport_series']['value'] ?? null;
                        $current->policyholder_birthday = $config_fields['policyholder_birthday']['value'] ?? null;
                    }
                }
            }
        }

        // Проверка прав доступа
        if ($request->user_id && $current->user_id != $request->user_id) {
            return [
                'error' => [
                    'message' => 'Forbidden',
                    'code' => 403
                ]
            ];
        }

        if (!$current) {
            return [
                'error' => [
                    'message' => 'Object not found',
                    'code' => 404
                ]
            ];
        }

        // Обработка заголовка
        $data['title'] = $current->name ?? '';
        $tenant = tenant('id');

        if (ValueHelper::isJson($data['title'])) {
            $data['title'] = json_decode($data['title'], true);
            $data['title'] = isset($data['title']['value'])
                ? ['name' => $data['title']['value'], 'key' => 'name']
                : ['name' => null, 'key' => 'name'];
        } else {
            $data['title'] = ['name' => $data['title'], 'key' => 'name'];
        }

        if ($request->is_copy) {
            $data['title'] = [
                'name' => "Копирование объекта $entity->title_singular",
                'key' => 'name'
            ];
        }

        if (!$id) {
            $data['title'] = [
                'name' => $entity->title_singular,
                'key' => 'name'
            ];
        }

        $name = $data['title']['name'];
        $data['header_title'] = $id
            ? "$name | $entity->title_plural | Compas.pro"
            : "Создание $entity->title_singular | $entity->title_plural | Compas.pro";

        if (isset($current->deleted_at) && $current->deleted_at) {
            $data['deleted_at'] = $current->deleted_at;
        }

        // Сам объект удалён — в его связях показываем и удалённые записи
        // (например, у удалённой компании — удалённые машины). У живых
        // объектов удалённые связи по-прежнему скрываются.
        $isTrashedCurrent = isset($current->deleted_at) && (bool) $current->deleted_at;

        // Обработка полей модели
        foreach ($model_fields as $field) {
            // Внешняя ссылка (аноним, без авторизации) не обладает ролями,
            // поэтому поля с ограничением видимости по ролям (roles_read)
            // скрываем целиком — для аноним-доступа perms не вычисляются, и
            // иначе такие поля протекали во внешнюю ссылку (8460).
            if (!$isAuthenticated
                && !empty($field->roles_read)
                && !in_array(trim((string) $field->roles_read), ['', '[]', '0'], true)) {
                continue;
            }
            if (!array_key_exists($field->field, $fields_data) &&
                (!isset($settings[$slug]['perms'][$field->field]['read']) ||
                 $settings[$slug]['perms'][$field->field]['read'] ||
                 $isAdmin))
            {
                if ($field->type == 'relation' && $field->relation_table && !$settings['models'][$field->relation_table]->enable) {
                    continue;
                }

                $val = (string)($current->{$field->field} ?? '');
                $field_value = ValueHelper::isJson($val) && $field->field != 'products' && is_array(json_decode($val, true))
                    ? json_decode($val, true)
                    : $val;

                if ($field->type == 'relation' && $field->is_plural && $field->relation_table) {
                    $relation_table = $field->relation_table;
                    info($relation_table);
                    $relation_query = $current->{$relation_table}();
                    if ($isTrashedCurrent && in_array(SoftDeletes::class, class_uses_recursive($relation_query->getRelated()))) {
                        $relation_query = $relation_query->withTrashed();
                    }
                    // get()->pluck: pluck('id') на уровне запроса для
                    // belongsToMany даёт неоднозначный column id (join с pivot)
                    $field_value = $relation_query->get()->pluck('id')->toArray();
                }

                $fields_data[$field->field] = $settings[$slug]['field_data'][$field->field];
                $fields_data[$field->field]['can_read'] = $settings[$slug]['perms'][$field->field]['read'] || $isAdmin ? 1 : 0;
                $fields_data[$field->field]['can_edit'] = isset($data['deleted_at']) || $field->only_read ||
                    !$settings[$slug]['perms'][$field->field]['write'] && !$isAdmin ? 0 : 1;
                if ($slug == 'logistic_tasks' && $field->field == 'delivery_date' && $current->route_id && !$request->is_copy) {
                    $fields_data[$field->field]['can_edit'] = 0;
                }
                // Обработка значений полей
                if ($request->is_copy) {
                    if ($field->field == 'created_at') {
                        $field_value = \Carbon\Carbon::now();
                    } elseif ($field->field == 'id' || $field->field == 'updated_at') {
                        $field_value = null;
                    } elseif ($field->field == 'user_id' && $isAuthenticated) {
                        $field_value = $user->id;
                    } elseif ($slug == 'logistic_tasks' && $field->field == 'route_id') {
                        // При копировании задачи логистики не тащим привязанный маршрут:
                        // иначе delivery_date пересчитается от маршрута и затрёт введённую
                        // пользователем дату. Копия создаётся без маршрута.
                        $field_value = null;
                    }
                }
                $fields_data[$field->field]['guide'] = null;
                if(is_array($guides) && isset($guides['fields'][$slug][$field->field]) && $show_hints)
                    $fields_data[$field->field]['guide'] = $guides['fields'][$slug][$field->field];

                $fields_data[$field->field]['value'] = $field->field == 'password' ? '' : $field_value;

                // Дополнительная обработка для специфичных типов полей
                if ($field->type == 'file') {
                    if (!isset($field_value[0]) && $field_value) {
                        $fields_data[$field->field]['value'] = [$field_value];
                    } elseif (!isset($field_value[0]) && !$field_value) {
                        $fields_data[$field->field]['value'] = [];
                    } elseif (is_array($field_value)) {
                        $fields_data[$field->field]['value'] = array_filter($field_value);
                    }
                }

                $list_values = array();
                if(isset($settings['list_values'][$field->id]))
                    $list_values = $settings['list_values'][$field->id];
                if($field->type == 'relation' && $field->is_plural) {
                    $values = $field_value;
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                    if(is_array($values)) {
                        foreach($values as $val) {
                            if(isset($list_values[$val])) {
                                $fields_data[$field->field]['value']['value'][] = $list_values[$val]['value'];
                                $fields_data[$field->field]['value']['localOptions'][] = $list_values[$val];
                            } elseif(($table_option = self::listValueFromTable($field, $val))) {
                                // Записи нет в list_values: либо она удалена
                                // (а сам объект в корзине — pluck шёл withTrashed),
                                // либо кэш настроек устарел. Собираем подпись из БД.
                                $fields_data[$field->field]['value']['value'][] = $table_option['value'];
                                $fields_data[$field->field]['value']['localOptions'][] = $table_option;
                            }
                        }
                    }
                } elseif($field->type == 'relation' && $field_value && !is_array($field_value)
                    && !$isTrashedCurrent && !self::relatedIsAlive($field, $field_value)) {
                    // Связанная запись удалена, а сам объект жив — связь не
                    // показываем (даже если она ещё числится в устаревшем
                    // кэше list_values).
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                } elseif($field->type == 'relation' && isset($list_values[$field_value])) {
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                    $fields_data[$field->field]['value']['localOptions'] = array($list_values[$field_value]);
                    $fields_data[$field->field]['value']['value'] = array($list_values[$field_value]['value']);

                } elseif($field->type == 'relation' && $field_value && !is_array($field_value)
                    && ($table_option = self::listValueFromTable($field, $field_value))) {
                    // Значения нет в list_values: у удалённого объекта это
                    // удалённая связь (показываем), у живого — живая запись,
                    // не попавшая в устаревший кэш (тоже показываем; удалённые
                    // у живого отсечены веткой выше).
                    $fields_data[$field->field]['value'] = array(
                        'value' => array($table_option['value']),
                        'localOptions' => array($table_option)
                    );
                } elseif($field->type == 'relation') {
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                }


                if($field->type == 'relation' && $field->field != 'role_id') {

                    $fields_data[$field->field]['can_create'] = 1;

                    if(isset($permissions_all[$settings['models'][$field->relation_table]->id]['create_p']) && !$isAdmin)

                        $fields_data[$field->field]['can_create'] = $permissions_all[$settings['models'][$field->relation_table]->id]['create_p'] == 'N' ? 0 : 1;

                }

                if($field->type == 'relation' && $field->relation_table && isset($restrictions_tariff['objects'][$field->relation_table]['count'])) {
                    if($restrictions_tariff['objects'][$field->relation_table]['count'] <= \DB::table($field->relation_table)->whereNull('deleted_at')->count()) {
                        $fields_data[$field->field]['can_create'] = 0;
                    };
                }
                if(isset($settings['list_values'][$field->id])) {
                    $values = array();
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true);
                        if($field->is_plural && isset($fields_data[$field->field]['value']['value'])) {
                            foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                                // У удалённого объекта value может содержать ID
                                // удалённых записей — их нет в list_values.
                                if(isset($settings['list_values'][$field->id][$field_val]))
                                    $field_values[$field_val] = $settings['list_values'][$field->id][$field_val];
                            }
                        } elseif($current->{$field->field} && isset($settings['list_values'][$field->id][$current->{$field->field}])) {
                            $field_values[$current->{$field->field}] = $settings['list_values'][$field->id][$current->{$field->field}];
                        }
                        // Значение есть, но его нет в list_values — связанная
                        // запись удалена (list_values строится с whereNull
                        // deleted_at). Удалённые в опциях не показываем.
                    } else {
                        if(isset($settings[$slug]['options'][$field->field]))
                            $field_values = $settings[$slug]['options'][$field->field];
                        else
                            $field_values = $settings['list_values'][$field->id];
                    }
                    $fields_data[$field->field]['options'] = array_values($field_values);
                    $fields_data[$field->field]['choosed'] = [];
                    if(isset($settings[$slug]['fields'][$field->field]->choosed))
                        $fields_data[$field->field]['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
                    if($field->type == 'status') {
                        $simple_options = array();
                        foreach($field_values as $option) {
                            if(isset($settings[$slug]['options'][$field->field])) {
                                $simple_options[$option['value']] = $option['label']['text'];
                                $values[] = array(
                                    'value' => $option['value'],
                                    'label' => $option['label']['text'],
                                    'sort' => $option['label']['sort']
                                );
                            } else {
                                $simple_options[$option->id] = $option->value;
                                $values[] = array(
                                    'value' => $option->id,
                                    'label' => $option->value,
                                    'sort' => $option->sort
                                );
                            }

                        }
                    } else {
                        if($field->type == 'relation') {
                            if($field->is_plural && is_array($fields_data[$field->field]['value'])) {
                                foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                                    if(isset($settings['list_values'][$field->id][$field_val]))
                                        $field_values[$field_val] = $settings['list_values'][$field->id][$field_val]['value'];
                                }
                            } elseif($current->{$field->field} && isset($settings['list_values'][$field->id][$current->{$field->field}])) {
                                $field_values[$current->{$field->field}] = $settings['list_values'][$field->id][$current->{$field->field}];
                            }
                            // Удалённую связанную запись в опции не добавляем
                            // (см. комментарий выше).
                        } else {
                            $field_values = $settings['list_values'][$field->id];
                        }
                        foreach($field_values as $k => $option) {
                            $simple_options[$k] = $option;
                            $values[] = $option;
                        }
                    }
                };
                if($field->type == 'relation' && $t = json_decode($field->details, true)) {
                    if(isset($t['table']))
                        $fields_data[$field->field]['related_table'] = $t['table'];
                }
                if($field->type == 'text_group') {
                    $subfields = \App\Models\Field::getByGroup($field->id);
                    $values = array();
                    $fields_data[$field->field]['options'] = array();
                    foreach($subfields as $subfield) {
                        $values[] = array(
                            'value' => $subfield->id,
                            'label' => $subfield->title,
                            'sort' => $subfield->sort
                        );
                    };
                    $fields_data[$field->field]['options'] = $values;
                    $fields_data[$field->field]['fields'] = array();

                    foreach($subfields as $subfield) {
                        if (isset($fields_data[$subfield->field])) {
                            $subfield_data = $fields_data[$subfield->field];
                            //$subsection_data['fields'][] = $fields_data[$subfield->field];
                        } else {
                            $subfield_data = $settings[$slug]['field_data'][$subfield->field];
                        }

                        $subfield_data['can_read'] = $settings[$slug]['perms'][$subfield->field]['read'] || $isAdmin ? 1 : 0;
                        $subfield_data['can_edit'] = $subfield->only_read || !$settings[$slug]['perms'][$subfield->field]['write'] && !$isAdmin ? 0 : 1;
                        if(!$id && $permissions['create_p'] == 'Y' && $field->field == 'user_id' && !$isAdmin) {
                            $subfield_data['can_edit'] = 0;
                        }
                        if($id && $permissions['update_p'] == 'Y' && \Auth::user() && $current->user_id != \Auth::user()->id && !$isAdmin && $slug != 'users' && \Auth::user() && \Auth::user()->id != $id ||
                            $id && $permissions['update_p'] == 'N' && !$isAdmin) {
                                $subfield_data['can_edit'] = 0;
                        }
                        $val = (string)$current->{$subfield->field};
                        $field_value = ValueHelper::isJson($val) && $subfield->field != 'products' && is_array(json_decode($val, true)) ? json_decode($val, true) : $val;
                        if($subfield->type == 'relation' && $subfield->is_plural && $subfield->relation_table) {
                            $relation_table = $subfield->relation_table;
                            $field_value = $current->{$relation_table}->pluck('id')->toArray();

                        }
                        $subfield_data['value'] = $field_value;
                        // if($subfield->type == 'relation')
                        //     $field_value = $settings['list_values'][$subfield->id][$field_value];
                        if(isset($settings['list_values'][$subfield->id]))
                            $list_values = $settings['list_values'][$subfield->id];
                        if($subfield->type == 'relation' && $subfield->is_plural) {
                            $values = $field_value;
                            $subfield_data['value'] = array(
                                'value' => array(),
                                'localOptions' => array()
                            );
                            if(is_array($values)) {
                                foreach($values as $val) {
                                    if(isset($list_values[$val])) {
                                        $subfield_data['value']['value'][] = $list_values[$val]['value'];
                                        $subfield_data['value']['localOptions'][] = $list_values[$val];
                                    }
                                }
                            }
                        } elseif($subfield->type == 'relation' && isset($list_values[$field_value])) {
                            $subfield_data['value'] = array(
                                'value' => array(),
                                'localOptions' => array()
                            );
                            $subfield_data['value']['localOptions'] = array($list_values[$field_value]);
                            $subfield_data['value']['value'] = array($list_values[$field_value]['value']);

                        } elseif($subfield->type == 'relation') {
                            $subfield_data['value'] = array(
                                'value' => array(),
                                'localOptions' => array()
                            );
                        }

                        $fields_data[$field->field]['fields'][] = $subfield_data;
                    }
                }
                if($request->is_copy && $slug == 'companies' && ($field->field == 'employee_id' || $field->field == 'car_id' || $field->field == 'fine_id')) {
                    $fields_data[$field->field]['value'] = ['value' => [], 'localOptions' => []];
                }
            }
        }

        // Обработка секций и скрытых полей
        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        $sections_1 = \App\Models\FieldSection::get($slug, 1);
        $sections_2 = \App\Models\FieldSection::get($slug, 2);

        // Обработка секций
        foreach ([$sections_1, $sections_2] as $index => $sections) {
            $column_key = 'column_' . ($index + 1);
            foreach ($sections as $section) {
                $section_data = [
                    'id' => $section->id,
                    'name' => $section->name,
                    'is_short' => $section->is_short,
                    'fields' => [],
                    'children' => []
                ];

                // Обработка полей секции
                if ($section->fields && count($section->fields)) {
                    foreach ($section->fields as $field) {
                        if (isset($settings[$slug]['perms'][$field->field]['read']) &&
                            $settings[$slug]['perms'][$field->field]['read'] == 'disabled' &&
                            !$isAdmin) {
                            continue;
                        }
                        if (isset($fields_data[$field->field]) && $field->data_type_id == $entity->id) {
                            $section_data['fields'][$field->id] = $fields_data[$field->field];
                        }
                    }
                }

                // Обработка подсекций
                if ($section->children) {
                    foreach ($section->children as $subsection) {
                        $subsection_data = [
                            'id' => $subsection->id,
                            'name' => $subsection->name,
                            'sort' => $subsection->sort,
                            'fields' => [],
                            'children' => []
                        ];

                        if ($subsection->fields && count($subsection->fields)) {
                            foreach ($subsection->fields as $subfield) {
                                if (isset($settings[$slug]['perms'][$subfield->field]['read']) &&
                                    $settings[$slug]['perms'][$subfield->field]['read'] == 'disabled' &&
                                    !$isAdmin) {
                                    continue;
                                }
                                if (isset($fields_data[$subfield->field])) {
                                    $subsection_data['fields'][] = $fields_data[$subfield->field];
                                }
                            }
                        }

                        $section_data['children'][] = $subsection_data;
                    }
                }
                   $section_data['fields'] = array_values($section_data['fields']);
                $data['columns'][$column_key][] = $section_data;
            }
        }

        // Обработка скрытых полей
        $data['hidden_fields'] = [];
        foreach ($hidden_fields as $field) {
            if (isset($settings[$slug]['perms'][$field->field]['read']) &&
                $settings[$slug]['perms'][$field->field]['read'] == 'disabled' &&
                !$isAdmin) {
                continue;
            }
            if (isset($fields_data[$field->field])) {
                $data['hidden_fields'][] = $fields_data[$field->field];
            }
        }

        return $data;
    }

    public static function detail_module($slug, $id, $module, Request $request)
    {
        $settings = app('settings');//\App\Models\Settings::get();
        $guides = Settings::getGuidesWithCache();
        $show_hints = Settings::hints();

        $data = array(
            'title' => '',
            'deleted_at' => null,
            'columns' => array(
                'column_1' => array(),
                'column_2' => array()
            )
        );
        $perms = array(
            'read' => array(),
            'write' => array(),
        );


        $entity = $settings['models'][$slug];
        if(!$entity || !$entity->enable) {
            return [
                'error' => array(
                    'message' => 'Entity not found',
                    'code' => 404
                )
            ];
        }
        $entity_class = $entity->model_name;
        $permissions = \Auth::user()->role->permissions()->select([
                'read_p',
                'create_p',
                'update_p',
                'delete_p',
                'export_p',
                'import_p'
            ])->where('entity_id', $entity->id)->first()->toArray();
        $entity_fields = $settings[$slug]['fields'];
        $model_fields = $entity_class::getFieldsByModule($module);
        $fields_data = array();
        $current = $entity_class::withTrashed()->where(['id' => $id])->first();
        if($request->user_id && $current->user_id != $request->user_id)
            return [
                'error' => array(
                    'message' => 'Forbidden',
                    'code' => 403
                )
            ];
        if(!$current) {
            return [
                'error' => array(
                    'message' => 'Object not found',
                    'code' => 404
                )
            ];
        }

        $data['title'] = $current->name;
        if(ValueHelper::isJson($data['title'])) {
            $data['title'] = json_decode($data['title'], true);
            if(isset($data['title']['value'])) {
                $data['title'] = array(
                    'name' => $data['title']['value'],
                    'key' => 'name'
                );
            } else {
                $data['title'] = array(
                    'name' => null,
                    'key' => 'name'
                );
            }
        } else {
            $data['title'] = array(
                'name' => $data['title'],
                'key' => 'name'
            );
        }
        if(isset($current->deleted_at) && $current->deleted_at)
            $data['deleted_at'] = $current->deleted_at;
        $permissions_all = \Auth::user()->role->permissions->keyBy('entity_id')->toArray();

        if($slug == 'fines_gibdd') {
            $success_payments = \Modules\Gibdd\Entities\Module::getSuccessPayments();
        }

        foreach($model_fields as $field) {
            if(!isset($settings[$slug]['field_data'][$field->field]))
                continue;
            if(!array_key_exists($field->field, $fields_data) && (!isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'] || \Auth::user()->is_admin)) {
                if($field->type == 'relation' && $field->relation_table && !$settings['models'][$field->relation_table]->enable)
                    continue;
                if($field->type == 'status')
                    $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
                $field_colors[$field->field] = $field->label_color ? $field->label_color : null;

                $val = (string)$current->{$field->field};
                $field_value = ValueHelper::isJson($val) && $field->field != 'products' && is_array(json_decode($val, true)) ? json_decode($val, true) : $val;
                if($field->type == 'relation' && $field->is_plural && $field->relation_table) {
                    $relation_table = $field->relation_table;
                    $field_value = $current->{$relation_table}->pluck('id')->toArray();
                }

                $fields_data[$field->field] = $settings[$slug]['field_data'][$field->field];//Field::getData($field);
                $fields_data[$field->field]['can_read'] = $settings[$slug]['perms'][$field->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
                $fields_data[$field->field]['can_edit'] = isset($data['deleted_at']) || $field->only_read || !$settings[$slug]['perms'][$field->field]['write'] && !\Auth::user()->is_admin ? 0 : 1;
                if(!$id && $permissions['create_p'] == 'Y' && $field->field == 'user_id' && !\Auth::user()->is_admin) {
                    $fields_data[$field->field]['can_edit'] = 0;
                }
                if ($slug == 'logistic_tasks' && $field->field == 'delivery_date' && $current->route_id && !$request->is_copy) {
                    $fields_data[$field->field]['can_edit'] = 0;
                }
                if($id && $permissions['update_p'] == 'Y' && $current->user_id != \Auth::user()->id && !\Auth::user()->is_admin && $slug != 'users' && \Auth::user()->id != $id ||
                    $id && $permissions['update_p'] == 'N' && !\Auth::user()->is_admin/* || $field->field == 'payment'*/) {
                        $fields_data[$field->field]['can_edit'] = 0;
                }

                //$fields_data[$field->field]['can_edit'] = $field->only_read ? 0 : 1;//!$settings[$slug]['perms'][$field->field]['write'] ? 1 : 0;
                if($request->is_copy && $field->field == 'created_at') {
                    $field_value = \Carbon\Carbon::now();
                } elseif($request->is_copy && ($field->field == 'id' || $field->field == 'updated_at')) {
                    $field_value = null;
                } elseif($request->is_copy && $field->field == 'user_id') {
                    $field_value = \Auth::user()->id;
                }
                $fields_data[$field->field]['value'] = $field->field == 'password' ? '' : $field_value;
                $fields_data[$field->field]['guide'] = null;
                if(is_array($guides) && isset($guides['fields'][$slug][$field->field]) && $show_hints)
                    $fields_data[$field->field]['guide'] = $guides['fields'][$slug][$field->field];
                $fields_data[$field->field]['used_in_modules'] = $settings[$slug]['used_in_modules'][$field->field];
                //$fields_data[$field->field]['comparison'] = $comparisons[$field->id] ?? null;
                //$fields_data[$field->field]['editableFields']['model'] = $slug;

                if($field->type == 'file' && !isset($field_value[0]) && $field_value) {
                    $fields_data[$field->field]['value'] = array($field_value);
                } elseif($field->type == 'file' && !isset($field_value[0]) && !$field_value) {
                    $fields_data[$field->field]['value'] = array();
                } elseif($field->type == 'file' && is_array($field_value)) {
                    $fields_data[$field->field]['value'] = array();
                    foreach($field_value as $fval) {
                        if($fval)
                            $fields_data[$field->field]['value'][] = $fval;
                    }
                }
                $list_values = array();
                if(isset($settings['list_values'][$field->id]))
                    $list_values = $settings['list_values'][$field->id];
                if($field->type == 'relation' && $field->is_plural) {
                    $values = $field_value;
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                    if(is_array($values)) {
                        foreach($values as $val) {
                            if(isset($list_values[$val])) {
                                $fields_data[$field->field]['value']['value'][] = $list_values[$val]['value'];
                                $fields_data[$field->field]['value']['localOptions'][] = $list_values[$val];
                            }
                        }
                    }
                } elseif($field->type == 'relation' && isset($list_values[$field_value])) {
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                    $fields_data[$field->field]['value']['localOptions'] = array($list_values[$field_value]);
                    $fields_data[$field->field]['value']['value'] = array($list_values[$field_value]['value']);

                } elseif($field->type == 'relation') {
                    $fields_data[$field->field]['value'] = array(
                        'value' => array(),
                        'localOptions' => array()
                    );
                }

                // if($field->type == 'relation') {
                //     if($field->field == 'category_id' || $field->field == 'role_id')
                //         $fields_data[$field->field]['can_create'] = 0;
                //     else
                //         $fields_data[$field->field]['can_create'] = 1;
                // }


                if($field->type == 'relation' && $field->field != 'role_id') {

                    $fields_data[$field->field]['can_create'] = 1;

                    if(isset($permissions_all[$settings['models'][$field->relation_table]->id]['create_p']) && !\Auth::user()->is_admin)

                        $fields_data[$field->field]['can_create'] = $permissions_all[$settings['models'][$field->relation_table]->id]['create_p'] == 'N' ? 0 : 1;
                    // if(isset($permissions_all[$settings['models'][$field->relation_table]->id]['update_p']) && !\Auth::user()->is_admin)
                    //     $fields_data[$field->field]['can_edit'] = $permissions_all[$settings['models'][$field->relation_table]->id]['update_p'] == 'N' ? 0 : 1;

                }

                if($field->type == 'relation' && $field->relation_table && isset($restrictions_tariff['objects'][$field->relation_table]['count'])) {
                    if($restrictions_tariff['objects'][$field->relation_table]['count'] <= \DB::table($field->relation_table)->whereNull('deleted_at')->count()) {
                        $fields_data[$field->field]['can_create'] = 0;
                    };
                }
                if(isset($settings['list_values'][$field->id])) {
                    $values = array();
                    if($field->type == 'relation') {
                        $field_values = array_slice($settings['list_values'][$field->id], 0, 19, true);
                        if($field->is_plural && isset($fields_data[$field->field]['value']['value'])) {
                            foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                                $field_values[$field_val] = $settings['list_values'][$field->id][$field_val];
                            }
                        } elseif($current->{$field->field} && isset($settings['list_values'][$field->id][$current->{$field->field}])) {
                            $field_values[$current->{$field->field}] = $settings['list_values'][$field->id][$current->{$field->field}];
                        }
                        // Значение есть, но его нет в list_values — связанная
                        // запись удалена. Удалённые в опциях не показываем.
                    } else {
                        if(isset($settings[$slug]['options'][$field->field]))
                            $field_values = $settings[$slug]['options'][$field->field];
                        else
                            $field_values = $settings['list_values'][$field->id];
                    }
                    $fields_data[$field->field]['options'] = array_values($field_values);
                    $fields_data[$field->field]['choosed'] = [];
                    if(isset($settings[$slug]['fields'][$field->field]->choosed))
                        $fields_data[$field->field]['choosed'] = $settings[$slug]['fields'][$field->field]->choosed;
                    if($field->type == 'status') {
                        $simple_options = array();
                        foreach($field_values as $option) {
                            if(isset($settings[$slug]['options'][$field->field])) {
                                $simple_options[$option['value']] = $option['label']['text'];
                                $values[] = array(
                                    'value' => $option['value'],
                                    'label' => $option['label']['text'],
                                    'sort' => $option['label']['sort']
                                );
                            } else {
                                $simple_options[$option->id] = $option->value;
                                $values[] = array(
                                    'value' => $option->id,
                                    'label' => $option->value,
                                    'sort' => $option->sort
                                );
                            }

                        }
                    } else {
                        if($field->type == 'relation') {
                            if($field->is_plural && is_array($fields_data[$field->field]['value'])) {
                                foreach($fields_data[$field->field]['value']['value'] as $field_val) {
                                    if(isset($settings['list_values'][$field->id][$field_val]))
                                        $field_values[$field_val] = $settings['list_values'][$field->id][$field_val]['value'];
                                }
                            } elseif($current->{$field->field} && isset($settings['list_values'][$field->id][$current->{$field->field}])) {
                                $field_values[$current->{$field->field}] = $settings['list_values'][$field->id][$current->{$field->field}];
                            }
                            // Удалённую связанную запись в опции не добавляем
                            // (см. комментарий выше).
                        } else {
                            $field_values = $settings['list_values'][$field->id];
                        }
                        foreach($field_values as $k => $option) {
                            $simple_options[$k] = $option;
                            $values[] = $option;
                            // $values[] = array(
                            //     'label' => [
                            //         'id' => $k,
                            //         'sort' => is_array($option) && isset($option['sort']) ? $option['sort'] : $k,
                            //         'file' => $avatar,
                            //         'is_hidden' => 0,
                            //         'field_id' => $field->id,
                            //         'color' => isset($current->color) && !$current->color ? $current->getColor() : ($current->color ?? ''),
                            //         'text' => is_array($option) ? $option['label'] : $option
                            //     ],
                            //     'value' => $k
                            // );
                            // $values[] = array(
                            //     'value' => $k,
                            //     'label' => is_array($option) ? $option['label'] : $option,
                            //     'sort' => is_array($option) && isset($option['sort']) ? $option['sort'] : $k
                            // );
                        }
                    }
                    // $fields_data[$field->field]['editableFields']['values'] = $values;
                    // if($field->type == 'status')
                    //     $fields_data[$field->field]['editableFields']['statuses'] = $settings['list_values'][$field->id];
                };
                if($field->type == 'relation' && $t = json_decode($field->details, true)) {
                    if(isset($t['table']))
                        $fields_data[$field->field]['related_table'] = $t['table'];
                }
                if($field->type == 'text_group') {
                    $subfields = \App\Models\Field::getByGroup($field->id);
                    $values = array();
                    $fields_data[$field->field]['options'] = array();
                    foreach($subfields as $subfield) {
                        //$fields_data[$field->field]['options'][$subfield->id] = $subfield->title;
                        $values[] = array(
                            'value' => $subfield->id,
                            'label' => $subfield->title,
                            'sort' => $subfield->sort
                        );
                    };
                    $fields_data[$field->field]['options'] = $values;
                    foreach($subfields as $subfield) {
                        $subfield_data = $settings[$slug]['field_data'][$subfield->field];
                        $subfield_data['can_read'] = $settings[$slug]['perms'][$subfield->field]['read'] || \Auth::user()->is_admin ? 1 : 0;
                        $subfield_data['can_edit'] = $subfield->only_read || !$settings[$slug]['perms'][$subfield->field]['write'] && !\Auth::user()->is_admin ? 0 : 1;
                        if(!$id && $permissions['create_p'] == 'Y' && $field->field == 'user_id' && !\Auth::user()->is_admin) {
                            $subfield_data['can_edit'] = 0;
                        }
                        if($id && $permissions['update_p'] == 'Y' && $current->user_id != \Auth::user()->id && !\Auth::user()->is_admin && $slug != 'users' && \Auth::user()->id != $id ||
                            $id && $permissions['update_p'] == 'N' && !\Auth::user()->is_admin) {
                                $subfield_data['can_edit'] = 0;
                        }
                        $val = (string)$current->{$subfield->field};
                        $field_value = ValueHelper::isJson($val) && $subfield->field != 'products' && is_array(json_decode($val, true)) ? json_decode($val, true) : $val;
                        if($subfield->type == 'relation' && $subfield->is_plural && $subfield->relation_table) {
                            $relation_table = $subfield->relation_table;
                            $field_value = $current->{$relation_table}->pluck('id')->toArray();
                        }
                        $subfield_data['value'] = $field_value;
                        //$subfield_data['value'] = $current->{$subfield->field};
                        $fields_data[$field->field]['fields'][] = $subfield_data;
                    }
                }
                // $fields_data[$field->field] = Field::getDataByObject($field, $slug, $current);
                if($request->is_copy && $slug == 'companies' && ($field->field == 'employee_id' || $field->field == 'car_id' || $field->field == 'fine_id')) {
                    $fields_data[$field->field]['value'] = ['value' => [], 'localOptions' => []];
                }
            }
        }
        if($slug == 'fines_gibdd' && isset($fields_data['payment']['value']['value'])) {
            if($fields_data['sale_finish']['value'] >= date('Y-m-d'))
                $fields_data['payment']['value']['value'] = ceil($fields_data['discount_sum']['value'] / (100 - \Modules\Gibdd\Entities\Module::getPriceKoef()) * 100);
            else
                $fields_data['payment']['value']['value'] = ceil($fields_data['sum']['value'] / (100 - \Modules\Gibdd\Entities\Module::getPriceKoef()) * 100);
            $fields_data['payment']['value']['number_doc'] = $fields_data['number_doc']['value'];
            $fields_data['payment']['value']['id'] = $id;
            if(in_array($fields_data['number_doc']['value'], $success_payments))
                $fields_data['payment']['value']['state'] = 1;
            // $fields_data['payment']['value']['value'] = round($fields_data['payment']['value']['value']*\Modules\Gibdd\Entities\Module::getPriceKoef());
        }

        $fields_data_entity = array();
        foreach($entity_fields as $field) {
            if($field->module)
                continue;
            // if($field->type == 'status')
            //     $fields_values[$field->field] = \App\Models\Field::getStatusesVisible($field->id);
            // $field_colors[$field->field] = $field->label_color ? $field->label_color : null;
            if(!array_key_exists($field->field, $fields_data_entity)) {
                $fields_data_entity[$field->field] = Field::getDataByObject($field, $slug, $current);

            }
        }


        $hidden_fields = \App\Models\Field::getHiddenFields($slug);
        $sections_1 = \App\Models\FieldSection::get($slug, 1, $module);
        $sections_2 = \App\Models\FieldSection::get($slug, 2, $module);

        $products = array();
        if($slug == 'logistic_tasks' && $current->products)
            $products = json_decode($current->products, true);

        $sum = $count = $weight = 0;
        foreach($products as $product) {
            $sum+=(int)$product['price']*(int)$product['count'];
            $count+=(int)$product['count'];
            $weight+=(int)$product['weight']*(int)$product['count'];
        }

        // $history_days = \App\Models\History::where(['entity' => $slug, 'entity_id' => $current->id])->orderBy('created_at', 'DESC')->get()->groupBy(function ($val) {
        //         return \Carbon\Carbon::parse($val->created_at)->format('d.m.Y');
        //     });
        $users = \App\Models\User::get();
        foreach($users as $user) {
            $users_arr[$user->id] = $user;
        }
        $users = $users_arr;

        foreach($sections_1 as $section) {
            $fields = array();
            $module_fields = $section->module_fields();
            if($module_fields && count($module_fields)) {
                foreach($module_fields as $k => $field) {
                    if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin() && isset($fields_data[$field->field]) || !isset($fields_data[$field->field]))
                        continue;


                    $fields[$field->id] = $fields_data[$field->field];
                }
            }
            $data['columns']['column_1'][] = array(
                'id' => $section->id,
                'name' => $section->name,
                'is_short' => $section->is_short,
                //'sort' => $section->sort,
                'fields' => array_values($fields)
            );
        }

        foreach($sections_2 as $section) {
            $fields = array();
            $module_fields = $section->module_fields();
            if($module_fields && count($module_fields)) {
                foreach($module_fields as $k => $field) {
                    if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin() && isset($fields_data[$field->field]))
                        continue;
                    $fields[$field->id] = $fields_data[$field->field];
                }
            }
            $data['columns']['column_2'][] = array(
                'id' => $section->id,
                'name' => $section->name,
                'is_short' => $section->is_short,
                //'sort' => $section->sort,
                'fields' => array_values($fields)
            );
        }
        $data['hidden_fields'] = array();
        $data['entity_fields'] = $fields_data_entity;
        foreach($hidden_fields as $field) {
            if(isset($settings[$slug]['perms'][$field->field]) && $settings[$slug]['perms'][$field->field]['read'] == 'disabled' && !\Auth::user()->isAdmin() || !isset($fields_data[$field->field]))
                        continue;
                $data['hidden_fields'][] = $fields_data[$field->field];

        }

        return $data;

    }

    private static function requirementValuesNotIn($settings, $model_fields, $fieldKey, array $reqs)
    {
        $field = $model_fields->firstWhere('field', $fieldKey);
        if (!$field || !isset($settings['list_values'][$field->id]) || !is_array($settings['list_values'][$field->id])) {
            return [];
        }

        $all = [];
        foreach ($settings['list_values'][$field->id] as $value => $option) {
            $optValue = (is_array($option) && isset($option['value'])) ? $option['value'] : $value;
            $all[] = (int) $optValue;
        }

        return array_values(array_diff(array_unique($all), $reqs));
    }

    public static function list($slug, Request $request)
    {
        $settings = app('settings');//\App\Models\Settings::get();
        $guides = Settings::getGuidesWithCache();
        $show_hints = Settings::hints();

        $per_page_settings = \DB::table('settings')->where([
            'key' => 'per_page',
            'entity' => $slug,
            'user_id' => \Auth::user() ? \Auth::user()->id : 1,
        ])->first();

        $tariff = Tariff::current();
        $restrictions = $blocked_pages = [];
        if($tariff->restrictions) {
            $restrictions_tariff = json_decode($tariff->restrictions,true);

            if(isset($restrictions_tariff['objects'][$slug])) {
                $restrictions = $restrictions_tariff['objects'][$slug];
            }
            if(isset($restrictions_tariff['blocked_pages'])) {
                $blocked_pages = $restrictions_tariff['blocked_pages'];
            }
        }
        if($request->trashed && in_array('trash', $blocked_pages)) {
            return [
                'error' => array(
                    'message' => 'Недоступно в бесплатном тарифе',
                    'code' => 403
                )
            ];
        }

        if(!$per_page_settings) {
            $per_page = $request->per_page ? $request->per_page : 25;
            \DB::table('settings')->insert([
                'key' => 'per_page',
                'entity' => $slug,
                'user_id' => \Auth::user() ? \Auth::user()->id : 1,
                'value' => $per_page
            ]);
        } else {
            $per_page = $per_page_settings->value;
        }

        $tables = \Auth::user() ? \Auth::user()->tables : null;
        if($tables)
            $tables = json_decode($tables, true);

        $limit = $request->has('per_page') ? $request->per_page : $per_page;
        $page = $request->page ? $request->page : 1;
        // Строку "null" (а равно пустую строку) трактуем как «сортировка не задана»
        // и откатываемся к сохранённым настройкам таблицы. Без этого фронт мог
        // прислать sort_field=null, и orderBy получал несуществующую колонку "null".
        $req_sort_field = $request->sort_field;
        if ($req_sort_field === 'null' || $req_sort_field === '' || $req_sort_field === null) {
            $req_sort_field = null;
        }
        $sort_field = $req_sort_field ?: (isset($tables[$slug]['sort_field']) ? $tables[$slug]['sort_field'] : 'id');
        if ($sort_field === 'null' || $sort_field === '' || $sort_field === null) {
            $sort_field = 'id';
        }
        $sort_order = $request->sort_order ?? ($tables[$slug]['sort_order'] ?? 'desc');
        $sort_order = strtolower((string) $sort_order);
        if ($sort_order === 'null' || !in_array($sort_order, ['asc','desc'], true)) {
            $sort_order = 'desc';
        }


        if(!$request->filter && $request->is_slug) {
            return [
                'count' => 0,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 25,
                'total' => 0,
                'from' => 0,
                'to' => 0,
                'data' => [],
                'buttons' => $settings[$slug]['buttons'],
                'sort_field' => $sort_field,
                'sort_order' => $sort_order
            ];
        }

        $entity = $settings['models'][$slug];
        if(!$entity || !$entity->enable) {
            return [
                'error' => array(
                    'message' => 'Сущность не найдена',
                    'code' => 404,
                )
            ];
        }
        $entity_class = $entity->model_name;
        $model_fields = collect($settings[$slug]['fields']);

        $field_colors = array();
        $perms = array(
            'read' => array(),
            'write' => array(),
        );
        $sorted_values = array();
        $category_slug = null;
        foreach($model_fields as $field) {
            if($field->field == $sort_field && $field->type == 'relation' && $field->is_plural && $field->relation_table) {
                $relation = $field->relation_table;
                $sorted_ids = $entity_class::with($relation)->get()->sortBy(function($item) use ($relation) {
                    return $item->{$relation}->count() ? implode(',', $item->{$relation}->pluck('name')->toArray()) : '';
                }, SORT_NATURAL, ($sort_order == 'asc' ? false : true))->pluck('id')->toArray();

                $sorted_values = implode(',', $sorted_ids);

                $paginator = $entity_class::orderByRaw("FIELD(id, $sorted_values)");

                break;
            } elseif($field->field == $sort_field && $field->type == 'relation') {

                $sorted_values = collect($settings['list_values'][$field->id])->sortBy(function ($item) {
                    return $item['label']['text'];
                });
                $sorted_values = $sorted_values->pluck('value')->toArray();

                $sorted_values = implode(',', $sorted_values);

                $paginator = $entity_class::orderByRaw("FIELD($sort_field, $sorted_values) $sort_order");
                break;
            } elseif($field->field == $sort_field && $field->type == 'file') {

                //if($sort_order == 'asc') {
                    $objects = $entity_class::select('id', $sort_field);
                    if($request->trashed)
                        $objects = $objects->onlyTrashed();
                    $sorted_ids = $objects->get()->sortBy(function($item) use($sort_field) {



                        $value = $item->{$sort_field};



                        if(ValueHelper::isJson($item->{$sort_field})) {



                            $value = is_array($item->{$sort_field}) ? $item->{$sort_field} : json_decode($item->{$sort_field}, true);
                            if($count = count($value)) {


                                $value = $count.''.json_encode($value);
                            } else {
                                $value = '';
                            }
                        }

                        return $value;
                    }, SORT_NATURAL, ($sort_order == 'asc' ? false : true))->pluck('id')->toArray();
                    //$sorted_ids = $entity_class::select('id', $sort_field)->get()->sortBy($sort_field, SORT_NATURAL)->pluck('id')->toArray();
                    $sorted_values = implode(',', $sorted_ids);
                    $paginator = $entity_class::orderByRaw("FIELD(id, $sorted_values)");
                // } else {
                //      $sorted_ids = $entity_class::select('id', $sort_field)->get()->sortBy($sort_field, SORT_NATURAL, true)->pluck('id')->toArray();
                //      $sorted_values = implode(',', $sorted_ids);
                //      $paginator = $entity_class::orderByRaw("FIELD(id, $sorted_values)");
                // }
                break;
            } elseif($field->field == $sort_field) {
                // if($sort_order == 'asc') {
                //      $sorted_ids = $entity_class::select('id', $sort_field)->get()->sortBy($sort_field, SORT_NATURAL)->pluck('id')->toArray();
                //      $sorted_values = implode(',', $sorted_ids);
                //      $paginator = $entity_class::orderByRaw("FIELD(id, $sorted_values) ASC");
                //      //$paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field");
                // }
                // else {
                $arr = ['id', $sort_field];
                if($sort_field == 'payment') {
                    $arr = array_merge($arr, ['sale_finish', 'discount_sum', 'sum']);
                }
                $objects = $entity_class::select($arr);
                if($request->trashed)
                    $objects = $objects->onlyTrashed();
                $sorted_ids = $objects->get()->sortBy(function($item) use($sort_field) {
                    $value = $item->{$sort_field};
                    if($sort_field == 'payment') {
                        $value = 1;
                    }
                    if(ValueHelper::isJson($item->{$sort_field})) {
                        $value = json_decode($item->{$sort_field}, true);
                        if(isset($value['state']))  {
                            if($item->sale_finish >= date('Y-m-d'))
                                $value = $value['state'].' '.ceil($item->discount_sum / (100 - \Modules\Gibdd\Entities\Module::getPriceKoef()) * 100);
                            else
                                $value = $value['state'].' '.ceil($item->sum / (100 - \Modules\Gibdd\Entities\Module::getPriceKoef()) * 100);
                           // $value = $value['state'].' '.$value['value'];
                        } else {
                            $value = isset($value['value']) ? $value['value'] : (isset($value['text']) ? $value['text'] : '');
                        }
                    }

                    return $value;
                }, SORT_NATURAL, ($sort_order == 'asc' ? false : true))->pluck('id')->toArray();
                // $sorted_ids = $entity_class::select('id', $sort_field)->get()->sortBy($sort_field, SORT_NATURAL, true)->pluck('id')->toArray();
                $sorted_values = implode(',', $sorted_ids);
                $paginator = $entity_class::orderByRaw("FIELD(id, $sorted_values)");

                    //$paginator = $entity_class::orderByRaw("$sort_field REGEXP '^-?[0-9\.]+$' AND LENGTH($sort_field) - LENGTH(REPLACE($sort_field, '.', '')) < 2 DESC, CAST($sort_field AS UNSIGNED), $sort_field desc");
                //}
                break;
            }
        };
        if($sort_field == 'id')
            $paginator = $entity_class::orderByRaw("CAST($sort_field AS DECIMAL) $sort_order");
        if(!isset($paginator)) {
            $paginator = $entity_class::orderBy('id', 'desc');
            $sort_field = 'id';
        }


        $tabs = array();
        if($request->trashed) {
            $paginator = $paginator->onlyTrashed();
        } elseif($request->with_trashed && in_array(SoftDeletes::class, class_uses_recursive($entity_class))) {
            // Вкладки связанных сущностей на деталке удалённого объекта:
            // показываем и удалённые связанные записи.
            $paginator = $paginator->withTrashed();
        }
        if($request->ids) {
            $paginator = $paginator->whereIntegerInRaw('id', $request->ids);
        }
        if($request->filter && is_array($request->filter)){

            if ($slug == 'logistic_tasks' && isset($request->filter['route_id'])) {
                $routeId = $request->filter['route_id'];
                if ($routeId == 'null' || $routeId === null) {
                    // «Задачи без актуального маршрута»: route_id пуст ЛИБО
                    // маршрут удалён (в корзине). При удалении маршрута задачи
                    // не отвязываются (restore должен вернуть состав), но в
                    // таблице «Задачи логистики» они должны появиться снова.
                    $paginator = $paginator->where(function ($q) {
                        $q->whereNull('route_id')
                          ->orWhereNotExists(function ($sub) {
                              $sub->select(\DB::raw(1))
                                  ->from('routes')
                                  ->whereColumn('routes.id', 'logistic_tasks.route_id')
                                  ->whereNull('routes.deleted_at');
                          });
                    });
                } else {
                    $paginator = $paginator->where('route_id', $routeId);
                }
            }
            foreach($request->filter as $field => $val) {
                if($val == 'null')
                    $val = null;
                // route_id у logistic_tasks уже обработан спец-блоком выше
                // (учитывает удалённые маршруты через orWhereNotExists).
                // Повторная обработка в общем цикле навешивала бы AND
                // route_id IS NULL и отменяла учёт удалённых маршрутов —
                // задачи удалённого маршрута не попадали в «Задачи логистики».
                if($slug == 'logistic_tasks' && $field == 'route_id')
                    continue;
                if($slug == 'logistic_tasks' && in_array($field, ['car_requirements', 'employee_requirements'], true))
                    continue;
                if(!isset($settings[$slug]['fields'][$field]))
                    continue;
                /*if($field == 'created_at' || $field == 'updated_at') {
                    if(is_array($val)) {}
                    $paginator = $paginator->whereDate($field, $val);
                } else*/
                if($settings[$slug]['fields'][$field]->type == 'date') {
                    if(!is_array($val) && strstr($val, ','))
                        $val = explode(',', $val);
                    if(is_array($val) && $val[0] && $val[1]) {
                        if($val[0] != $val[1])
                            $paginator = $paginator->whereBetween($field, $val);
                        else
                            $paginator = $paginator->whereDate($field, date('Y-m-d', strtotime($val[0])));
                    } elseif(is_array($val) && $val[0] && !$val[1]) {
                        $paginator = $paginator->whereDate($field, '>=', $val[0]);
                    } elseif(is_array($val) && !$val[0] && $val[1]) {
                        $paginator = $paginator->whereDate($field, '<=', $val[1]);
                    } else {
                        $paginator = $paginator->whereDate($field, date('Y-m-d', strtotime($val)));
                        //$paginator = $paginator->whereDate($field, date('Y-m-d', strtotime($val)));
                    }
                } elseif($settings[$slug]['fields'][$field]->type == 'number' && $settings[$slug]['fields'][$field]->field != 'id') {
                    if(!is_array($val) && strstr($val, ','))
                        $val = explode(',', $val);

                    if(is_array($val)) {
                        $min = isset($val[0]) && $val[0] !== '' && $val[0] !== 'null' ? $val[0] : null;
                        $max = isset($val[1]) && $val[1] !== '' && $val[1] !== 'null' ? $val[1] : null;

                        if($min !== null && $max !== null) {
                            $paginator = $paginator->where(function($q) use ($field, $min, $max) {
                                $q->whereBetween(\DB::raw("CAST($field AS DECIMAL(10,2))"), [$min, $max])
                                  ->orWhereNull($field)
                                  ->orWhere($field, '');
                            });
                        } elseif($min !== null) {
                            $paginator = $paginator->where(function($q) use ($field, $min) {
                                $q->where(\DB::raw("CAST($field AS DECIMAL(10,2))"), '>=', $min)
                                  ->orWhereNull($field)
                                  ->orWhere($field, '');
                            });
                        } elseif($max !== null) {
                            $paginator = $paginator->where(function($q) use ($field, $max) {
                                $q->where(\DB::raw("CAST($field AS DECIMAL(10,2))"), '<=', $max)
                                  ->orWhereNull($field)
                                  ->orWhere($field, '');
                            });
                        }
                    } else {
                        $paginator = $paginator->where($field, $val);
                    }
                } else {
                    if(isset($settings[$slug]['fields'][$field]) && $settings[$slug]['fields'][$field]->is_plural) {
                        if($settings[$slug]['fields'][$field]->type == 'relation' && $settings[$slug]['fields'][$field]->relation_table) {
                            $paginator = $paginator->whereHas($settings[$slug]['fields'][$field]->relation_table, function($q) use($val) {
                                $q->where('id', '=', (int)$val);
                            });

                            //$paginator = $paginator->whereJsonContains($field, (int)$val);
                        } elseif($settings[$slug]['fields'][$field]->type == 'relation') {
                            $paginator = $paginator->whereJsonContains($field, (int)$val);
                        } else {
                            //->whereRaw("json_contains(`client_id`, ?)", [15])->whereRaw('json_contains(`tip_tk`, \'"'.$str.'"\')')

                            // Изменено на логику AND: запись должна содержать ВСЕ значения из переданного массива
                            if(is_array($val)) {
                                foreach($val as $v) {
                                    if($v !== null && $v !== 'null') {
                                        // Применяем whereRaw последовательно, что создает условия AND
                                        $paginator = $paginator->whereRaw('json_contains('.$field.', \''.$v.'\')');
                                    }
                                }
                            } else {
                                $paginator = $paginator->whereRaw('json_contains('.$field.', \''.$val.'\')');
                            }
                            //$paginator = $paginator->whereRaw('json_contains('.$field.', \'"'.$val.'"\')');
                        }
                    } else {
                        if($field == 'category_id' && !$request->exclude_childs) {
                            foreach($model_fields as $f) {
                                if($f->field == $field) {
                                    $related_table = $field->relation_table;//json_decode($f->details, true)['table'];
                                    $dt = \DB::table('data_types')->where('name', $related_table)->first();
                                    $descendants = $dt->model_name::descendantsAndSelf($val)->pluck('id')->toArray();
                                }
                            }
                            if(isset($descendants) && is_array($descendants)) {
                                $paginator = $paginator->whereIntegerInRaw($field, $descendants);
                            }

                        } else {
                            if(is_array($val))
                                $paginator = $paginator->whereIntegerInRaw($field, $val);
                            else {
                                // $paginator = $paginator->where(function ($query) use ($search_columns, $q) {
                                //      foreach ($search_columns as $column) {
                                //          $query->orWhere($column, 'like', "%{$q}%");
                                //      }
                                // });

                                if($settings[$slug]['fields'][$field]->type == 'address')
                                    //$paginator = $paginator->whereJsonContains('address', ['text' => $val]);
                                    $paginator = $paginator->where("{$field}->text",'like', "%{$val}%");
                                    //$paginator = $paginator->whereJsonContains("address->text", $val);
                                    //$paginator = $paginator->where($field, '%"text": "'.$val.'"%');
                                    //$paginator = $paginator->whereRaw('json_contains('.$field.', \'"'.$val.'"\')');
                                    //$paginator = $paginator->whereJsonContains($field, $val);
                                elseif($settings[$slug]['fields'][$field]->type == 'text')
                                    $paginator->where(function ($query) use ($val, $field) {
                                        $query->where($field, 'like', "%{$val}%")
                                          ->orWhere("{$field}->value", 'like', "%{$val}%");
                                    });
                                else
                                    $paginator = $paginator->where($field, $val);

                            }
                        }

                    }

                }
            }
        }

        // Filter logistic_tasks by route requirements
        if ($slug == 'logistic_tasks' && $request->filter) {
            $filter = $request->filter;

            if (isset($filter['car_requirements']) && is_array($filter['car_requirements'])) {
                $reqs = array_map('intval', array_filter($filter['car_requirements'], function ($v) {
                    return $v !== null && $v !== '';
                }));
                $disallowed = self::requirementValuesNotIn($settings, $model_fields, 'car_requirements', $reqs);
                $paginator = $paginator->where(function ($q) use ($disallowed) {
                    $q->where(function ($q2) {
                        $q2->whereNull('car_requirements')
                           ->orWhere('car_requirements', '[]')
                           ->orWhere('car_requirements', '');
                    });
                    if (!empty($disallowed)) {
                        $q->orWhere(function ($q2) use ($disallowed) {
                            foreach ($disallowed as $v) {
                                $q2->whereJsonDoesntContain('car_requirements', $v);
                            }
                        });
                    } else {
                        $q->orWhereRaw('1 = 1');
                    }
                });
            }

            if (isset($filter['employee_requirements']) && is_array($filter['employee_requirements'])) {
                $reqs = array_map('intval', array_filter($filter['employee_requirements'], function ($v) {
                    return $v !== null && $v !== '';
                }));
                $disallowed = self::requirementValuesNotIn($settings, $model_fields, 'employee_requirements', $reqs);
                $paginator = $paginator->where(function ($q) use ($disallowed) {
                    $q->where(function ($q2) {
                        $q2->whereNull('employee_requirements')
                           ->orWhere('employee_requirements', '[]')
                           ->orWhere('employee_requirements', '');
                    });
                    if (!empty($disallowed)) {
                        $q->orWhere(function ($q2) use ($disallowed) {
                            foreach ($disallowed as $v) {
                                $q2->whereJsonDoesntContain('employee_requirements', $v);
                            }
                        });
                    } else {
                        $q->orWhereRaw('1 = 1');
                    }
                });
            }


            // // Weight filter
            // if (isset($filter['weight']) && is_array($filter['weight'])) {
            //     $wMin = $filter['weight'][0] ?? null;
            //     $wMax = $filter['weight'][1] ?? null;
            //     if ($wMin !== null || $wMax !== null) {
            //         $paginator = $paginator->get()->filter(function($task) use ($wMin, $wMax) {
            //             $products = json_decode($task->products, true);
            //             $totalWeight = 0;
            //             if (is_array($products)) {
            //                 foreach ($products as $p) {
            //                     $totalWeight += ($p['weight'] ?? 0) * ($p['count'] ?? 1);
            //                 }
            //             }
            //             if ($wMin !== null && $totalWeight < (float)$wMin) return false;
            //             if ($wMax !== null && $totalWeight > (float)$wMax) return false;
            //             return true;
            //         });
            //         // Re-paginate after filtering
            //         $total = $paginator->count();
            //         $paginator = $paginator->forPage($request->page ?? 1, $request->per_page ?? 25);
            //     }
            // }

            // // Volume filter (same logic if tasks have volume in products)
            // if (isset($filter['volume']) && is_array($filter['volume'])) {
            //     $vMin = $filter['volume'][0] ?? null;
            //     $vMax = $filter['volume'][1] ?? null;
            //     if ($vMin !== null || $vMax !== null) {
            //         $paginator = $paginator->get()->filter(function($task) use ($vMin, $vMax) {
            //             $products = json_decode($task->products, true);
            //             $totalVolume = 0;
            //             if (is_array($products)) {
            //                 foreach ($products as $p) {
            //                     $totalVolume += ($p['volume'] ?? 0) * ($p['count'] ?? 1);
            //                 }
            //             }
            //             if ($vMin !== null && $totalVolume < (float)$vMin) return false;
            //             if ($vMax !== null && $totalVolume > (float)$vMax) return false;
            //             return true;
            //         });
            //         $total = $paginator->count();
            //         $paginator = $paginator->forPage($request->page ?? 1, $request->per_page ?? 25);
            //     }
            // }
        }

        if($request->order_id && $slug == 'products') {

            $order = \App\Models\Task::withTrashed()->where(['id' => $request->order_id])->first();

            if($order) {
                $products = json_decode($order->products, true);

                $product_ids = array();
                $fix_order = false;
                if(is_array($products)) {
                    foreach($products as $product_k => $product) {
                        if(!isset($product['id'])) {
                            $prod = \Modules\Products\Entities\Product::where('name', $product['name'])->first();
                            if($prod) {
                                $fix_order = true;
                                $product_ids[] = $prod->id;
                                $products[$product_k]['id'] = $prod->id;
                            }

                        } else {
                            $product_ids[] = $product['id'];
                        }
                    }
                    if($fix_order) {
                        $order->products = json_encode($products, JSON_UNESCAPED_UNICODE);
                        $order->saveQuietly();
                    }
                    $paginator = $paginator->whereIntegerInRaw('id', $product_ids);
                } else {
                    return [
                        'count' => 0,
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 25,
                        'total' => 0,
                        'from' => 0,
                        'to' => 0,
                        'data' => [],
                        'buttons' => $settings[$slug]['buttons'],
                        'sort_field' => $sort_field,
                        'sort_order' => $sort_order
                    ];
                    //return [];
                }
            }
        };

        if($request->q) {

            $q = $request->q;
            $search_columns = $model_fields->filter(function ($field) {
                                return ($field->type != 'relation' && $field->type != 'status' && $field->type != 'text_group');
                            })->pluck('field')->toArray();


            $paginator = $paginator->where(function ($query) use ($slug, $settings, $model_fields, $search_columns, $q) {
                foreach ($search_columns as $column) {
                    $query->orWhere(function ($subquery) use ($q, $column) {
                        $subquery->where($column, 'like', "%{$q}%")
                          ->orWhere("{$column}->value", 'like', "%{$q}%");
                    });
                }
                foreach($model_fields as $field) {
                    if($field->type == 'relation') {
                        $relations = collect($settings['list_values'][$field->id])->filter(function ($item) use ($q) {
                            return mb_stristr($item['label']['text'], $q);
                        })->pluck('value')->toArray();
                        if(count($relations) && $field->is_plural) {
                            $query->orWhereHas($field->relation_table, function($subquery) use($relations) {
                                $subquery->whereIntegerInRaw('id', $relations);
                            })->get();
                        } elseif(count($relations)) {
                            $query->orWhere(function ($subquery) use ($field, $relations) {
                               foreach ($relations as $id) {
                                   $subquery->orWhere($field->field, $id);
                               }
                            });
                        }
                    }
                    if($field->type == 'date' && strtotime($q)) {
                        //($field, date('Y-m-d', strtotime($val)));
                        $date = date('Y-m-d', strtotime($q));
                        $query->orWhereDate($field->field, 'like', "%{$date}%");
                    }
                    if($slug == 'fines_gibdd' && $field->field == 'payment' && mb_strstr(mb_strtolower($q), 'оплат')) {
                        $query->orWhere("payment->state", 0);
                    }
                }
            });


        };

        if ($slug == 'logistic_tasks' && $request->filter && isset($request->filter['route_id']) && $request->filter['route_id'] != 'null') {
            $paginator = $paginator->reorder('sort', 'asc');
        }

        $paginator = $paginator->paginate(function($total) use ($limit){
            if(!$limit){
                return $total;
            }
            return $limit;
        });
        //$paginator = $paginator->paginate($limit);

        $objects = array();
        $field_values = array();
        if($slug == 'fines_gibdd') {
            $success_payments = \Modules\Gibdd\Entities\Module::getSuccessPayments();
        }
        foreach($model_fields as $field) {
            if($field->field == 'category_id' && $field->relation_table) {
                $category_slug = $field->relation_table;
                break;
            }
        };

        // Авторитетная проверка «жива ли связанная запись» для одиночных
        // relation-полей страницы: list_values может быть прочитан из
        // устаревшего кэша настроек (SettingsClearJob ещё в очереди), поэтому
        // удалённость проверяем по БД — один запрос на поле на страницу.
        // У живых строк удалённые связи скрываются, у строк в корзине —
        // показываются (см. маппинг ниже).
        $relation_alive = array();
        foreach($model_fields as $field) {
            if($field->type != 'relation' || $field->is_plural)
                continue;
            $rel_table = self::relationTableOf($field);
            if(!$rel_table)
                continue;
            $rel_ids = array();
            foreach ($paginator->items() as $item) {
                $v = $item->{$field->field};
                if($v && !is_array($v) && is_numeric($v))
                    $rel_ids[(int)$v] = true;
            }
            if(!count($rel_ids))
                continue;
            $rel_has_deleted_at = self::tableHasDeletedAt($rel_table);
            $rel_rows = \DB::table($rel_table)
                ->whereIntegerInRaw('id', array_keys($rel_ids))
                ->get($rel_has_deleted_at ? ['id', 'deleted_at'] : ['id']);
            foreach($rel_rows as $rel_row) {
                $relation_alive[$field->id][$rel_row->id] = $rel_has_deleted_at ? is_null($rel_row->deleted_at) : true;
            }
        }

        foreach ($paginator->items() as $item) {
            $data = array(
                'id' => $item->id
            );
            foreach($model_fields as $field) {
                if(!array_key_exists($field->field, $data) && $field->type != 'text_group' && $field->type != 'password' && (!isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'])) {
                    if($field->type == 'relation' && $field->relation_table && !$settings['models'][$field->relation_table]->enable)
                        continue;
                    $value = $item->{$field->field};
                    $field_value = ValueHelper::isJson($value) && $field->field != 'products' && is_array(json_decode($value, true)) ? json_decode($value, true) : $value;
                    if($field->field == 'products') {
                        $field_value = $item->getHtmlProducts();
                    }
                    if($field->type == 'relation' && $field->is_plural && $field->relation_table) {
                        $relation_table = $field->relation_table;
                        $relation_query = $item->{$relation_table}();
                        if (isset($item->deleted_at) && $item->deleted_at
                            && in_array(SoftDeletes::class, class_uses_recursive($relation_query->getRelated()))) {
                            // Строка в корзине — показываем и удалённые связи
                            $relation_query = $relation_query->withTrashed();
                        }
                        $field_value = $relation_query->get()->pluck('id')->toArray();
                    }
                    $data[$field->field] = $field_value;
                    $list_values = array();
                    if(isset($settings['list_values'][$field->id]))
                        $list_values = $settings['list_values'][$field->id];
                    if($field->type == 'file' && !isset($field_value[0])) {
                        $data[$field->field] = array($field_value);
                    }
                    if($field->type == 'file' && is_array($data[$field->field])) {
                        foreach($data[$field->field] as $k => $fval) {
                            if(!$fval)
                                unset($data[$field->field][$k]);
                        }
                        $data[$field->field] = array_values($data[$field->field]);
                    }
                    if($field->type == 'relation' && $field->is_plural) {
                        $values = $field_value;
                        $data[$field->field] = array(
                            'value' => array(),
                            'localOptions' => array()
                        );
                        if(is_array($values)) {
                            foreach($values as $val) {
                                if(isset($list_values[$val])) {
                                    $data[$field->field]['value'][] = $list_values[$val]['value'];
                                    $data[$field->field]['localOptions'][] = $list_values[$val];
                                } elseif(($table_option = self::listValueFromTable($field, $val))) {
                                    // Удалённая связь строки из корзины либо
                                    // живая запись, не попавшая в устаревший кэш
                                    $data[$field->field]['value'][] = $table_option['value'];
                                    $data[$field->field]['localOptions'][] = $table_option;
                                }
                            }
                        }
                    } elseif($field->type == 'relation' && $field_value && !is_array($field_value)) {
                        $row_trashed = isset($item->deleted_at) && $item->deleted_at;
                        $alive = is_numeric($field_value)
                            ? ($relation_alive[$field->id][(int)$field_value] ?? null)
                            : null;
                        if($alive === false && !$row_trashed) {
                            // Связанная запись удалена, строка живая — скрываем
                            // (даже если запись ещё есть в устаревшем list_values)
                            $data[$field->field] = array(
                                'value' => null,
                                'localOptions' => null
                            );
                        } elseif($alive === false && ($table_option = self::listValueFromTable($field, $field_value))) {
                            // Строка в корзине — показываем удалённую связь
                            $data[$field->field] = array(
                                'value' => array($table_option['value']),
                                'localOptions' => array($table_option)
                            );
                        } elseif($alive !== false && isset($list_values[$field_value])) {
                            $data[$field->field] = array(
                                'value' => array($list_values[$field_value]['value']),
                                'localOptions' => array($list_values[$field_value])
                            );
                        } elseif($alive === true && ($table_option = self::listValueFromTable($field, $field_value))) {
                            // Живая запись, которой нет в (устаревшем) кэше
                            $data[$field->field] = array(
                                'value' => array($table_option['value']),
                                'localOptions' => array($table_option)
                            );
                        } else {
                            $data[$field->field] = array(
                                'value' => null,
                                'localOptions' => null
                            );
                        }
                    } elseif($field->type == 'relation') {
                        $data[$field->field] = array(
                            'value' => null,
                            'localOptions' => null
                        );
                    }
                    if($field->type == 'file' && $field->is_link) {
                        $data[$field->field] = null;
                    }
                    if($field->type == 'file' && $field->is_link && $item->{$field->field}) {
                        $file = json_decode($item->{$field->field}, true);
                        $data[$field->field] = array(
                            'value' => $file['name'] != 'invoice.pdf' ? $file['name'] : $item->name,//$file['name'],
                            'external_link' => $file['file']
                        );
                    }



                }
            }

            if($slug == 'fines_gibdd' && isset($data['payment']['value'])) {
                if($data['sale_finish'] >= date('Y-m-d'))
                    $data['payment']['value'] = ceil($data['discount_sum'] / (100 - \Modules\Gibdd\Entities\Module::getPriceKoef()) * 100);
                else
                    $data['payment']['value'] = ceil($data['sum'] / (100 - \Modules\Gibdd\Entities\Module::getPriceKoef()) * 100);
                if(in_array($data['number_doc'], $success_payments))
                    $data['payment']['state'] = 1;
                //$data['payment']['value'] = round($data['payment']['value']*\Modules\Gibdd\Entities\Module::getPriceKoef());
            }

            $objects[$item->id] = $data;

        }


        if(isset($order) && $order && $slug == 'products') {
            $products_objects = array();
            $products = json_decode($order->products, true);
            $list_values = array();
            if(isset($settings['list_values'][$field->id]))
                $list_values = $settings['list_values'][$field->id];
            if(!is_array($products) || !count($products)) {
                return [
                    'count' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 25,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                    'data' => [],
                    'buttons' => $settings[$slug]['buttons'],
                    'sort_field' => $sort_field,
                    'sort_order' => $sort_order
                ];
            }

            if(is_array($products)) {
                foreach($products as $num => $product) {
                    if(isset($product['id']) && isset($objects[$product['id']])) {
                        $data = $objects[$product['id']];
                        //$data['product_name'] = isset($product['product_name']) ? $product['product_name'] : $product['name'];
                        $photo = $item->photo;
                        if($photo) {
                            $photo = json_decode($photo, true);
                            if(isset($photo[0]['url']))
                                $photo = $photo[0]['url'];
                        }
                        $data['product_id'] = array(
                            'value' => [$product['id']],
                            'localOptions' => [[
                                'value' => $product['id'],
                                'label' => [
                                    'id' => $product['id'],
                                    'sort' => 0,
                                    'file' => $photo,
                                    'is_hidden' => 0,
                                    'field_id' => 0,
                                    'color' => isset($item->color) ? $item->color : '',
                                    'text' => isset($product['name']) && !is_array($product['name']) ? $product['name'] : $item->name
                                ]
                            ]]
                        );
                        foreach($model_fields as $field) {
                            if($field->type != 'text_group' && $field->field != 'name' && $field->field != 'id' && (!isset($settings[$slug]['perms'][$field->field]['read']) || $settings[$slug]['perms'][$field->field]['read'])) {
                                if($field->type == 'date')
                                    $data['product_id']['localOptions'][0]['label'][$field->field] = \Carbon\Carbon::parse($item->{$field->field})->format('d.m.Y');
                                elseif($field->field == 'category_id')
                                    $data['product_id']['localOptions'][0]['label'][$field->field] = $item->categories->pluck('id')->toArray();
                                else
                                    $data['product_id']['localOptions'][0]['label'][$field->field] = $item->{$field->field};
                            }
                        }

                        $data['product_name'] = isset($product['name']) && !is_array($product['name']) ? $product['name'] : $item->name;

                        $data['product_price'] = $product['price'];
                        $data['product_count'] = $product['count'];
                        $data['product_weight'] = $product['weight'];
                        $data['product_sum'] = $product['sum'];
                        $data['sort'] = $num;
                        $products_objects[] = $data;
                    }
                }
                $objects = $products_objects;
            }
        };
        // if($sort_order == 'asc' && $sort_field)
        //      array_sort_by_column($objects, $sort_field, SORT_ASC, SORT_NATURAL);
        // elseif($sort_field)
        //      array_sort_by_column($objects, $sort_field, SORT_DESC, SORT_NATURAL);
        $fields_data[$field->field]['guide'] = null;
        if(is_array($guides) && isset($guides['fields'][$slug][$field->field]) && $show_hints)
                    $fields_data[$field->field]['guide'] = $guides['fields'][$slug][$field->field];

        // Задача 14: столбцы связанных сущностей (Компания/Автопарк/Сотрудники),
        // выведенные пользователем в таблицу Маршрутов. Ключ: rel__{slug}__{field}.
        if($slug == 'routes') {
            $rel_fk_map = ['companies' => 'company_id', 'cars' => 'car_id', 'employees' => 'employee_id'];

            // Настройки таблицы Маршрутов: сначала пользователь, затем роль, затем
            // глобальные — как в Table::get (rel-столбцы могут жить на любом уровне).
            $saved_fields = (isset($tables['routes']['fields']) && is_array($tables['routes']['fields'])) ? $tables['routes']['fields'] : null;
            if(empty($saved_fields)) {
                $u = \Auth::user();
                if($u && $u->role_id) {
                    $role = \App\Models\Role::find($u->role_id);
                    if($role && $role->tables) {
                        $rt = json_decode($role->tables, true);
                        if(isset($rt['routes']['fields']) && is_array($rt['routes']['fields'])) $saved_fields = $rt['routes']['fields'];
                    }
                }
            }
            if(empty($saved_fields)) {
                $gset = \DB::table('settings')->where('key', 'tables')->first();
                if($gset && $gset->value) {
                    $gt = json_decode($gset->value, true);
                    if(isset($gt['routes']['fields']) && is_array($gt['routes']['fields'])) $saved_fields = $gt['routes']['fields'];
                }
            }
            $saved_fields = is_array($saved_fields) ? $saved_fields : [];

            $rel_cols = array();
            foreach($saved_fields as $col) {
                $key = is_array($col) ? ($col['key'] ?? null) : (is_object($col) ? ($col->key ?? null) : null);
                $enabled = is_array($col) ? ($col['enabled'] ?? false) : (is_object($col) ? ($col->enabled ?? false) : false);
                if(!$key || strpos((string)$key, 'rel__') !== 0 || !$enabled)
                    continue;
                $parts = explode('__', $key);
                if(count($parts) < 3)
                    continue;
                $rslug = $parts[1];
                if(!isset($rel_fk_map[$rslug]))
                    continue;
                $rel_cols[] = ['key' => $key, 'slug' => $rslug, 'fk' => $rel_fk_map[$rslug], 'field' => implode('__', array_slice($parts, 2))];
            }
            if(count($rel_cols)) {
                $extractFk = function($v) {
                    if(is_array($v)) return $v[0] ?? null;
                    if(is_string($v) && ValueHelper::isJson($v)) {
                        $d = json_decode($v, true);
                        if(is_array($d)) return $d[0] ?? null;
                    }
                    return $v;
                };
                $rel_rows = array();
                foreach(array_unique(array_column($rel_cols, 'slug')) as $rslug) {
                    // Имя таблицы связанной сущности. Загружаем raw-запросом (а не
                    // через Eloquent-модель), чтобы не терять записи из-за
                    // soft-delete/глобальных scope — иначе значение в таблице пусто.
                    $rel_table = null;
                    if(isset($settings['models'][$rslug]) && $settings['models'][$rslug]->model_name) {
                        try { $rel_table = (new ($settings['models'][$rslug]->model_name))->getTable(); } catch (\Throwable $e) { $rel_table = null; }
                    }
                    if(!$rel_table) $rel_table = $rslug;
                    $fk = $rel_fk_map[$rslug];
                    $ids = array();
                    foreach($paginator->items() as $it) {
                        $id = $extractFk($it->{$fk} ?? null);
                        if(!empty($id)) $ids[] = $id;
                    }
                    $ids = array_values(array_unique($ids));
                    $rel_rows[$rslug] = array();
                    if(count($ids) && \Illuminate\Support\Facades\Schema::hasTable($rel_table)) {
                        foreach(\DB::table($rel_table)->whereIntegerInRaw('id', $ids)->get() as $m) {
                            $rel_rows[$rslug][$m->id] = $m;
                        }
                    }
                }
                foreach($paginator->items() as $it) {
                    if(!isset($objects[$it->id]))
                        continue;
                    foreach($rel_cols as $rc) {
                        $val = null;
                        $fkVal = $extractFk($it->{$rc['fk']} ?? null);
                        if($fkVal && isset($rel_rows[$rc['slug']][$fkVal])) {
                            $raw = $rel_rows[$rc['slug']][$fkVal]->{$rc['field']} ?? null;
                            $val = (ValueHelper::isJson($raw) && is_array(json_decode($raw, true))) ? json_decode($raw, true) : $raw;
                        }
                        $objects[$it->id][$rc['key']] = $val;
                    }
                }
            }
        }

        $res = array(
            'count' => $paginator->count(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'data' => array_values($objects),
            'buttons' => $settings[$slug]['buttons'],
            'sort_field' => $sort_field,
            'sort_order' => $sort_order,
            'restrictions' => $restrictions,
            'empty_text' => $entity->empty_text ?? 'Нет данных',
            'category_slug' => $category_slug,
            'guide' => is_array($guides) && isset($guides['entities'][$slug]) && $show_hints ? $guides['entities'][$slug] : null
        );
        return $res;

    }

    public static function copy($slug, $source, $fields)
    {
        $settings = app('settings');//\App\Models\Settings::get();

        $model_fields = $settings[$slug]['fields'];
        $history_response_events = $history_response_fields = $history_items = $history_events = $history_fields = $old_relations = $new_relations = array();
        $entity = $settings['models'][$slug];
        $entity_class = $entity->model_name;
        $object = new $entity_class;
        $object->saveQuietly();
        $new_id = $object->id;

        if(is_array($source->name))
            $val = $source->name['value'];
        elseif(!is_array($source->name) && json_decode($source->name, true) && !is_int(json_decode($source->name, true)))
            $val = json_decode($source->name, true)['value'];
        else
            $val = $source->name;

        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $object->id, 'user_id' => \Auth::user()->id, 'event' => 'OBJECT_COPIED', 'text' => "Создан на основании: <span data-slug='$slug' data-id='$source->id'>$val</span>", 'old_value' => $object->id, 'new_value' => $source->id]);
        $history->saveQuietly();
        $history_events[] = $history;

        if(isset($fields['name'])) {
            $object->name = is_array($fields['name']) ? json_encode($fields['name'], JSON_UNESCAPED_UNICODE) : $fields['name'];
        }


        $history = new \App\Models\History(['entity' => $slug, 'entity_id' => $source->id, 'user_id' => \Auth::user()->id, 'event' => 'OBJECT_COPIED', 'text' => "Создана копия: <span data-slug='$slug' data-id='$object->id'>$object->name</span>", 'old_value' => $source->id, 'new_value' => $object->id]);

        $history->saveQuietly();

        $history_items = \App\Models\History::getDataList($history_events);
        $history_response_events = array_merge($history_response_events, $history_items['fields']);

        unset($fields['route_id']);

        foreach($fields as $field => $value) {
            if($model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural && $model_fields[$field]->relation_table) {
                $relation_table = $model_fields[$field]->relation_table;
                $old_relations[$object->id] = array('field' => $field, 'entities' => array());
                $new_relations[$object->id] = array('field' => $field, 'entities' => array());
                $old_relations[$object->id]['entities'][$relation_table] = array();
                $new_relations[$object->id]['entities'][$relation_table] = array_filter($value, 'is_int');
            }
        }

        foreach($new_relations as $id => $change_relations) {
            foreach($change_relations['entities'] as $relation_table => $relations) {
                $related_key = $model_fields[$change_relations['field']]->related_field;

                $entity_relation = $settings['models'][$relation_table];
                $entity_relation_class = $entity_relation->model_name;
                $ids = array_unique(array_merge($relations, $old_relations[$id]['entities'][$relation_table]));
                $relation_objects = $entity_relation_class::whereIntegerInRaw('id', $ids)->get();
                foreach($relation_objects as $related_item) {
                    if(isset($related_item->{$slug})) {
                        $values = $related_item->{$slug}->pluck('id')->toArray();
                        if(in_array($related_item->id, $relations) && !in_array($id, $values)) {
                            $values[] = $id;
                            $related_item->sync_history($related_key, $values);
                        } elseif(!in_array($related_item->id, $relations) && in_array($id, $values)) {
                            unset($values[array_search($id, $values)]);
                            $related_item->sync_history($related_key, $values);
                        }
                    }
                }
            }
        }

        $fields['id'] = $object->id;
        $hobjects = \App\Models\History::saveForObject($slug, array($fields));
        $changed_fields = $hobjects['changed_fields'];
        foreach($hobjects['data']['fields'] as $h_item) {
            if($h_item['event'] == 'FIELD_UPDATED') {
                $history_response_fields[] = $h_item;
            } else {
                $history_response_events[] = $h_item;
            }
        }


        if(isset($fields['password'])) {
            $fields['password'] = Hash::make($fields['password']);
        }
        unset($fields['id']);

        foreach($fields as $field => $value) {
            if (is_numeric($field))
                continue;
            if($model_fields[$field]->type == 'relation' && !$model_fields[$field]->is_plural && is_array($value))
                $value = array_pop($value);
            if($model_fields[$field]->type == 'relation' && $model_fields[$field]->is_plural && $model_fields[$field]->relation_table) {
                $relation_table = $model_fields[$field]->relation_table;
                $new_values = array_filter($value, 'is_int');

                foreach($new_values as $nv) {
                    $new_values_keyby[$nv] = $nv;
                }

                if(method_exists($object->{$relation_table}(), 'sync')) {

                    $object->{$relation_table}()->sync($new_values);
                }
                $old_values = $object->{$relation_table}->pluck('id')->toArray();
                $old = array_diff($old_values, $new_values);
                $new = array_diff($new_values, $old_values);
                $related_rows = array();
                foreach($old as $o_item) {
                    $related_rows[] = array('id' => $o_item, $model_fields[$field]->related_field => null);
                }
                foreach($new as $n_item) {
                    $related_rows[] = array('id' => $n_item, $model_fields[$field]->related_field => $object->id);
                }

                $h = \App\Models\History::saveForObject($relation_table, $related_rows);

                // if($slug == 'companies')
                //     \DB::table($relation_table)->whereIntegerInRaw('id', $old_values)->update([$model_fields[$field]->related_field => null, 'choosed_at' => null]);

                // if(is_array($old_values))
                //     $only_new_values = array_diff($new_values, $old_values);
                // foreach($only_new_values as $sort => $v) {
                //     \DB::table($relation_table)->where('id', $v)->update([$model_fields[$field]->related_field => $object->id, 'choosed_at' => now()->addSeconds($sort)]);
                // }
                $object->load($relation_table);


            } elseif ($model_fields[$field]->type == 'relation' && $model_fields[$field]->field != 'user_id') {
                $related_rows = array();
                $relation_table = $model_fields[$field]->relation_table;
                $entity_relation = $settings['models'][$relation_table];
                $entity_relation_class = $entity_relation->model_name;
                if($object->{$model_fields[$field]->field}) {
                    $old_el_relations = $entity_relation_class::where('id', $object->{$model_fields[$field]->field})->first()->{$slug}()->pluck('id')->toArray();

                    if(in_array($object->id, $old_el_relations)) {
                        unset($old_el_relations[array_search($object->id, $old_el_relations)]);
                        if($relation_table == 'companies')
                            $object->choosed_at = null;
                        // \DB::table($slug)->whereIntegerInRaw('id', $old_values)->update([$model_fields[$field]->related_field => null, 'choosed_at' => null]);
                        $related_rows[] = array('id' => $object->{$model_fields[$field]->field}, $model_fields[$field]->related_field => $old_el_relations);
                    }
                }

                if($value) {
                    $new_el_relations = $entity_relation_class::where('id',$value)->first()->{$slug}()->pluck('id')->toArray();
                    $new_el_relations[] = $object->id;
                    $related_rows[] = array('id' => $value, $model_fields[$field]->related_field => $new_el_relations);
                }
                $h = \App\Models\History::saveForObject($relation_table, $related_rows);
            }


            if(!is_array($value) && is_array(json_decode($value, true))) {
                // Уже валидная JSON-строка (например, detail_text у type=redactor) —
                // сохраняем как есть, иначе повторный json_encode давал двойное
                // кодирование и ломал вывод статьи.
                $object->{$field} = $value;
            } else {
                $object->{$field} = $value;
            }
        }
        if(isset($model_fields['products']) && !array_key_exists('products', $fields) && $source->products) {
            $object->products = $source->products;
        }

        if(!$object->name) {
            $object->name = $entity->title_singular.' #'.$object->id;
        }

        $object->save();

        \App\Models\Settings::clear_cache();
        $settings = \App\Models\Settings::get(true);
        $object = $entity_class::where('id', $new_id)->first();
        $data = $object->getData($changed_fields, $settings);
        \App\Events\ObjectUpdated::dispatch('ObjectCreated', $data, tenant('id'));

        if(ValueHelper::isJson($object->name)) {
            if(is_array($object->name))
                $title = $object->name;
            else
                $title = json_decode($object->name, true);
            if(isset($title['value'])) {
                $title = $title['value'];
            } else {
                $title = null;
            }
        } else {
            $title = $object->name;
        }

        $data = ['id' =>$object->id, 'title'=>$title, 'details' => $data['viewDetail'], 'success' => true, 'history_events' => $history_response_events, 'history_fields' => $history_response_fields];


        return $data;
    }

}
