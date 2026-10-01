<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReleaseBookingFundsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Booking $booking)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->booking->funds_released_at) {
            return; 
        }

        if ($this->booking->disputes()->where('status', 'open')->exists()) {
            return; 
        }

        $wallet = $this->booking->trip->driverProfile->wallet; 
        $wallet->debloquer($this->booking->amount_total); 

        $this->booking->update(['funds_released_at' => now()]); 
    }
}
