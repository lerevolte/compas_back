<?php

namespace Modules\Products\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function list(Request $request)
    {
        $categories = Category::get()->toTree()->toArray();

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $fields = $this->validated($request);

        $data = Category::create($fields);
        $data = Category::where('id', $data->id)->with('children')->first();
        Category::fixTree();
        \App\Models\Settings::clear_cache();

        return response()->json($data);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = Category::find($id);
        if(!$data) {
            return response()->json([
                'status' => 404,
            ]);
        }
        $fields = $this->validated($request);
        if (isset($fields['parent_id']) && (int) $fields['parent_id'] === (int) $id) {
            unset($fields['parent_id']);
        }
        $data->update($fields);
        Category::fixTree();
        \App\Models\Settings::clear_cache();

        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if (!$this->canManage()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = Category::find($id);
        if(!$data) {
            return response()->json([
                'status' => 404,
            ]);
        }
        $ids = Category::descendantsAndSelf($data->id)->pluck('id')->toArray();
        Category::whereIntegerInRaw('id', $ids)->get()->each->delete();
        if (\Schema::hasColumn('products', 'category_id')) {
            \DB::table('products')->whereIntegerInRaw('category_id', $ids)->update(['category_id' => null]);
        }
        Category::fixTree();
        \App\Models\Settings::clear_cache();

        return response()->json([
            'status' => 200,
            'success' => true
        ]);
    }

    private function validated(Request $request): array
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);
        if (array_key_exists('parent_id', $fields) && !$fields['parent_id']) {
            $fields['parent_id'] = null;
        }
        return $fields;
    }

    private function canManage(): bool
    {
        $user = \Auth::user();
        if (!$user) {
            return false;
        }
        if ($user->is_admin) {
            return true;
        }
        $entityId = \DB::table('data_types')->where('slug', 'products')->value('id');
        if (!$entityId) {
            return false;
        }
        $perm = \DB::table('permissions')
            ->where('role_id', $user->role_id)
            ->where('entity_id', $entityId)
            ->first();
        return !$perm || $perm->update_p != 'N';
    }
}
