<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
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
            'vehicle_id' => $this->vehicle_id,
            'departure_city' => $this->departure_city,
            'arrival_city' => $this->arrival_city,
            'pickup_point' => $this->pickup_point,
            'departure_at' => $this->departure_at,
            'seats_total' => $this->seats_total,
            'seats_available' => $this->seats_available,
            'price_per_seat' => $this->price_per_seat,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'arrival_lat' => $this->arrival_lat,
            'arrival_lng' => $this->arrival_lng,
            'driver_name' => $this->formatDriverName(),
            'vehicle' => $this->when($this->vehicle, fn () => [
                'marque' => $this->vehicle->marque,
                'modele' => $this->vehicle->modele,
            ]),
        ];
    }

    // Prénom + initiale du nom (ex: "Abdoulaye K."), jamais le nom complet :
    // cette page est visible sans compte, on ne publie pas l'identité entière.
    private function formatDriverName(): ?string
    {
        $name = $this->driverProfile?->user?->name;
        if (! $name) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) < 2) {
            return $parts[0];
        }

        return $parts[0] . ' ' . mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.';
    }
}
