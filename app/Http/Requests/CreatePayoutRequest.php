<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreatePayoutRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:1'],
            // Numéro Mobile Money sur lequel recevoir les fonds — pas
            // forcément celui du compte (et en sandbox, il faut un vrai
            // numéro de test Fedapay, pas le numéro réel de l'utilisateur).
            'phone_number' => ['required', 'string', 'regex:/^[0-9]{8,10}$/'],
        ];
    }
}
