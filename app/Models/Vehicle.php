<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    // aligne sur les vraies colonnes de la table 'vehicles' (l'ancienne liste ne correspondait a aucune colonne existante)
    protected $fillable = ['driver_profile_id', 'marque', 'modele', 'plaque_number', 'photo_path', 'status_verification'];

    public function driverProfile() {
        return $this->belongsTo(DriverProfile::class); 
    }
}
