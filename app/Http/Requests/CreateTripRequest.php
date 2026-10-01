<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateTripRequest extends FormRequest
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
            'vehicle_id' => ['required', 'string', 'exists:vehicles,id'],
            'departure_city' => ['required', 'string'], 
            'arrival_city' => ['required', 'string'],
            'pickup_point' => ['required', 'string'],
            'departure_at' => ['required', 'date', 'after:now'],
            'seats_total' => ['required', 'integer', 'min:1'],
            'seats_available' => ['required', 'integer', 'min:0', 'lte:seats_total'],
            'price_per_seat' => ['required', 'integer', 'min:1'],
        ];
    }
}
