<?php

declare(strict_types=1);

namespace App\Actions\Stay;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Models\Lease;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StopLeaseAction
{
    public function __construct(
        private readonly ActivityLogger $logger
    ) {
    }

    /**
     * Stop weekly lease renewal before check-out day (BR-10, BE-034).
     *
     * @throws ValidationException
     */
    public function stopWeekly(Lease $lease, ?User $actor = null): Lease
    {
        if ($lease->rent_type !== RentType::Weekly) {
            throw ValidationException::withMessages([
                'rent_type' => 'Aksi ini hanya berlaku untuk sewa mingguan.',
            ]);
        }

        return DB::transaction(function () use ($lease, $actor): Lease {
            /** @var Lease $lockedLease */
            $lockedLease = Lease::where('id', $lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Void any unpaid renewal weekly invoices issued ahead of time
            $renewalInvoices = $lockedLease->invoices()
                ->where('type', InvoiceType::Weekly)
                ->where('status', InvoiceStatus::Unpaid)
                ->get();

            foreach ($renewalInvoices as $invoice) {
                $invoice->update(['status' => InvoiceStatus::Void]);
            }

            // Planned checkout is end of paid/billed period
            $checkoutDate = $lockedLease->paid_until ?? $lockedLease->billed_until;

            $lockedLease->update([
                'auto_renew' => false,
                'status' => LeaseStatus::Ending,
                'planned_checkout_date' => $checkoutDate,
            ]);

            $this->logger->log(
                action: 'stay.stop_weekly',
                auditable: $lockedLease,
                description: "Sewa mingguan dihentikan perpanjangannya untuk kamar {$lockedLease->room->room_number}",
                actorId: $actor?->id,
            );

            return $lockedLease;
        });
    }

    /**
     * Request or confirm monthly lease termination (BR-33, BE-034).
     *
     * @throws ValidationException
     */
    public function requestStopMonthly(Lease $lease, ?User $actor = null, ?string $reason = null): Lease
    {
        if ($lease->rent_type !== RentType::Monthly) {
            throw ValidationException::withMessages([
                'rent_type' => 'Pengajuan berhenti sewa bulanan hanya berlaku untuk sewa tipe bulanan.',
            ]);
        }

        return DB::transaction(function () use ($lease, $actor, $reason): Lease {
            /** @var Lease $lockedLease */
            $lockedLease = Lease::where('id', $lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            // End date is end of current billed month
            $checkoutDate = $lockedLease->billed_until
                ? Carbon::parse($lockedLease->billed_until)->endOfMonth()
                : now()->endOfMonth();

            $lockedLease->update([
                'auto_renew' => false,
                'status' => LeaseStatus::Ending,
                'planned_checkout_date' => $checkoutDate->toDateString(),
                'checkout_notes' => $reason ?? $lockedLease->checkout_notes,
            ]);

            $this->logger->log(
                action: 'stay.stop_monthly',
                auditable: $lockedLease,
                description: "Pengajuan berhenti sewa bulanan dicatat untuk kamar {$lockedLease->room->room_number}",
                actorId: $actor?->id,
            );

            return $lockedLease;
        });
    }
}
