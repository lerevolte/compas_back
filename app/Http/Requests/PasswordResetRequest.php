<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class PasswordResetRequest extends FormRequest
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
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ];
    }

    public function messages()
    {

        return [
            'token.required' => 'Токен обязателен',
            'email.required' => 'Email обязателен',
            'password.required' => 'Пароль обязателен',
            'password.min' => 'Пароль не может быть короче 8 символов',
            'password.confirmed' => 'Пароли не совпадают'
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
