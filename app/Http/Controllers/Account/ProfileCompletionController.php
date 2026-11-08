<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileCompletionController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Account/CompleteProfile', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! PhoneNormalizer::isValid((string) $value)) {
                    $fail('Nomor WhatsApp tidak valid. Gunakan format Indonesia yang benar.');
                }
            }],
        ]);

        $user = $request->user();
        $user->phone = $request->input('phone');
        $user->save();

        return redirect()->intended('/akun/reservasi-saya')
            ->with('success', 'Profil WhatsApp Anda berhasil dilengkapi.');
    }
}
