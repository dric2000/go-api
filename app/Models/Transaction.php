<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['booking_id', 'status', 'type', 'provider', 'provider_reference', 'amount'];
    protected $casts = ['amount' => 'integer'];


    public function booking() {
        return $this->belongsTo(Booking::class); 
    }
}
