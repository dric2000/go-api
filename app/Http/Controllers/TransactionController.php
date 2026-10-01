<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // Historique des transactions de l'utilisateur connecté : les trajets
    // qu'il a conduits s'il est conducteur approuvé, sinon ses réservations
    // en tant que voyageur. Un compte peut être les deux à la fois côté
    // modèle (profil voyageur créé automatiquement à l'inscription), mais
    // on ne montre ici que la vue correspondant à son rôle principal,
    // comme demandé.
    public function index(Request $request)
    {
        $user = $request->user();
        $driverProfile = $user->driverProfile;

        if ($driverProfile && $driverProfile->status_verification === 'approved') {
            $transactions = Transaction::whereHas('booking.trip', function ($query) use ($driverProfile) {
                $query->where('driver_profile_id', $driverProfile->id);
            })->with(['booking.trip', 'booking.traveler.user'])->latest()->get();

            return TransactionResource::collection($transactions);
        }

        $travelerProfile = $user->travelerProfile;
        if (! $travelerProfile) {
            return TransactionResource::collection(collect());
        }

        $transactions = Transaction::whereHas('booking', function ($query) use ($travelerProfile) {
            $query->where('traveler_id', $travelerProfile->id);
        })->with('booking.trip')->latest()->get();

        return TransactionResource::collection($transactions);
    }
}
