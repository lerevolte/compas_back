<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use \App\Models\User;
use \App\Models\Role;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use \TCG\Voyager\Http\Controllers\VoyagerUserController;
use Illuminate\Support\Facades\Hash;

class UserController extends VoyagerUserController
{
    public function get_drivers(Request $request)
    {
        $drivers = Role::find(11)->users;

        $drivers = $drivers->filter(function ($item, $key) {
            return $item->latitude && $item->longitude;
        });

        return response()->json($drivers);
    }

    public function profile(Request $request)
    {
        $current = \App\Models\User::find(Auth::user()->id);
        $fields = \App\Models\Field::getVisibleFields('users');
        $hidden_fields = \App\Models\Field::getHiddenFields('users');
        $sections = \App\Models\FieldSection::get('users');

        return view('users.profile', compact('current', 'fields', 'hidden_fields', 'sections'));
    }

    public function edit_password(Request $request)
    {
        $current = \App\Models\User::find(Auth::user()->id);
        $fields = \App\Models\Field::getVisibleFields('users');
        $hidden_fields = \App\Models\Field::getHiddenFields('users');
        $sections = \App\Models\FieldSection::get('users');

        return view('users.edit_password', compact('current', 'fields', 'hidden_fields', 'sections'));
    }

    public function update_password(Request $request)
    {
        $request->validate([
            'password' => ['required'],
            'confirm_password' => ['same:password'],
        ]);
   
        User::find(Auth::user()->id)->update(['password'=> Hash::make($request->password)]);

        return redirect('/admin/login');
    }

    public function update_profile(Request $request, User $user)
    {
        info('UPD USER');
        info($request->all());
        $model = 'users';
        $id = $user->id;
        $user->update($request->all());
        $fields = \App\Models\Field::getVisibleFields('users');
        $post_files = $request->allFiles();
        $file_fields = array();
        foreach($fields as $field) {
            if($field->type == 'image' || $field->type == 'file')
                $file_fields[] = $field->field;
        }
        foreach($file_fields as $field_name) {
            $data_files = array();
            if(!isset($post_files[$field_name])) {
                $data_files[$field_name] = null; 
            } else {
                $files = $post_files[$field_name];
                $file_list = array();
                foreach($files as $file) {
                    $file = $file->store('public/avatars');
                    $document = new \App\Models\File();
                    $document->name = pathinfo($file)['basename'];
                    $document->path = $file;
                    $document->save();
                    $file_list[] = $document->id;
                }
                $data_files[$field_name] = json_encode($file_list); 
            }
        }
        \DB::table($model)->where(['id' => $id])->update($data_files);
        // if (count($request->allFiles())) {

        //     foreach($request->allFiles() as $field_name => $files) {
                
        //     }
        // }

        return redirect()->back();
        return response()->json($request->all());
    }

    public function generate_token()
    {
        $token = \Auth::user()->generateToken();

        return $token;
    }

