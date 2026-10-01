<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => [
                'required',
                'string',
                'regex:/^(\+229)?[0-9]{8,10}$/',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
        ];
    }
}
