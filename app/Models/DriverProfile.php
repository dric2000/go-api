<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    protected $fillable = ['user_id', 'license_number', 'status_verification'];

    public function user() {
        return $this->belongsTo(User::class); 
    }

    public function vehicles() {
        return $this->hasMany(Vehicle::class); 
    }

    public function trips() {
        return $this->hasMany(Trip::class);
    }

    public function wallet() {
        return $this->hasOne(Wallet::class);
    }

    public function payouts() {
        return $this->hasMany(Payout::class);
    }

    public function reviews() {
        return $this->hasMany(Review::class);
    }
}
