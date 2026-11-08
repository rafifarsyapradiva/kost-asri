<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\PaymentStatus;
use App\Enums\RentType;
use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTransactionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Demo Users
        $tenantUser = User::firstOrNew(['email' => 'penyewa@example.com']);
        $tenantUser->forceFill([
            'name' => 'Budi Santoso',
            'phone' => '6281234567891',
            'role' => UserRole::Tenant,
            'email_verified_at' => now(),
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ])->save();

        $prospectUser = User::firstOrNew(['email' => 'calon@example.com']);
        $prospectUser->forceFill([
            'name' => 'Siti Rahma',
            'phone' => '6281234567892',
            'role' => UserRole::Prospect,
            'email_verified_at' => now(),
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ])->save();

        // 2. Room 101 -> Occupied & Paid (Lease aktif)
        $room101 = Room::where('code', '101')->first();
        if ($room101 !== null) {
            $room101->update(['status' => RoomStatus::Occupied]);

            $rsv101 = Reservation::updateOrCreate(
                ['code' => 'RSV-' . now()->format('Ymd') . '-0101'],
                [
                    'user_id' => $tenantUser->id,
                    'room_id' => $room101->id,
                    'rent_type' => RentType::Monthly,
                    'payment_scheme' => PaymentScheme::Full,
                    'preferred_method' => 'midtrans',
                    'source' => ReservationSource::Online,
                    'check_in_date' => now()->startOfMonth()->toDateString(),
                    'check_out_date' => now()->endOfMonth()->toDateString(),
                    'duration_qty' => 1,
                    'rate_snapshot' => 900_000,
                    'total_amount' => 900_000,
                    'dp_amount' => 900_000,
                    'initial_billed_until' => now()->endOfMonth()->toDateString(),
                    'status' => ReservationStatus::CheckedIn,
                ]
            );

            $lease101 = Lease::updateOrCreate(
                ['reservation_id' => $rsv101->id],
                [
                    'user_id' => $tenantUser->id,
                    'room_id' => $room101->id,
                    'rent_type' => RentType::Monthly,
                    'daily_rate' => 60_000,
                    'weekly_rate' => 300_000,
                    'monthly_rate' => 900_000,
                    'start_date' => now()->startOfMonth()->toDateString(),
                    'billed_until' => now()->endOfMonth()->toDateString(),
                    'paid_until' => now()->endOfMonth()->toDateString(),
                    'cycle_count' => 1,
                    'auto_renew' => true,
                    'status' => LeaseStatus::Active,
                    'checked_in_at' => now()->startOfMonth(),
                ]
            );

            $inv101 = Invoice::updateOrCreate(
                ['number' => 'INV/ASRI/' . now()->format('Y/m') . '/0101'],
                [
                    'reservation_id' => $rsv101->id,
                    'lease_id' => $lease101->id,
                    'user_id' => $tenantUser->id,
                    'type' => InvoiceType::Monthly,
                    'cycle_index' => 1,
                    'period_start' => now()->startOfMonth()->toDateString(),
                    'period_end' => now()->endOfMonth()->toDateString(),
                    'issue_date' => now()->startOfMonth()->toDateString(),
                    'due_date' => now()->startOfMonth()->addDays(9)->toDateString(),
                    'subtotal' => 900_000,
                    'total' => 900_000,
                    'minimum_pay_amount' => 900_000,
                    'paid_amount' => 900_000,
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => now()->startOfMonth()->addDay(),
                ]
            );

            InvoiceItem::firstOrCreate(
                ['invoice_id' => $inv101->id, 'type' => InvoiceItemType::Rent],
                [
                    'description' => 'Sewa Bulan ' . now()->translatedFormat('F Y'),
                    'qty' => 1,
                    'unit_amount' => 900_000,
                    'amount' => 900_000,
                ]
            );

            $pay101 = Payment::firstOrCreate(
                ['invoice_id' => $inv101->id],
                [
                    'user_id' => $tenantUser->id,
                    'method' => PaymentMethod::Midtrans,
                    'order_id' => 'ORDER-DEMO-101',
                    'payment_channel' => 'bca_va',
                    'amount' => 900_000,
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now()->startOfMonth()->addDay(),
                ]
            );

            Receipt::firstOrCreate(
                ['payment_id' => $pay101->id],
                [
                    'number' => 'KWT/ASRI/' . now()->format('Y/m') . '/0101',
                    'invoice_id' => $inv101->id,
                    'user_id' => $tenantUser->id,
                    'amount' => 900_000,
                    'issued_at' => now()->startOfMonth()->addDay(),
                ]
            );
        }

        // 3. Room 102 -> Booked (DP Paid)
        $room102 = Room::where('code', '102')->first();
        if ($room102 !== null) {
            $room102->update(['status' => RoomStatus::Booked]);

            Reservation::updateOrCreate(
                ['code' => 'RSV-' . now()->format('Ymd') . '-0102'],
                [
                    'user_id' => $prospectUser->id,
                    'room_id' => $room102->id,
                    'rent_type' => RentType::Monthly,
                    'payment_scheme' => PaymentScheme::Dp,
                    'preferred_method' => 'midtrans',
                    'source' => ReservationSource::Online,
                    'check_in_date' => now()->addDays(3)->toDateString(),
                    'check_out_date' => now()->addMonth()->toDateString(),
                    'duration_qty' => 1,
                    'rate_snapshot' => 900_000,
                    'total_amount' => 900_000,
                    'dp_amount' => 270_000,
                    'initial_billed_until' => now()->addMonth()->toDateString(),
                    'status' => ReservationStatus::DpPaid,
                    'expires_at' => now()->addHours(20),
                ]
            );
        }

        // 4. Room 103 -> Overdue (Terisi menunggak)
        $room103 = Room::where('code', '103')->first();
        if ($room103 !== null) {
            $room103->update(['status' => RoomStatus::Occupied]);

            $rsv103 = Reservation::updateOrCreate(
                ['code' => 'RSV-' . now()->format('Ymd') . '-0103'],
                [
                    'user_id' => $tenantUser->id,
                    'room_id' => $room103->id,
                    'rent_type' => RentType::Monthly,
                    'payment_scheme' => PaymentScheme::Full,
                    'preferred_method' => 'cash',
                    'source' => ReservationSource::Online,
                    'check_in_date' => now()->subMonth()->toDateString(),
                    'check_out_date' => now()->toDateString(),
                    'duration_qty' => 1,
                    'rate_snapshot' => 900_000,
                    'total_amount' => 900_000,
                    'dp_amount' => 900_000,
                    'initial_billed_until' => now()->toDateString(),
                    'status' => ReservationStatus::CheckedIn,
                ]
            );

            $lease103 = Lease::updateOrCreate(
                ['reservation_id' => $rsv103->id],
                [
                    'user_id' => $tenantUser->id,
                    'room_id' => $room103->id,
                    'rent_type' => RentType::Monthly,
                    'daily_rate' => 60_000,
                    'weekly_rate' => 300_000,
                    'monthly_rate' => 900_000,
                    'start_date' => now()->subMonth()->toDateString(),
                    'billed_until' => now()->endOfMonth()->toDateString(),
                    'paid_until' => now()->subMonth()->endOfMonth()->toDateString(),
                    'cycle_count' => 2,
                    'auto_renew' => true,
                    'status' => LeaseStatus::Active,
                    'checked_in_at' => now()->subMonth(),
                ]
            );

            Invoice::updateOrCreate(
                ['number' => 'INV/ASRI/' . now()->format('Y/m') . '/0103'],
                [
                    'reservation_id' => $rsv103->id,
                    'lease_id' => $lease103->id,
                    'user_id' => $tenantUser->id,
                    'type' => InvoiceType::Monthly,
                    'cycle_index' => 2,
                    'period_start' => now()->startOfMonth()->toDateString(),
                    'period_end' => now()->endOfMonth()->toDateString(),
                    'issue_date' => now()->startOfMonth()->toDateString(),
                    'due_date' => now()->subDays(2)->toDateString(),
                    'subtotal' => 900_000,
                    'total' => 900_000,
                    'minimum_pay_amount' => 900_000,
                    'paid_amount' => 0,
                    'status' => InvoiceStatus::Overdue,
                ]
            );
        }

        // 5. Room 104 -> Maintenance
        $room104 = Room::where('code', '104')->first();
        if ($room104 !== null) {
            $room104->update([
                'status' => RoomStatus::Maintenance,
                'maintenance_note' => 'Perbaikan instalasi shower dan pengecatan ulang dinding',
            ]);
        }
    }
}
