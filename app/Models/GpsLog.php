<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsLog extends Model
{
    protected $table = 'gps_logs';

    protected $fillable = ['trip_id', 'latitude', 'longitude', 'type', 'horodatage'];
    protected $casts = ['horodatage' => 'datetime'];

    public function trip() {
        return $this->belongsTo(Trip::class);
    }

    public static function enregistrerPosition(int $tripId, float $lat, float $lng, string $type = 'position'): self {
        return self::create([
            'trip_id' => $tripId,
            'latitude' => $lat,
            'longitude' => $lng,
            'type' => $type,
        ]);
    }
}
