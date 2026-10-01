<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = ['driver_profile_id','vehicle_id', 'departure_city', 'arrival_city', 'pickup_point',
                            'departure_at', 'seats_total', 'seats_available', 'price_per_seat',
                            'status', 'started_at', 'current_lat', 'current_lng', 'position_updated_at',
                        ];
    protected $casts = [
        'departure_at' => 'datetime',
        'started_at' => 'datetime',
        'position_updated_at' => 'datetime',
    ];

    public function driverProfile() {
        return $this->belongsTo(DriverProfile::class);
    }

    public function vehicle() {
        return $this->belongsTo(Vehicle::class);
    }

    public function bookings() {
        return $this->hasMany(Booking::class);
    }

    public function gpsLogs() {
        return $this->hasMany(GpsLog::class);
    }
}
