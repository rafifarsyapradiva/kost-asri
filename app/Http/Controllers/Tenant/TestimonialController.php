<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    /**
     * Active tenant submits a testimonial (FR-TNT-03, FR-CMS-06).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'max:1000'],
            'origin_info' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user()->load('tenantProfile');
        $content = $validated['content'] ?? $validated['review'] ?? '';
        $originInfo = $validated['origin_info'] ?? $user->tenantProfile?->institution_name ?? 'Penyewa Kost Asri';

        Testimonial::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'avatar_path' => $user->avatar_url,
            'origin_info' => $originInfo,
            'rating' => $validated['rating'],
            'content' => trim(strip_tags((string) $content)),
            'moderation_status' => 'pending',
            'is_published' => false,
            'sort_order' => ((int) Testimonial::max('sort_order')) + 1,
        ]);

        return back()->with('success', 'Testimoni Anda berhasil dikirim dan menunggu moderasi pengelola.');
    }
}
