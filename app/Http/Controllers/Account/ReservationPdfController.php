<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\Pdf\PdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReservationPdfController extends Controller
{
    public function __invoke(Reservation $reservation, PdfService $pdfService): Response
    {
        Gate::authorize('view', $reservation);

        return $pdfService->streamReservationNote($reservation);
    }
}

