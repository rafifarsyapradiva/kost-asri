<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() || $invoice->user_id === $user->id;
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        $allowedStatus = ['unpaid', 'partial', 'overdue'];
        $statusValue = is_string($invoice->status) ? $invoice->status : $invoice->status->value;

        return ($user->isAdmin() || $invoice->user_id === $user->id) && in_array($statusValue, $allowedStatus, true);
    }

    public function pdf(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin();
    }
}
