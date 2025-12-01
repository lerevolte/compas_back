<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Http\Exceptions\HttpResponseException;


class StoreFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return [
            // 'files'  => 'required|mimes:doc,docx,pdf,txt,jpg,png,svg,xlsx,xls,rar|max:65536',
            //'files'  => 'required',
            'files.*' => 'required|file|max:100000'
        ];
    }

    public function messages()
    {

        return [
            'files.*.required' => 'Файл обязателен',
            'files.*.max' => 'Максимальный размер загружаемого файла - 100мб'
        ];

    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));
        
    }
}
