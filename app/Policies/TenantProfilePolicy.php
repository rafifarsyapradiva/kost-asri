<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantProfile;
use App\Models\User;

class TenantProfilePolicy
{
    public function view(User $user, TenantProfile $profile): bool
    {
        return $user->isAdmin() || $profile->user_id === $user->id;
    }

    public function update(User $user, TenantProfile $profile): bool
    {
        return $user->isAdmin() || $profile->user_id === $user->id;
    }

    public function viewKtp(User $user, TenantProfile $profile): bool
    {
        return $user->isAdmin() || $profile->user_id === $user->id;
    }
}
