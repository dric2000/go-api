<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Transaction as PaymentTransaction;
use FedaPay\Error\SignatureVerification;
use FedaPay\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FedapayController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('X-FEDAPAY-SIGNATURE');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.fedapay.webhook_secret')
            );
        } catch (\UnexpectedValueException | SignatureVerification $e) {
            return response()->json(['message' => 'Webhook invalide'], 400);
        }

        if (str_starts_with($event->name, 'payout.')) {
            $this->handlePayoutEvent($event);

            return response()->json(['message' => 'ok'], 200);
        }

        $localTransation = PaymentTransaction::where('provider_reference', $event->entity->id)->first();

        if (! $localTransation || $localTransation->status !== 'pending') {
            return response()->json(['message' => 'ok'], 200);
        }

        if ($event->name === 'transaction.approved') {
            $this->handleApproved($localTransation);
        } elseif ($event->name === 'transaction.declined') {
            $this->handleDeclined($localTransation);
        }

        return response()->json(['message' => 'ok'], 200);
    }

    private function handleApproved(PaymentTransaction $transaction): void 
    {
        $transaction->update(['status' => 'success']); 

        $booking = $transaction->booking; 
        $booking->update(['statut_validation' => 'paid', 'paid_at' => now()]); 

        $wallet = $booking->trip->driverProfile->wallet; 
        $wallet->increment('solde_bloque', $transaction->amount); 
    }

    private function handleDeclined(PaymentTransaction $transaction): void
    {
        $transaction->update(['status' => 'failed']);

        $booking = $transaction->booking;
        $booking->update(['statut_validation' => 'canceled']);
        $booking->trip->increment('seats_available', $booking->seats_booked);
    }

    // Le nom exact de l'événement et les valeurs de statut Fedapay pour un
    // payout n'ont pas encore été vérifiés contre un vrai webhook (aucun
    // payout n'a encore abouti en sandbox) : on logge systématiquement le
    // payload pour confirmer/ajuster ça lors du premier test réel avec
    // ngrok, plutôt que de deviner silencieusement.
    private function handlePayoutEvent(object $event): void
    {
        $payout = Payout::where('provider_reference', $event->entity->id)->first();

        Log::info('Webhook payout Fedapay reçu', [
            'event' => $event->name,
            'payout_found' => (bool) $payout,
            'entity' => (array) $event->entity,
        ]);

        if (! $payout || $payout->status !== 'pending') {
            return;
        }

        $status = $event->entity->status ?? null;

        if (in_array($status, ['transferred', 'success'], true)) {
            $payout->update(['status' => 'success']);
            return;
        }

        if (in_array($status, ['failed', 'declined'], true)) {
            $payout->update(['status' => 'failed']);
            $payout->driverProfile->wallet->crediter($payout->amount);
        }
    }
}
