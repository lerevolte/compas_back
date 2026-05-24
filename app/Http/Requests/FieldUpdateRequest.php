<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\DTO\FieldUpdateDto;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

final class FieldUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            
        ];
    }
    
    public function getDto(): FieldUpdateDto
    {
        return new FieldUpdateDto(
            $this->has('title') ? $this->get('title') : null,
            $this->has('set_color') ? $this->get('set_color') : null,
            $this->has('set_color') && $this->has('color') && $this->get('set_color') ? $this->get('color') : null,
            $this->has('unit') ? $this->get('unit') : null,
            $this->has('button_name') ? $this->get('button_name') : '',
            $this->has('section_id') ? $this->get('section_id') : null,
            $this->has('visible_always') ? $this->get('visible_always') : null,
            $this->has('is_external_link') ? $this->get('is_external_link') : null,
            $this->has('is_plural') ? $this->get('is_plural') : null,
            $this->has('is_hidden') ? $this->get('is_hidden') : null,
            $this->has('required') ? $this->get('required') : null,
            $this->has('show_file_name') ? $this->get('show_file_name') : null,
            $this->has('options') ? $this->get('options') : array(),
            $this->has('has_roles_read') ? $this->get('has_roles_read') : null,
            $this->has('has_roles_write') ? $this->get('has_roles_write') : null,
            $this->has('roles_read') ? $this->get('roles_read') : array(),
            $this->has('subfields') ? $this->get('subfields') : array(),
            $this->has('roles_write') ? $this->get('roles_write') : array(),
            $this->has('change_section') ? $this->get('change_section') : null,
            $this->has('sort') ? $this->get('sort') : null,
            $this->has('can_create') ? (int)$this->get('can_create') : null,
        );
    }

    public function messages()
    {
        return [
            
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