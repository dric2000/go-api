<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReviewRequest;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateReviewRequest $request, Booking $booking)
    {
        if ($request->user()->travelerProfile->id !== $booking->traveler_id) {
            abort(403, "Vous ne pouvez pas laisser un avis sur un trajet que vous n'avez pas réservé.");
        }

        if (!$booking->funds_released_at) {
            return response()->json([
                'message' => "Ce trajet n'est pas confirmé, vous ne pouvez pas laisser d'avis.", 
            ], 409); 
        }

        if ($booking->reviews()->exists()) {
            return response()->json([
                'message' => "Vous avez déjà laissé un avis pour cette réservation."
            ], 409);
        }

        $review = Review::create([
            'driver_profile_id' => $booking->trip->driver_profile_id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return response()->json([
            'message' => 'Avis publié.',
            'review' => $review,
        ], 201);
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
