<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'trip_id' => $this->trip_id,
            'seats_booked' => $this->seats_booked,
            'statut_validation' => $this->statut_validation,
            'amount_total' => $this->amount_total,
            'paid_at' => $this->paid_at,
            'driver_arrival_confirmed_at' => $this->driver_arrival_confirmed_at,
            'traveler_confirmed_at' => $this->traveler_confirmed_at,
            'funds_released_at' => $this->funds_released_at,
            'trip' => new TripResource($this->whenLoaded('trip')),
            'traveler' => $this->when(
                $this->relationLoaded('traveler') && $this->traveler->relationLoaded('user'),
                fn () => [
                    'name' => $this->traveler->user->name,
                    'phone' => $this->traveler->user->phone,
                ],
            ),
        ];
    }
}
