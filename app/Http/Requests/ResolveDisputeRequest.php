<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'release' = on donne raison au chauffeur (deblocage des fonds)
            // 'refund' = on donne raison au voyageur (remboursement, geste manuel Fedapay ensuite)
            'action' => ['required', 'string', 'in:release,refund'],
            'resolution_note' => ['required', 'string'],
        ];
    }
}
