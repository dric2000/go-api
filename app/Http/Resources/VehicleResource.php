<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleResource extends JsonResource
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
            'modele' => $this->modele,
            'marque' => $this->marque,
            'plaque_number' => $this->plaque_number,
            // Le disque 'local' est privé : on expose une URL signée
            // temporaire plutôt que le chemin brut (inutilisable tel quel).
            'photo_url' => $this->photo_path
                ? Storage::disk('local')->temporaryUrl($this->photo_path, now()->addHour())
                : null,
            'status_verification' => $this->status_verification,
        ];
    }
}
