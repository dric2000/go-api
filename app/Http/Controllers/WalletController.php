<?php

namespace App\Http\Controllers;

use App\Http\Resources\WalletResource;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    // Solde du conducteur connecté (fonds bloqués + disponibles).
    public function show(Request $request)
    {
        return new WalletResource($request->user()->driverProfile->wallet);
    }
}
