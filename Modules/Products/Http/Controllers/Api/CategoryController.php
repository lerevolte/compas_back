<?php

namespace Modules\Products\Http\Controllers\Api;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Products\Entities\Category;
use Modules\Products\Entities\Product;

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
        $data = Category::create($request->all());
        $data = Category::where('id', $data->id)->with('children')->first();
        Category::fixTree();
        \App\Models\Settings::clear_cache();
        info($data);

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
        $data = Category::find($id);
        if(!$data) {
            return response()->json([
                'status' => 404,
            ]);
        }
        $data->update($request->all());
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
        $data = Category::find($id);
        if(!$data) {
            return response()->json([
                'status' => 404,
            ]);
        }
        $data->delete();
        Category::fixTree();
        cache()->flush();

        return response()->json([
            'status' => 200,
            'success' => true
        ]);
    }
}
