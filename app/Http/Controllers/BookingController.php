<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use FedaPay\Transaction as FedaPayTransaction; 

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $travelerProfile = $request->user()->travelerProfile;

        // Un compte purement conducteur (créé avant l'ajout automatique du
        // profil voyageur à l'inscription, ou sans profil voyageur pour
        // une autre raison) n'a simplement aucune réservation.
        if (! $travelerProfile) {
            return BookingResource::collection(collect());
        }

        $bookings = $travelerProfile->bookings()
            ->with('trip')
            ->latest()
            ->get();

        return BookingResource::collection($bookings);
    }

    // Réservations d'un trajet donné, vues par le conducteur qui l'a publié.
    public function forTrip(Request $request, Trip $trip)
    {
        if ($request->user()->driverProfile->id !== $trip->driver_profile_id) {
            abort(403, "Vous ne pouvez consulter que les réservations de vos propres trajets");
        }

        $bookings = $trip->bookings()
            ->with(['trip', 'traveler.user'])
            ->latest()
            ->get();

        return BookingResource::collection($bookings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateBookingRequest $request, Trip $trip)
    {
        if ($request->user()->id === $trip->driverProfile->user->id) {
            abort(403, "Vous ne pouvez pas réserver votre propre trajet !"); 
        }

        [$booking, $localTransaction] = DB::transaction(function() use ($request, $trip) {
            $trip = Trip::where('id', $trip->id)->lockForUpdate()->first(); 

            if ($trip->status !== 'scheduled') {
                abort(409, "Ce trajet n'accepte plus de réservations"); 
            }

            if ($trip->seats_available < $request->seats_booked) {
                abort(422, "Pas assez de places disponibles.");
            }

            $trip->decrement('seats_available', $request->seats_booked);

            // Certains comptes (créés avant l'ajout automatique du profil
            // voyageur à l'inscription) n'en ont pas : on le crée à la volée
            // plutôt que de planter, un utilisateur doit pouvoir réserver
            // quel que soit l'âge de son compte.
            $travelerProfile = $request->user()->travelerProfile()->firstOrCreate([]);

            $booking = Booking::create([
                'trip_id' => $trip->id,
                'traveler_id' => $travelerProfile->id,
                'seats_booked' => $request->seats_booked,
                'amount_total' => $trip->price_per_seat*$request->seats_booked,
            ]);

            $localTransaction = $booking->transctions()->create([
                'type' => 'payment', 
                'amount' => $booking->amount_total, 
                'provider' => 'fedapay', 
                'status' => 'pending', 
            ]); 

            return [$booking, $localTransaction]; 
        });
        
        try {
            $fedapayTransaction = FedapayTransaction::create([
                'description' => "Réservation de trajet", 
                'amount' => $booking->amount_total,
                'currency' => ['iso' => 'XOF'], 
                // Le navigateur doit revenir sur le site go-web (pas sur
                // l'API) ; on lui passe l'id de réservation pour qu'il
                // puisse afficher le vrai statut, confirmé côté serveur
                // par le webhook ci-dessous — jamais par ce paramètre.
                'callback_url' => config('app.frontend_url') . '/paiement/retour?booking_id=' . $booking->id,
                'customer' => [
                    'firstname' => $request->user()->name, 
                    'lastname' => '-',
                    'email' => $request->user()->email, 
                    'phone' => '+229' . $request->user()->phone, 
                ]
            ]); 

            $token = $fedapayTransaction->generateToken(); 

            $localTransaction->update(['provider_reference' => $fedapayTransaction->id]); 

            return response()->json([
                'message' => 'Réservation effectuée, en attente de paiement.',
                'booking' => $booking, 
                'payment_url' => $token->url,
            ], 201); 

        } catch (\Exception $e) {
            $trip->increment('seats_available', $request->seats_booked);
            $booking->update(['statut_validation' => 'canceled']); 
            $localTransaction->update(['status' => 'failed']); 

            return response()->json([
                'message' => 'Erreur lors du paiement'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Booking $booking)
    {
        if ($request->user()->travelerProfile->id !== $booking->traveler_id) {
            abort(403, "Vous ne pouvez consulter que vos propres réservations");
        }

        return new BookingResource($booking->load('trip'));
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
