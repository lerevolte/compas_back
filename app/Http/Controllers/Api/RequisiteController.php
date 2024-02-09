<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Requisite;

class RequisiteController extends Controller
{
    public function list(Request $request, $company_id = null)
    {
        $items = Requisite::where('company_id', $company_id)->get();
        $data = array();

        foreach ($items as $item) {
            $data[] = array(
                'id' => $item->id,
                'company_id' => $item->company_id,
                'fields' => array(
                    [
                        "id" => 2418,
                        "key" => "id",
                        "title" => "ID",
                        "type" => "number",
                        "value" => $item->id
                    ],
                    [
                        "id" => 1959,
                        "key" => "name",
                        "title" => "Название организации",
                        "type" => "text",
                        "value" => $item->name,
                        "required" => 1
                    ],
                    [
                        "id" => 1960,
                        "key" => "inn",
                        "title" => "ИНН",
                        "type" => "text",
                        "value" => $item->inn
                    ],
                    [
                        "id" => 1961,
                        "key" => "kpp",
                        "title" => "КПП",
                        "type" => "text",
                        "value" => $item->kpp
                    ],
                    [
                        "id" => 1962,
                        "key" => "address",
                        "title" => "Юридический адрес",
                        "type" => "text",
                        "value" => $item->address
                    ],
                    [
                        "id" => 1963,
                        "key" => "fact_address",
                        "title" => "Фактический адрес",
                        "type" => "text",
                        "value" => $item->fact_address
                    ]
                )
            );
        }


        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = Requisite::create($request->all());
        $data = Requisite::find($data->id);

        $data = array(
                'id' => $data->id,
                'company_id' => $data->company_id,
                'fields' => array(
                    [
                        "id" => 1959,
                        "key" => "name",
                        "title" => "Название организации",
                        "type" => "text",
                        "value" => $data->name,
                    ],
                    [
                        "id" => 1960,
                        "key" => "inn",
                        "title" => "ИНН",
                        "type" => "text",
                        "value" => $data->inn
                    ],
                    [
                        "id" => 1961,
                        "key" => "kpp",
                        "title" => "КПП",
                        "type" => "text",
                        "value" => $data->kpp
                    ],
                    [
                        "id" => 1962,
                        "key" => "address",
                        "title" => "Юридический адрес",
                        "type" => "text",
                        "value" => $data->address
                    ],
                    [
                        "id" => 1963,
                        "key" => "fact_address",
                        "title" => "Фактический адрес",
                        "type" => "text",
                        "value" => $data->fact_address
                    ]
                )
            );

        return response()->json($data);
    }

    public function update($id, Request $request)
    {
        $data = Requisite::find($id);
        if(!$data) {
            return response()->json([
                'status' => 404,
            ]);
        }
        $data->update($request->all());

        $data = array(
                'id' => $data->id,
                'company_id' => $data->company_id,
                'fields' => array(
                    [
                        "id" => 1959,
                        "key" => "name",
                        "title" => "Название организации",
                        "type" => "text",
                        "value" => $data->name
                    ],
                    [
                        "id" => 1960,
                        "key" => "inn",
                        "title" => "ИНН",
                        "type" => "text",
                        "value" => $data->inn
                    ],
                    [
                        "id" => 1961,
                        "key" => "kpp",
                        "title" => "КПП",
                        "type" => "text",
                        "value" => $data->kpp
                    ],
                    [
                        "id" => 1962,
                        "key" => "address",
                        "title" => "Юридический адрес",
                        "type" => "text",
                        "value" => $data->address
                    ],
                    [
                        "id" => 1963,
                        "key" => "fact_address",
                        "title" => "Фактический адрес",
                        "type" => "text",
                        "value" => $data->fact_address
                    ]
                )
            );

        return response()->json($data);
    }

    public function destroy($id) {
        $requisite = Requisite::find($id);
        if(!$requisite) {
            return response()->json([
                'status' => 404,
            ]);
        }
        $requisite->delete();

        return response()->json([
            'status' => 200,
            'success' => true
        ]);
    }
}
