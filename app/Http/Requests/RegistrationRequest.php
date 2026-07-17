<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class RegistrationRequest extends FormRequest
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
            'domain' => 'string|max:12|regex:/^[A-Za-z0-9]+$/i',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'owner_type' => 'nullable|integer|in:1,2,3',
            'phone' => 'nullable|string|max:30',
        ];
    }

    public function messages()
    {

        return [
            'domain.string' => 'Домен должен быть строкой',
            'domain.max' => 'Домен не может быть длиннее 12 символов',
            'domain.regex' => 'Некорректный формат домена',
            'email.required' => 'Email обязателен',
            'email.email' => 'Неверный формат email',
            'password.required' => 'Пароль обязателен',
            'password.min' => 'Пароль не может быть короче 8 символов',
            'password.confirmed' => 'Пароли не совпадают',
            'owner_type.in' => 'Некорректное значение поля «Кем вы являетесь»',
            'phone.max' => 'Телефон не может быть длиннее 30 символов'
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
