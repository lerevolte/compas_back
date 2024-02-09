<?
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Auth;
use App\Models\FieldSection;

class FieldSectionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $last_column_section = \DB::table('field_sections')->selecT('sort')->where('column_id', $request->column_id)->orderBy('sort', 'desc')->first();
        $item = FieldSection::create([
            'name' => $request->name,
            'page' => $request->page,
            'column_id' => $request->column_id,
            'sort' => ($last_column_section->sort + 1)
        ]);

        return response()->json($item);
    }

    public function update(Request $request, FieldSection $field_section)
    {
        $field_section->update($request->all());

        return response()->json($field_section);
    }

    public function changeSort(Request $request)
    {
        if($request->items_1 && count($request->items_1)) {
            $items = FieldSection::whereIn('id', $request->items_1)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items_1).")"))->get();
            foreach ($items as $key => $item) {
                $item->sort = $key;
                $item->column_id = 1;
                $item->save();
            }
        }

        if($request->items_2 && count($request->items_2)) {
            $items = FieldSection::whereIn('id', $request->items_2)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$request->items_2).")"))->get();
            foreach ($items as $key => $item) {
                $item->sort = $key;
                $item->column_id = 2;
                $item->save();
            }
        }
        
    }

    public function hide(Request $request) {
        \DB::table('field_sections')->where(['id' => $request->section])->update([
            'hide' => 1
        ]);

        return $request->section;
    }

    public function destroy(Request $request)
    {
        $section = FieldSection::find($request->section);
        if(!$section->fields()->count()){
            FieldSection::destroy($request->section);
        } else {
            return 'Удалите поля, привязанные к разделу!';
        }
    }
}
