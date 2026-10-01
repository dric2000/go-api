<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = ['driver_profile_id', 'amount', 'provider_reference', 'status'];
    protected $casts = ['amount' => 'integer'];

    public function driverProfile() {
        return $this->belongsTo(DriverProfile::class);
    }
}
