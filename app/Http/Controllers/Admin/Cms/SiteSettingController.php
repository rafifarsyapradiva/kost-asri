<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\Media\MediaUploadService;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function __construct(
        protected MediaUploadService $mediaService
    ) {
    }

    public function show(Request $request): Response|JsonResponse
    {
        $settings = SiteSetting::all()->keyBy('key');

        if ($request->wantsJson()) {
            return response()->json($settings);
        }

        return Inertia::render('Admin/Cms/SiteSettings/Show', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'about_title' => ['nullable', 'string', 'max:150'],
            'about_body' => ['nullable', 'string'],
            'about_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'address' => ['nullable', 'string', 'max:255'],
            'google_maps_embed' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:150'],
            'operating_hours' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
        ]);

        $aboutImage = null;
        if ($request->hasFile('about_image')) {
            $aboutImage = $this->mediaService->uploadAndOptimize($request->file('about_image'), 'site');
        }

        $existingAbout = SiteSetting::where('key', 'about_us')->first()?->value ?? [];
        if ($aboutImage !== null) {
            $existingAbout['image'] = $aboutImage;
        }
        if (isset($validated['about_title'])) {
            $existingAbout['title'] = $validated['about_title'];
        }
        if (isset($validated['about_body'])) {
            $existingAbout['body'] = HtmlSanitizer::clean($validated['about_body']);
        }

        SiteSetting::updateOrCreate(
            ['key' => 'about_us'],
            ['group' => 'about', 'value' => $existingAbout, 'updated_at' => now()]
        );

        SiteSetting::updateOrCreate(
            ['key' => 'location'],
            ['group' => 'contact', 'value' => [
                'address' => $validated['address'] ?? 'Jl. Banjarsari Selatan No. 8, Tembalang, Semarang',
                'google_maps_embed' => $validated['google_maps_embed'] ?? null,
                'latitude' => $validated['latitude'] ?? -7.0532,
                'longitude' => $validated['longitude'] ?? 110.4357,
                'phone' => $validated['phone'] ?? '6281234567890',
                'email' => $validated['email'] ?? 'kostasri012@gmail.com',
                'operating_hours' => $validated['operating_hours'] ?? '08:00 - 21:00 WIB',
            ], 'updated_at' => now()]
        );

        SiteSetting::updateOrCreate(
            ['key' => 'seo'],
            ['group' => 'seo', 'value' => [
                'meta_title' => $validated['meta_title'] ?? 'ASRI Boarding House — Kost Eksklusif Dekat Undip Tembalang',
                'meta_description' => $validated['meta_description'] ?? 'Kost putra/putri nyaman, bersih, fasilitas lengkap, dan aman di Tembalang Semarang.',
                'meta_keywords' => $validated['meta_keywords'] ?? 'kost tembalang, kost undip, asri boarding house',
            ], 'updated_at' => now()]
        );

        \Illuminate\Support\Facades\Cache::forget('landing.aggregated_content');

        return back()->with('success', 'Pengaturan situs landing page berhasil diperbarui.');
    }
}
