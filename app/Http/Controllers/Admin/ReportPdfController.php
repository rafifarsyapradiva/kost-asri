<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Room;
use App\Services\Pdf\PdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportPdfController extends Controller
{
    public function __invoke(Request $request, PdfService $pdfService): Response
    {
        $monthStr = $request->query('month', now()->format('Y-m'));
        $date = Carbon::parse($monthStr . '-01');

        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', RoomStatus::Occupied)->count();
        $occupancyRate = $totalRooms > 0 ? ($occupiedRooms / $totalRooms) * 100 : 0.0;

        $paidInvoices = Invoice::with(['user', 'reservation.room', 'lease.room'])
            ->whereYear('paid_at', $date->year)
            ->whereMonth('paid_at', $date->month)
            ->where('status', InvoiceStatus::Paid)
            ->get();

        $totalRevenue = (int) $paidInvoices->sum('total');

        $overdueCount = Invoice::where('status', InvoiceStatus::Overdue)->count();

        $items = $paidInvoices->map(fn (Invoice $inv) => [
            'room' => $inv->reservation?->room?->code ?? $inv->lease?->room?->code ?? '-',
            'tenant' => $inv->user->name ?? '-',
            'amount' => $inv->total,
            'status' => 'Lunas',
        ])->all();

        $reportData = [
            'period' => $date->translatedFormat('F Y'),
            'total_revenue' => $totalRevenue,
            'occupancy_rate' => round($occupancyRate, 1),
            'total_rooms' => $totalRooms ?: 32,
            'occupied_rooms' => $occupiedRooms,
            'overdue_count' => $overdueCount,
            'items' => $items,
        ];

        return $pdfService->streamReport('bulanan', $reportData);
    }
}
