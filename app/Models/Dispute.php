<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    protected $fillable = ['booking_id', 'raised_by', 'reason', 'status'];

    public function booking() {
        return $this->belongsTo(Booking::class);
    }

    public function raisedBy() {
        return $this->belongsTo(User::class, 'raised_by');
    }
}
