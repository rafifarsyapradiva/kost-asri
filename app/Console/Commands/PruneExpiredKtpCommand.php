<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\ReservationStatus;
use App\Models\TenantProfile;
use App\Services\Audit\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneExpiredKtpCommand extends Command
{
    protected $signature = 'privacy:prune-expired-ktp';

    protected $description = 'Hapus foto KTP alumni kost > 1 tahun pasca check-out tanpa tunggakan (SEC-013)';

    public function handle(ActivityLogger $logger): int
    {
        $cutoffDate = now()->subYear();

        $profiles = TenantProfile::whereNotNull('ktp_photo_path')
            ->with(['user.leases', 'user.invoices', 'user.reservations'])
            ->get();

        $prunedCount = 0;

        foreach ($profiles as $profile) {
            $user = $profile->user;
            if (! $user) {
                continue;
            }

            // Must have had at least one lease
            if ($user->leases->isEmpty()) {
                continue;
            }

            // All leases must be ended AND checked out before cutoff date
            $hasActiveOrRecentLease = $user->leases->contains(function ($lease) use ($cutoffDate) {
                if ($lease->status !== LeaseStatus::Ended) {
                    return true;
                }

                if (! $lease->checked_out_at || $lease->checked_out_at->gt($cutoffDate)) {
                    return true;
                }

                return false;
            });

            if ($hasActiveOrRecentLease) {
                continue;
            }

            // Must not have active or upcoming reservations
            $hasActiveReservation = $user->reservations->contains(function ($res) {
                return in_array($res->status, [
                    ReservationStatus::PendingPayment,
                    ReservationStatus::DpPaid,
                    ReservationStatus::Paid,
                    ReservationStatus::CheckedIn,
                ], true);
            });

            if ($hasActiveReservation) {
                continue;
            }

            // Must not have unpaid or overdue invoices
            $hasUnpaidInvoices = $user->invoices->contains(function ($inv) {
                return in_array($inv->status, [
                    InvoiceStatus::Unpaid,
                    InvoiceStatus::Partial,
                    InvoiceStatus::Overdue,
                ], true);
            });

            if ($hasUnpaidInvoices) {
                continue;
            }

            // Eligible for pruning
            $ktpPath = $profile->ktp_photo_path;
            if ($ktpPath && Storage::disk('private')->exists($ktpPath)) {
                Storage::disk('private')->delete($ktpPath);
            }

            $profile->forceFill(['ktp_photo_path' => null])->save();

            $logger->log(
                action: 'privacy.prune_ktp',
                auditable: $profile,
                description: "Foto KTP alumni {$user->name} dihapus otomatis sesuai batas retensi privasi UU PDP (SEC-013)"
            );

            $prunedCount++;
        }

        $this->info("{$prunedCount} foto KTP alumni berhasil dihapus.");

        return self::SUCCESS;
    }
}
