<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Setting\SettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {
    }

    public function index(Request $request): Response
    {
        $now = Carbon::now('Asia/Jakarta');
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // 32 Rooms aggregation
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $availableRooms = Room::where('status', 'available')->count();
        $bookedRooms = Room::where('status', 'booked')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0.0;

        // MTD Revenue
        $mtdPayments = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->get();

        $revenueMtdTotal = (int) $mtdPayments->sum('amount');
        $revenueMtdCash = (int) $mtdPayments->where('payment_method', 'cash')->sum('amount');
        $revenueMtdMidtrans = (int) $mtdPayments->where('payment_method', '!=', 'cash')->sum('amount');

        // Arrears total
        $arrearsInvoices = Invoice::whereIn('status', ['overdue', 'unpaid', 'partial'])->get();
        $arrearsTotal = (int) $arrearsInvoices->sum(fn ($inv) => max(0, (int) $inv->total_amount - (int) $inv->paid_amount));

        // Today's Check-ins & Check-outs
        $todayCheckIns = Reservation::with(['user', 'room.roomType'])
            ->whereDate('check_in_date', $now->toDateString())
            ->whereIn('status', ['paid', 'dp_paid', 'confirmed'])
            ->get();

        $todayCheckOuts = Lease::with(['tenant', 'room.roomType'])
            ->whereDate('planned_checkout_date', $now->toDateString())
            ->where('status', 'active')
            ->get();

        // Upcoming due invoices
        $upcomingDueInvoices = Invoice::with(['tenant', 'room'])
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->where('due_date', '<=', $now->copy()->addDays(3)->toDateString())
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // Recent Reservations
        $recentReservations = Reservation::with(['user', 'room.roomType'])
            ->latest()
            ->limit(5)
            ->get();

        // Cron health indicator
        $cronHealth = [
            'generate_monthly' => setting('cron_last_run.generate_monthly'),
            'generate_weekly' => setting('cron_last_run.generate_weekly'),
            'apply_penalties' => setting('cron_last_run.apply_penalties'),
            'notify_reminders' => setting('cron_last_run.notify_reminders'),
            'expire_reservations' => setting('cron_last_run.expire_reservations'),
            'checkout_reminder' => setting('cron_last_run.checkout_reminder'),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'available_rooms' => $availableRooms,
                'booked_rooms' => $bookedRooms,
                'maintenance_rooms' => $maintenanceRooms,
                'occupancy_rate' => $occupancyRate,
                'revenue_mtd' => [
                    'total' => $revenueMtdTotal,
                    'cash' => $revenueMtdCash,
                    'midtrans' => $revenueMtdMidtrans,
                ],
                'arrears_total' => $arrearsTotal,
            ],
            'today_schedule' => [
                'check_ins' => $todayCheckIns,
                'check_outs' => $todayCheckOuts,
            ],
            'upcoming_due_invoices' => $upcomingDueInvoices,
            'recent_reservations' => $recentReservations,
            'cron_health' => $cronHealth,
        ]);
    }
}
