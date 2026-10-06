<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseArticleCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpenseArticleCategoryController extends Controller
{
    private const ENTITY = 'expense_articles';

    public function list(): JsonResponse
    {
        if (!$this->ready()) {
            return response()->json([]);
        }

        return response()->json(ExpenseArticleCategory::get()->toTree()->toArray());
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->ready() || !$this->canManage()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $category = ExpenseArticleCategory::create($this->validated($request));
        ExpenseArticleCategory::fixTree();
        \App\Models\Settings::clear_cache();

        return response()->json(['id' => $category->id, 'name' => $category->name, 'parent_id' => $category->parent_id]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if (!$this->ready() || !$this->canManage()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $category = ExpenseArticleCategory::find($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $fields = $this->validated($request);
        if (isset($fields['parent_id']) && in_array((int) $fields['parent_id'], ExpenseArticleCategory::descendantsAndSelf($category->id)->pluck('id')->map(fn ($v) => (int) $v)->all(), true)) {
            unset($fields['parent_id']);
        }
        $category->update($fields);
        ExpenseArticleCategory::fixTree();
        \App\Models\Settings::clear_cache();

        return response()->json(['id' => $category->id, 'name' => $category->name, 'parent_id' => $category->parent_id]);
    }

    public function destroy($id): JsonResponse
    {
        if (!$this->ready() || !$this->canManage()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $category = ExpenseArticleCategory::find($id);
        if (!$category) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $ids = ExpenseArticleCategory::descendantsAndSelf($category->id)->pluck('id')->toArray();
        ExpenseArticleCategory::whereIntegerInRaw('id', $ids)->get()->each->delete();
        if (Schema::hasColumn(self::ENTITY, 'category_id')) {
            DB::table(self::ENTITY)->whereIn('category_id', array_map('strval', $ids))->update(['category_id' => null]);
        }
        ExpenseArticleCategory::fixTree();
        \App\Models\Settings::clear_cache();

        return response()->json(['status' => 200, 'success' => true]);
    }

    private function validated(Request $request): array
    {
        if ($request->exists('parent_id')) {
            $raw = $request->input('parent_id');
            if (is_array($raw)) {
                $raw = $raw['value'] ?? null;
            }
            $request->merge(['parent_id' => is_numeric($raw) ? (int) $raw : null]);
        }
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:expense_article_categories,id',
        ]);
        if (array_key_exists('parent_id', $fields) && !$fields['parent_id']) {
            $fields['parent_id'] = null;
        }

        return $fields;
    }

    private function ready(): bool
    {
        return Schema::hasTable('expense_article_categories');
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
        $entityId = DB::table('data_types')->where('slug', self::ENTITY)->value('id');
        if (!$entityId) {
            return false;
        }
        $perm = DB::table('permissions')->where('role_id', $user->role_id)->where('entity_id', $entityId)->first();

        return !$perm || $perm->update_p != 'N';
    }
}
