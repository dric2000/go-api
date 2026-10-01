<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePayoutRequest;
use App\Http\Resources\PayoutResource;
use FedaPay\Payout as FedapayPayout;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $payouts = $request->user()->driverProfile->payouts()->latest()->get();

        return PayoutResource::collection($payouts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePayoutRequest $request)
    {
        $driver = $request->user()->driverProfile;
        $wallet = $driver->wallet;

        try {
            $wallet->retirer($request->amount);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Solde disponible insuffisant.'], 422);
        }

        $payout = $driver->payouts()->create([
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        try {
            $fedapayPayout = FedapayPayout::create([
                'customer' => [
                    'firstname' => $request->user()->name,
                    'lastname' => '-',
                    'email' => $request->user()->email,
                    'phone' => '+229' . $request->phone_number,
                ],
                'currency' => ['iso' => 'XOF'],
                'amount' => $request->amount,
            ]);

            $fedapayPayout->sendNow([
                'phone_number' => ['number' => $request->phone_number, 'country' => 'BJ'],
            ]);

            $payout->update(['provider_reference' => $fedapayPayout->id]);

            return response()->json([
                'message' => 'Retrait initié, en cours de traitement.',
                'payout' => $payout,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Échec initialisation payout Fedapay', [
                'payout_id' => $payout->id,
                'exception' => $e->getMessage(),
            ]);

            $wallet->crediter($request->amount);
            $payout->update(['status' => 'failed']);

            return response()->json(['message' => "Erreur lors de l'initialisation du retrait."], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
