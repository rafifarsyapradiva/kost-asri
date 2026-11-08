<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\Pdf\PdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReceiptPdfController extends Controller
{
    public function __invoke(Receipt $receipt, PdfService $pdfService): Response
    {
        Gate::authorize('view', $receipt);

        return $pdfService->streamReceipt($receipt);
    }
}

