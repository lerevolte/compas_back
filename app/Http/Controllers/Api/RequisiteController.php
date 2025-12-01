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
                        "id" => 1959,
                        "key" => "name",
                        "title" => "Название организации",
                        "type" => "text",
                        "value" => $item->name,
                        "required" => 1,
                        "object_id" => $item->id
                    ],
                    [
                        "id" => 1960,
                        "key" => "inn",
                        "title" => "ИНН",
                        "type" => "text",
                        "value" => $item->inn,
                        "object_id" => $item->id
                    ],
                    [
                        "id" => 1961,
                        "key" => "kpp",
                        "title" => "КПП",
                        "type" => "text",
                        "value" => $item->kpp,
                        "object_id" => $item->id
                    ],
                    [
                        "id" => 1962,
                        "key" => "address",
                        "title" => "Юридический адрес",
                        "type" => "text",
                        "value" => $item->address,
                        "object_id" => $item->id
                    ],
                    [
                        "id" => 1963,
                        "key" => "fact_address",
                        "title" => "Фактический адрес",
                        "type" => "text",
                        "value" => $item->fact_address,
                        "object_id" => $item->id
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
                        "required" => 1,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1960,
                        "key" => "inn",
                        "title" => "ИНН",
                        "type" => "text",
                        "value" => $data->inn,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1961,
                        "key" => "kpp",
                        "title" => "КПП",
                        "type" => "text",
                        "value" => $data->kpp,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1962,
                        "key" => "address",
                        "title" => "Юридический адрес",
                        "type" => "text",
                        "value" => $data->address,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1963,
                        "key" => "fact_address",
                        "title" => "Фактический адрес",
                        "type" => "text",
                        "value" => $data->fact_address,
                        "object_id" => $data->id
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
                        "value" => $data->name,
                        "required" => 1,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1960,
                        "key" => "inn",
                        "title" => "ИНН",
                        "type" => "text",
                        "value" => $data->inn,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1961,
                        "key" => "kpp",
                        "title" => "КПП",
                        "type" => "text",
                        "value" => $data->kpp,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1962,
                        "key" => "address",
                        "title" => "Юридический адрес",
                        "type" => "text",
                        "value" => $data->address,
                        "object_id" => $data->id
                    ],
                    [
                        "id" => 1963,
                        "key" => "fact_address",
                        "title" => "Фактический адрес",
                        "type" => "text",
                        "value" => $data->fact_address,
                        "object_id" => $data->id
                    ]
                )
            );

        return response()->json($data);
    }

    public function batch(Request $request)
    {
        if($request->rows) {
            $data = array();
            foreach($request->rows as $row) {
                if(isset($row['isNew'])) {
                    unset($row['id']);
                    unset($row['isNew']);
                    $req = Requisite::create($row);
                } else {
                    $id = $row['id'];
                    unset($row['id']);
                    Requisite::where('id', $id)->update($row);
                    $req = Requisite::find($id);
                }
                $data[] = array(
                    'id' => $req->id,
                    'company_id' => $req->company_id,
                    'fields' => array(
                        [
                            "id" => 1959,
                            "key" => "name",
                            "title" => "Название организации",
                            "type" => "text",
                            "value" => $req->name,
                            "required" => 1,
                            "object_id" => $req->id
                        ],
                        [
                            "id" => 1960,
                            "key" => "inn",
                            "title" => "ИНН",
                            "type" => "text",
                            "value" => $req->inn,
                            "object_id" => $req->id
                        ],
                        [
                            "id" => 1961,
                            "key" => "kpp",
                            "title" => "КПП",
                            "type" => "text",
                            "value" => $req->kpp,
                            "object_id" => $req->id
                        ],
                        [
                            "id" => 1962,
                            "key" => "address",
                            "title" => "Юридический адрес",
                            "type" => "text",
                            "value" => $req->address,
                            "object_id" => $req->id
                        ],
                        [
                            "id" => 1963,
                            "key" => "fact_address",
                            "title" => "Фактический адрес",
                            "type" => "text",
                            "value" => $req->fact_address,
                            "object_id" => $req->id
                        ]
                    )
                );
            }
        }
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
