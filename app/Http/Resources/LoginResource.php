<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            // Même règle que GET /user : évite au mobile de rester bloqué
            // sur isDriver=false jusqu'au prochain redémarrage de l'app.
            'is_driver' => (bool) ($this->driverProfile && $this->driverProfile->status_verification === 'approved'),
        ];
    }
}
