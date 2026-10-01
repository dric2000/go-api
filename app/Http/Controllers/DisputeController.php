<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDisputeRequest;
use App\Http\Requests\ResolveDisputeRequest;
use App\Models\Booking;
use App\Models\Dispute;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DisputeController extends Controller
{
    use AuthorizesRequests;

    // signalerLitige() du diagramme : le voyageur ouvre un litige sur sa propre reservation
    public function store(CreateDisputeRequest $request, Booking $booking)
    {
        if ($request->user()->travelerProfile->id !== $booking->traveler_id) {
            abort(403, "Vous ne pouvez signaler un litige que sur vos propres réservations.");
        }

        // au-dela de ce point les fonds sont deja chez le chauffeur : un litige ici
        // n'a plus d'effet bloquant possible via ReleaseBookingFundsJob
        if ($booking->funds_released_at) {
            return response()->json([
                'message' => "Les fonds de cette réservation ont déjà été débloqués, impossible d'ouvrir un litige."
            ], 409);
        }

        if ($booking->disputes()->where('status', 'open')->exists()) {
            return response()->json([
                'message' => "Un litige est déjà ouvert pour cette réservation."
            ], 409);
        }

        $dispute = $booking->disputes()->create([
            'raised_by' => $request->user()->id,
            'reason' => $request->reason,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Litige signalé.',
            'dispute' => $dispute,
        ], 201);
    }

    // resoudre() du diagramme : action admin uniquement (DisputePolicy)
    public function resolve(ResolveDisputeRequest $request, Dispute $dispute)
    {
        $this->authorize('resolve', $dispute);

        if ($dispute->status !== 'open') {
            return response()->json(['message' => 'Ce litige a déjà été résolu.'], 409);
        }

        $booking = $dispute->booking;

        if ($request->action === 'release') {
            // on donne raison au chauffeur : meme logique que ReleaseBookingFundsJob/confirmArrival
            $wallet = $booking->trip->driverProfile->wallet;
            $wallet->debloquer($booking->amount_total);
            $booking->update(['funds_released_at' => now()]);

            $dispute->update([
                'status' => 'resolved',
                'resolution_note' => $request->resolution_note,
            ]);

            return response()->json([
                'message' => 'Litige résolu en faveur du chauffeur, fonds débloqués.',
                'dispute' => $dispute,
            ]);
        }

        // action === 'refund' : on donne raison au voyageur.
        // L'argent sort de solde_bloque sans jamais passer par solde_disponible : il ne
        // deviendra jamais la propriete du chauffeur, il repart chez le voyageur.
        // Pas d'API de remboursement chez Fedapay (confirme via leur doc) : le remboursement
        // reel se fait a la main dans leur dashboard. On garde une trace locale (Transaction
        // type=refund, status=pending) et on renvoie la reference a l'admin pour qu'il sache
        // laquelle rembourser manuellement.
        $wallet = $booking->trip->driverProfile->wallet;
        $wallet->annulerBlocage($booking->amount_total);

        $booking->update(['statut_validation' => 'refunded']);

        $originalTransaction = $booking->transctions()
            ->where('type', 'payment')
            ->where('status', 'success')
            ->first();

        $refundTransaction = $booking->transctions()->create([
            'type' => 'refund',
            'amount' => $booking->amount_total,
            'provider' => 'fedapay',
            'status' => 'pending',
        ]);

        $dispute->update([
            'status' => 'resolved',
            'resolution_note' => $request->resolution_note,
        ]);

        return response()->json([
            'message' => 'Litige résolu en faveur du voyageur. Remboursement à effectuer manuellement dans le dashboard Fedapay (MTN Mobile Money uniquement).',
            'dispute' => $dispute,
            'refund_transaction_id' => $refundTransaction->id,
            'fedapay_provider_reference_a_rembourser' => $originalTransaction->provider_reference ?? null,
        ]);
    }
}
