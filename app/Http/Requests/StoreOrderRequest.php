<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
        $fields = Order::getFields();
        foreach ($fields as $key => $field) {
            $rule = array();
            if($field->required)
                $rule[] = 'required';
            if($field->type == 'number')
                $rule[] = 'digits';
            if($field->details) {
                $field_details = json_decode($field->details, true);
                $options = array();
                if(isset($field_details['table'])) {

                    $rule[] = Rule::exists($field_details['table'], 'id');
                } elseif(isset($field_details['options'])) {
                    $rule[] = Rule::in(array_keys($field_details['options']));
                }
            }
            if(count($rule))
                $rules[$field->field] = $rule;
        }

        return $rules;
    }

    // public function failedValidation(Validator $validator)
    // {
    //    throw new HttpResponseException(response()->json([
    //      'success'   => false,
    //      'message'   => 'Validation errors',
    //      'data'      => $validator->errors()
    //    ]));
    // }
}
