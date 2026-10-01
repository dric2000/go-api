<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traveler_id')->constrained('traveler_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('seats_booked');
            $table->enum('statut_validation', ['pending_payment', 'paid', 'confirmed', 'disputed', 'refunded', 'canceled'])->default('pending_payment');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('driver_arrival_confirmed_at')->nullable();
            $table->timestamp('traveler_confirmed_at')->nullable();
            $table->timestamp('funds_released_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
