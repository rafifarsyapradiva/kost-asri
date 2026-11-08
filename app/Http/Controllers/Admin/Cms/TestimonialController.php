<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $testimonials = Testimonial::with('user')->orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($testimonials);
        }

        return Inertia::render('Admin/Cms/Testimonials/Index', [
            'testimonials' => $testimonials,
        ]);
    }

    public function publish(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $nextStatus = ! $testimonial->is_published;

        $testimonial->update([
            'is_published' => $nextStatus,
            'moderation_status' => $nextStatus ? 'approved' : 'rejected',
            'approved_by' => $nextStatus ? \Illuminate\Support\Facades\Auth::id() : null,
        ]);

        $status = $nextStatus ? 'dipublikasikan' : 'disembunyikan';

        return back()->with('success', "Testimoni berhasil {$status}.");
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('admin.cms.testimonials.index')->with('success', 'Testimoni berhasil dihapus.');
    }
}