    public function index(Request $request)
    {
        $roles = Role::orderBy('sort')->get();
        $users_wo_role = User::has('roles', 0)->get();

        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = 'users';

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name.'.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';

                $searchField = $dataType->name.'.'.$search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name.'.*',
                        'joined.'.$row->details->label.' as '.$orderBy,
                    ])->leftJoin(
                        $row->details->table.' as joined',
                        $dataType->name.'.'.$row->details->column,
                        'joined.'.$row->details->key,
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        return Voyager::view($view, compact(
            'roles',
            'users_wo_role',
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn'
        ));
    }

    public function edit(Request $request, $id)
    {
        $roles = Role::orderBy('sort')->get();
        $users_wo_role = User::has('roles', 0)->get();

        $slug = 'users';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        $fields = \App\Models\Field::getVisibleFields('users');
        $hidden_fields = \App\Models\Field::getHiddenFields('users');
        $sections = \App\Models\FieldSection::get('users');

        return Voyager::view($view, compact('roles', 'users_wo_role', 'dataType', 'dataTypeContent', 'isModelTranslatable', 'fields', 'hidden_fields', 'sections'));
    }

    public function create(Request $request)
    {
        $roles = Role::orderBy('sort')->get();
        $users_wo_role = User::has('roles', 0)->get();
        
        $slug = 'users';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
                            ? new $dataType->model_name()
                            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        $fields = \App\Models\Field::getVisibleFields('users');
        $hidden_fields = \App\Models\Field::getHiddenFields('users');
        $sections = \App\Models\FieldSection::get('users');

        return Voyager::view($view, compact('roles', 'users_wo_role', 'dataType', 'dataTypeContent', 'isModelTranslatable', 'fields', 'hidden_fields', 'sections'));
    }

    public function store(Request $request)
    {
        $slug = 'users';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        
  

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());
        $data->last_name = $request->last_name;
        $data->save();
        event(new BreadDataAdded($dataType, $data));
        if ($files = $request->file('avatar')) {
            foreach($files as $file) {
                $file = $file->store('public/avatars');
                $model = 'users';
                $id = $data->id;
                $field = 'avatar';
                $document = new \App\Models\File();
                $document->name = pathinfo($file)['basename'];
                $document->path = $file;
                $document->save();

                $file_list = array();
                $file_list[] = $document->id;
                \DB::table($model)->where(['id' => $id])->update([
                    $field => json_encode($file_list)
                ]);
                // return Response()->json([
                //     "success" => true,
                //     "file" => str_replace('/public', '', \Storage::disk('public')->url($file))
                // ]);
            }
            
  
        }
        cache()->flush();
        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("users.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }

    public function destroy(Request $request, $id)
    {
        User::find($id)->delete();
    }

    public function restore(Request $request, $id)
    {
        $item = User::withTrashed()->find($id);
        $item->restore();
    }

    public function changeSort(Request $request)
    {
        $items = User::whereIn('id', $request->items)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items).")"))->get();
        foreach ($items as $key => $item) {
            $item->sort = $key;
            $item->save();
        }
    }

    public function update(Request $request, $id)
    {
        if($request->password) {
            User::find($id)->update(['password'=> Hash::make($request->password)]);
        };
   
        //$user = User::find($id);
        // $user->name = $request->NAME;
        // $user->email = $request->EMAIL;
        // $user->crm_id = $request->crm_id;
        // $user->saveQuietly();
        //$user->update($request->all());
        // dd($request->user_belongstomany_role_relationship);
        // if (Auth::user()->getKey() == $id) {
        //     $request->merge([
        //         'role_id'                              => Auth::user()->role_id,
        //         'user_belongstomany_role_relationship' => Auth::user()->roles->pluck('id')->toArray(),
        //     ]);
        // }
        
        $data = array();
        foreach($request->all() as $field => $value) {
            $data[strtolower($field)] = $value;
        }
        
        //dd($request->allFiles());
        $slug = 'users';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        $request->merge($data);
        
        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        $query = $model->query();
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $query->withTrashed();
        }

        $data = $query->findOrFail($id);

        // Check permission
        $this->authorize('edit', $data);

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        // Get fields with images to remove before updating and make a copy of $data
        $to_remove = $dataType->editRows->where('type', 'image')
            ->filter(function ($item, $key) use ($request) {
                return $request->hasFile($item->field);
            });
        $original_data = clone($data);
        //dd($data);
        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        // Delete Images
        $this->deleteBreadImages($original_data, $to_remove);

        $redirect = redirect()->back();

        if (count($request->allFiles())) {
            foreach($request->allFiles() as $field_name => $files) {
                foreach($files as $file) {
                    $file = $file->store('public/avatars');
                    $model = 'users';
                    $id = $id;
                    $field = $field_name;
                    $document = new \App\Models\File();
                    $document->name = pathinfo($file)['basename'];
                    $document->path = $file;
                    $document->save();

                    $file_list = array();
                    $file_list[] = $document->id;
                    \DB::table($model)->where(['id' => $id])->update([
                        $field => json_encode($file_list)
                    ]);
                }
            }
        }
        cache()->flush();
        
        return $redirect->with([
            'alert-type' => 'success',
        ]);


    }


}