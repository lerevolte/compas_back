<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class LoginRequest extends FormRequest
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
            'email' => 'required|exists:users',
            'password' => 'required',
        ];
    }

    public function messages()
    {

        return [
            'domain.required' => 'Домен обязателен',
            'email.required' => 'Email обязателен',
            'email.exists' => 'Пользователя не существует',
            'email.email' => 'Неверный формат email',
            'password.required' => 'Пароль обязателен'
        ];

    }

    public function failedValidation(Validator $validator) {
        info('login');
        //info($request->email);
        info(tenant('id'));
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Ошибки валидации',
            'data'      => $validator->errors()
        ]));
        
    }
}
