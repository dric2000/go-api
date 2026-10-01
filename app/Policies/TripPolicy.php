<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function update(User $user, Trip $trip): bool
    {
        return $user->id === $trip->driverProfile->user_id;
    }
}
