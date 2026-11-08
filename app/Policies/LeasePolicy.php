<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lease;
use App\Models\User;

class LeasePolicy
{
    public function view(User $user, Lease $lease): bool
    {
        return $user->isAdmin() || $lease->user_id === $user->id;
    }

    public function manage(User $user, Lease $lease): bool
    {
        return $user->isAdmin();
    }
}
