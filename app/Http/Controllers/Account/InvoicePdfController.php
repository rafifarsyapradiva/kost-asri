<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Pdf\PdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class InvoicePdfController extends Controller
{
    public function __invoke(Invoice $invoice, PdfService $pdfService): Response
    {
        Gate::authorize('view', $invoice);

        return $pdfService->streamInvoice($invoice);
    }
}

