<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    // paid_at/driver_arrival_confirmed_at/traveler_confirmed_at/funds_released_at ajoutes :
    // necessaires pour que le webhook (paid_at) et les futurs flows de confirmation
    // puissent les modifier via update() (sinon mass assignment les ignore silencieusement)
    protected $fillable = ['trip_id', 'traveler_id', 'seats_booked',
        'statut_validation', 'amount_total', 'paid_at',
        'driver_arrival_confirmed_at', 'traveler_confirmed_at', 'funds_released_at',
    ];
    protected $casts = [
        'paid_at' => 'datetime',
        'driver_arrival_confirmed_at' => 'datetime',
        'traveler_confirmed_at' => 'datetime',
        'funds_released_at' => 'datetime',
    ];

    public function trip() {
        return $this->belongsTo(Trip::class);
    }

    public function traveler() {
        return $this->belongsTo(TravelerProfile::class, 'traveler_id');
    }

    public function transctions() {
        return $this->hasMany(Transaction::class);
    }

    public function disputes() {
        return $this->hasMany(Dispute::class);
    }

    public function reviews() {
        return $this->hasMany(Review::class); 
    }
}
