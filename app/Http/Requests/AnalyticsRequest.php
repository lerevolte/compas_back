<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class AnalyticsRequest extends FormRequest
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
        $entity = request()->entity;

        return [
            //'entity' => 'exists:data_types,slug',
            'field' => [
                //'required',
                function (string $attribute, mixed $value, Closure $fail) use ($entity) {
                    $val = $value;
                    if(strstr($value, '.')) {
                        $val = explode('.', $value)[0];
                    }
                    if (!\Schema::hasColumn($entity, $val)) {
                        $fail("The {$val} doesn't exist in {$entity}.");
                    }
                },
            ],
            'period' => 'array',
            'period.start' => 'date',
            'period.end' => 'date',
            'condition' => 'array',
            'group_by' => [
                'string',
                function (string $attribute, mixed $value, Closure $fail) use ($entity) {
                    if (!\Schema::hasColumn($entity, $value)) {
                        $fail("The {$value} doesn't exist in {$entity}.");
                    }
                }
            ]
            //date_field
        ];
    }

    public function messages()
    {

        return [
            // 'domain.required' => 'Домен обязателен',
            // 'email.required' => 'Email обязателен',
            // 'email.exists' => 'Пользователя не существует',
            // 'email.email' => 'Неверный формат email',
            // 'password.required' => 'Пароль обязателен'
        ];

    }

    public function failedValidation(Validator $validator) {

        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Ошибки валидации',
            'data'      => $validator->errors()
        ]));
        
    }
}
