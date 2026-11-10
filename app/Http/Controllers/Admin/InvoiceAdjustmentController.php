<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInvoiceRequest;
use App\Http\Requests\Admin\VoidInvoiceRequest;
use App\Http\Requests\Admin\WaivePenaltyRequest;
use App\Models\Invoice;
use App\Services\Billing\PenaltyService;

class InvoiceAdjustmentController extends Controller
{
    public function __construct(
        private readonly PenaltyService $penaltyService
    ) {
    }

    public function waivePenalty(WaivePenaltyRequest $request, Invoice $invoice): mixed
    {
        $this->penaltyService->waivePenalty(
            $invoice,
            $request->user(),
            $request->validated('reason')
        );

        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('success', 'Denda berhasil dibebaskan (waived).');
        }

        return response()->json([
            'message' => 'Denda berhasil dibebaskan.',
            'invoice' => $invoice->fresh(['items', 'penaltyLogs']),
        ]);
    }

    public function voidInvoice(VoidInvoiceRequest $request, Invoice $invoice): mixed
    {
        $this->penaltyService->voidInvoice(
            $invoice,
            $request->user(),
            $request->validated('reason')
        );

        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('success', 'Invoice berhasil dibatalkan (void).');
        }

        return response()->json([
            'message' => 'Invoice berhasil dibatalkan (void).',
            'invoice' => $invoice->fresh(),
        ]);
    }

    public function addItem(AdjustInvoiceRequest $request, Invoice $invoice): mixed
    {
        $item = $this->penaltyService->addAdjustmentItem(
            $invoice,
            $request->user(),
            InvoiceItemType::from($request->validated('type')),
            $request->validated('description'),
            (int) $request->validated('amount')
        );

        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('success', 'Item penyesuaian berhasil ditambahkan.');
        }

        return response()->json([
            'message' => 'Item penyesuaian berhasil ditambahkan.',
            'item' => $item,
            'invoice' => $invoice->fresh(['items']),
        ]);
    }
}
