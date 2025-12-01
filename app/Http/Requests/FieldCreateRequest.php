<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\FieldCreateDto;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

final class FieldCreateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required',
            'type' => 'required'
        ];
    }
    
    public function getDto(): FieldCreateDto
    {
        return new FieldCreateDto(
            $this->has('id') ? $this->get('id') : null,
            $this->has('entity') ? $this->get('entity') : null,
            $this->get('title'),
            $this->has('set_color') ? $this->get('set_color') : null,
            $this->has('set_color') && $this->has('color') && $this->get('set_color') ? $this->get('color') : null,
            $this->get('type'),
            $this->has('unit') ? $this->get('unit') : null,
            $this->has('button_name') ? $this->get('button_name') : '',
            $this->has('section_id') ? $this->get('section_id') : null,
            $this->has('visible_always') ? $this->get('visible_always') : 0,
            $this->has('is_external_link') ? $this->get('is_external_link') : 0,
            $this->has('is_plural') ? $this->get('is_plural') : 0,
            $this->has('required') ? $this->get('required') : 0,
            $this->has('show_file_name') ? $this->get('show_file_name') : null,
            $this->has('options') ? $this->get('options') : array(),
            $this->has('has_roles_read') ? $this->get('has_roles_read') : null,
            $this->has('has_roles_write') ? $this->get('has_roles_write') : null,
            $this->has('roles_read') ? $this->get('roles_read') : array(),
            $this->has('subfields') ? $this->get('subfields') : array(),
            $this->has('roles_write') ? $this->get('roles_write') : array()
        );
    }

    public function messages()
    {

        return [
            'title.required' => 'Заполните название поля',
            'type.required' => 'Заполните тип поля'
        ];

    }

    public function failedValidation(Validator $validator)
    {
        info($validator->errors());
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));
        
    }
}