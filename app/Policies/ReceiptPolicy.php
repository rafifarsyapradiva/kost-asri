<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    public function view(User $user, Receipt $receipt): bool
    {
        return $user->isAdmin() || $receipt->user_id === $user->id;
    }

    public function pdf(User $user, Receipt $receipt): bool
    {
        return $this->view($user, $receipt);
    }
}
