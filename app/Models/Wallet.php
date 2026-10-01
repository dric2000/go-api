<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    protected $fillable = ['driver_profile_id', 'solde_bloque', 'solde_disponible'];
    protected $casts = ['solde_bloque' => 'integer', 'solde_disponible' => 'integer'];

    public function driverProfile() {
        return $this->belongsTo(DriverProfile::class);
    }

    public function crediter(int $montant): void {
        $this->increment('solde_disponible', $montant);
    }

    public function debiter(int $montant): void {
        $this->decrement('solde_disponible', $montant);
    }

    public function debloquer (int $montant): void {
        DB::transaction(function () use ($montant) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first(); 

            if ($wallet->solde_bloque < $montant) {
                throw new \RuntimeException("Solde bloqué insuffisant pour débloquer {$montant}."); 
            }

            $wallet->decrement('solde_bloque', $montant); 
            $wallet->increment('solde_disponible', $montant); 
        }); 
    }

    public function retirer(int $montant): void
    {
        DB::transaction(function () use ($montant) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->solde_disponible < $montant) {
                throw new \RuntimeException("Solde disponible insuffisant.");
            }

            $wallet->decrement('solde_disponible', $montant);
        });
    }

    // retire des fonds de solde_bloque SANS les crediter sur solde_disponible :
    // utilise quand un litige est resolu en faveur du voyageur, l'argent sort de la
    // sequestration sans jamais devenir disponible pour le chauffeur (remboursement)
    public function annulerBlocage(int $montant): void
    {
        DB::transaction(function () use ($montant) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->solde_bloque < $montant) {
                throw new \RuntimeException("Solde bloqué insuffisant pour annuler {$montant}.");
            }

            $wallet->decrement('solde_bloque', $montant);
        });
    }

}
