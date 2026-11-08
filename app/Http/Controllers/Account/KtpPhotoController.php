<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\TenantProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class KtpPhotoController extends Controller
{
    /**
     * Securely stream private KTP photo authorized by Policy (BE-005, SEC-010, NFR-03).
     */
    public function show(Request $request, TenantProfile $tenantProfile): Response
    {
        Gate::authorize('viewKtp', $tenantProfile);

        if (empty($tenantProfile->ktp_photo_path) || ! Storage::disk('local')->exists($tenantProfile->ktp_photo_path)) {
            abort(404, 'Berkas foto KTP tidak ditemukan.');
        }

        $fileContents = Storage::disk('local')->get($tenantProfile->ktp_photo_path);

        return response($fileContents, 200, [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="ktp.webp"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
