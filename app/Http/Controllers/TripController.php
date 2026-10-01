<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTripRequest;
use App\Http\Resources\TripResource;
use App\Jobs\ReleaseBookingFundsJob;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use AuthorizesRequests, ValidatesRequests;

    // Trajets ouverts à la réservation (page publique "Trajets"), avec
    // filtres optionnels ville de départ / arrivée / date.
    public function index(Request $request)
    {
        $trips = Trip::query()
            ->with(['driverProfile.user', 'vehicle'])
            ->where('status', 'scheduled')
            ->where('seats_available', '>', 0)
            ->where('departure_at', '>', now())
            ->when($request->filled('from'), fn ($q) => $q->where('departure_city', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->where('arrival_city', $request->to))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('departure_at', $request->date))
            ->orderBy('departure_at')
            ->get();

        return TripResource::collection($trips);
    }

    // Trajets créés par le conducteur connecté (tous statuts confondus).
    public function myTrips(Request $request)
    {
        $trips = $request->user()->driverProfile->trips()
            ->latest()
            ->get();

        return TripResource::collection($trips);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTripRequest $request)
    {
        $vehicle = Vehicle::find($request->vehicle_id); 

        if ($request->user()->driverProfile->id !== $vehicle->driverProfile->id) {
            return response()->json([
                'message'=> "Vous ne pouvez pas créer un trajet avec un véhicule qui ne vous appartient pas"
            ], 403); 
        }

        $trip = Trip::create([
            'driver_profile_id' => $request->user()->driverProfile->id,
            'vehicle_id' => $request->vehicle_id,
            'departure_city' => $request->departure_city,
            'arrival_city' => $request->arrival_city,
            'pickup_point' => $request->pickup_point,
            'departure_at' => $request->departure_at,
            'seats_total' => $request->seats_total,
            'seats_available' => $request->seats_available,
            'price_per_seat' => $request->price_per_seat,
        ]);

        $trip->refresh();

        return response()->json([
            'message' => "Votre trajet a été créé !", 
            'trajet' => new TripResource($trip)
        ]); 
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        $trip->load(['driverProfile.user', 'vehicle']);

        return new TripResource($trip);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    // demarrerCourse() du diagramme : passe le trajet a in_progress.
    // C'est cet etat que le (futur) flow de reservation devra verifier pour bloquer
    // toute nouvelle reservation une fois la course commencee.
    public function start(Trip $trip)
    {
        $this->authorize('update', $trip);

        if ($trip->status !== 'scheduled') {
            return response()->json([
                'message' => "Seul un trajet ouvert peut être démarré."
            ], 409);
        }

        $trip->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => "Le trajet a démarré.",
            'trajet' => new TripResource($trip)
        ]);
    }

    // marquerArrivee() du diagramme : ne peut se faire qu'apres un demarrage effectif.
    public function complete(Trip $trip)
    {
        $this->authorize('update', $trip);

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'message' => "Seul un trajet en cours peut être marqué comme terminé."
            ], 409);
        }

        $trip->update(['status' => 'completed']);

        $bookings = $trip->bookings()->where('statut_validation', 'paid')->get(); 

        foreach($bookings as $booking) {
            $booking->update(['driver_arrival_confirmed_at' =>now()]);
            ReleaseBookingFundsJob::dispatch($booking)->delay(now()->addHours(24)); 
        }

        return response()->json([
            'message' => "Le trajet est terminé.",
            'trajet' => new TripResource($trip)
        ]);
    }

    //Confirmation par le voyageur que la course a été réalisée 
    public function confirmArrival(Request $request, Booking $booking) {
        if ($request->user()->travelerProfile->id !== $booking->traveler_id) {
            abort(403, "Vous ne pouvez confirmer que vos propres réservations"); 
        }

        if ($booking->statut_validation !== 'paid') {
            return response()->json([
                'message' => "Cette réservation n'a pas été payée. Elle ne peut pas être confirmée."
            ], 409); 
        }

        $booking->update(['traveler_confirmed_at' => now()]);

        if ($booking->driver_arrival_confirmed_at && ! $booking->funds_released_at) {
            $wallet = $booking->trip->driverProfile->wallet; 
            $wallet->debloquer($booking->amount_total); 
            $booking->update(['funds_released_at' => now()]); 
        }

        return response()->json([
            'message' => "Arrivée confirmée !", 
            'booking' => $booking
        ]); 
    } 

    // annulerTrajet() du diagramme : possible tant que la course n'est pas deja terminee/annulee.
    // Pas de gestion des remboursements/notifications ici : le flow de reservation n'existe pas encore.
    public function cancel(Trip $trip)
    {
        $this->authorize('update', $trip);

        if (in_array($trip->status, ['completed', 'canceled'])) {
            return response()->json([
                'message' => "Un trajet déjà terminé ou annulé ne peut plus être annulé."
            ], 409);
        }

        $trip->update(['status' => 'canceled']);

        return response()->json([
            'message' => "Le trajet a été annulé.",
            'trajet' => new TripResource($trip)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // Reçoit la position courante du conducteur pendant un trajet en cours.
    // Écrase la dernière position connue (pas d'historique) : voir la
    // décision de conserver ça simple plutôt que de logger chaque ping.
    public function updatePosition(Request $request, Trip $trip)
    {
        $this->authorize('update', $trip);

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'message' => "La position ne peut être mise à jour que pour un trajet en cours."
            ], 409);
        }

        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $trip->update([
            'current_lat' => $request->lat,
            'current_lng' => $request->lng,
            'position_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Position mise à jour.']);
    }

    // Dernière position connue, consultée par la page web pendant le suivi.
    public function position(Trip $trip)
    {
        return response()->json([
            'lat' => $trip->current_lat,
            'lng' => $trip->current_lng,
            'updated_at' => $trip->position_updated_at,
        ]);
    }
}
