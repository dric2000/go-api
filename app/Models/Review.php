<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['driver_profile_id', 'booking_id', 'rating', 'review'];

    public function booking() {
        return $this->belongsTo(Booking::class);
    }

    public function driverProfile() {
        return $this->belongsTo(DriverProfile::class);
    }
}
