<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\TenantProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class TenantProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user()->load('tenantProfile');

        return Inertia::render('Account/Profile', [
            'user' => $user,
            'profile' => $user->tenantProfile,
            'masked_nik' => $user->tenantProfile?->masked_nik,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nik' => ['nullable', 'string', 'max:20'],
            'ktp_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'origin_address' => ['nullable', 'string', 'max:500'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'institution_name' => ['nullable', 'string', 'max:150'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:25'],
            'guardian_email' => ['nullable', 'email', 'max:150'],
            'guardian_relation' => ['nullable', 'string', 'max:50'],
            'emergency_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $user->tenantProfile ?? new TenantProfile(['user_id' => $user->id]);

        if ($request->hasFile('ktp_photo')) {
            $file = $request->file('ktp_photo');
            $uuid = (string) Str::uuid();
            $filename = "ktp/{$uuid}.webp";

            // Re-encode via Intervention Image to strip EXIF and prevent polyglots (SEC-010, SEC-011)
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            $encoded = (string) $image->toWebp(85);

            // Delete previous photo if exists
            if (! empty($profile->ktp_photo_path) && Storage::disk('local')->exists($profile->ktp_photo_path)) {
                Storage::disk('local')->delete($profile->ktp_photo_path);
            }

            Storage::disk('local')->put($filename, $encoded);
            $profile->ktp_photo_path = $filename;
        }

        foreach ([
            'nik', 'gender', 'birth_date', 'origin_address',
            'occupation', 'institution_name', 'guardian_name',
            'guardian_phone', 'guardian_email', 'guardian_relation', 'emergency_notes',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $profile->{$field} = $validated[$field];
            }
        }

        $profile->user_id = $user->id;
        $profile->save();

        return back()->with('success', 'Profil penyewa berhasil disimpan.');
    }
}
