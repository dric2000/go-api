<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type,
            'amount' => $this->amount,
            'provider' => $this->provider,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'trip' => $this->when(
                $this->relationLoaded('booking') && $this->booking?->relationLoaded('trip'),
                fn () => [
                    'departure_city' => $this->booking->trip->departure_city,
                    'arrival_city' => $this->booking->trip->arrival_city,
                ],
            ),
            // Présent uniquement côté conducteur (vue sur ses propres trajets) :
            // un voyageur n'a pas besoin de voir son propre nom sur ses transactions.
            'traveler' => $this->when(
                $this->relationLoaded('booking')
                    && $this->booking?->relationLoaded('traveler')
                    && $this->booking->traveler?->relationLoaded('user'),
                fn () => ['name' => $this->booking->traveler->user->name],
            ),
        ];
    }
}
