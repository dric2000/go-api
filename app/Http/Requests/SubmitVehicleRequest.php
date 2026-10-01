<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'modele' => ['string', 'required'], 
            'marque' => ['string', 'required'], 
            'plaque_number' => ['string', 'required', 'unique:vehicles,plaque_number', 'min:6'], 
            'photo' => ['file', 'required', 'mimes:jpg,jpeg,png', 'max:4096']
        ];
    }
}
