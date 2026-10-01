<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    // seul un admin resout un litige (comme IdentityDocumentPolicy/VehiclePolicy)
    public function resolve(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin();
    }
}
