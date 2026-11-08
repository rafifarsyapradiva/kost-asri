<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString());
        $method = $request->input('method');

        // Revenue query
        $revenueQuery = Payment::with(['invoice.user', 'invoice.lease.room.roomType', 'invoice.reservation.room.roomType'])
            ->where('status', 'paid')
            ->whereDate('paid_at', '>=', $dateFrom)
            ->whereDate('paid_at', '<=', $dateTo);

        if ($method) {
            $revenueQuery->where('method', $method);
        }

        $payments = $revenueQuery->latest('paid_at')->get();
        $totalRevenue = (int) $payments->sum('amount');

        // Arrears query
        $arrearsInvoices = Invoice::with(['user', 'lease.room.roomType', 'reservation.room.roomType'])
            ->whereIn('status', ['overdue', 'unpaid', 'partial'])
            ->orderBy('due_date')
            ->get();

        $totalArrears = (int) $arrearsInvoices->sum(fn ($inv) => max(0, (int) $inv->total - (int) $inv->paid_amount));

        // Occupancy stats
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0.0;

        $roomClasses = RoomType::withCount(['rooms as total_rooms', 'rooms as occupied_rooms' => function ($q): void {
            $q->where('status', 'occupied');
        }])->get();

        return Inertia::render('Admin/Reports/Index', [
            'revenue' => [
                'payments' => $payments,
                'total' => $totalRevenue,
            ],
            'arrears' => [
                'invoices' => $arrearsInvoices,
                'total' => $totalArrears,
            ],
            'occupancy' => [
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'occupancy_rate' => $occupancyRate,
                'by_class' => $roomClasses,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'method' => $method,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $type = $request->input('type', 'revenue');
        $filename = "laporan-{$type}-" . date('Ymd-His') . ".csv";

        $dateFrom = $request->input('date_from', Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now('Asia/Jakarta')->endOfMonth()->toDateString());

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($type, $dateFrom, $dateTo): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            if ($type === 'revenue') {
                fputcsv($handle, ['Tanggal', 'No. Invoice', 'Penghuni', 'Kamar', 'Metode', 'Nominal (Rp)']);

                $payments = Payment::with(['invoice.user', 'invoice.lease.room', 'invoice.reservation.room'])
                    ->where('status', 'paid')
                    ->whereDate('paid_at', '>=', $dateFrom)
                    ->whereDate('paid_at', '<=', $dateTo)
                    ->latest('paid_at')
                    ->get();

                foreach ($payments as $p) {
                    $roomCode = $p->invoice?->lease?->room?->code ?? $p->invoice?->reservation?->room?->code ?? '-';
                    fputcsv($handle, [
                        $p->paid_at?->format('Y-m-d H:i') ?? '-',
                        $p->invoice?->number ?? '-',
                        $p->invoice?->user?->name ?? ($p->user?->name ?? '-'),
                        $roomCode,
                        $p->method->value ?? (string) $p->method,
                        $p->amount,
                    ]);
                }
            } else {
                fputcsv($handle, ['No. Invoice', 'Penghuni', 'Kamar', 'Jatuh Tempo', 'Total (Rp)', 'Dibayar (Rp)', 'Sisa Tunggakan (Rp)', 'Status']);

                $invoices = Invoice::with(['user', 'lease.room', 'reservation.room'])
                    ->whereIn('status', ['overdue', 'unpaid', 'partial'])
                    ->orderBy('due_date')
                    ->get();

                foreach ($invoices as $inv) {
                    $roomCode = $inv->lease?->room?->code ?? $inv->reservation?->room?->code ?? '-';
                    fputcsv($handle, [
                        $inv->number,
                        $inv->user?->name ?? '-',
                        $roomCode,
                        $inv->due_date?->format('Y-m-d') ?? '-',
                        $inv->total,
                        $inv->paid_amount,
                        max(0, $inv->total - $inv->paid_amount),
                        $inv->status->value ?? (string) $inv->status,
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
