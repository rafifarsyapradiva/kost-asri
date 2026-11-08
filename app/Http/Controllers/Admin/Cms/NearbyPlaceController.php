<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\NearbyPlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NearbyPlaceController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $places = NearbyPlace::orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($places);
        }

        return Inertia::render('Admin/Cms/NearbyPlaces/Index', [
            'places' => $places,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:50'],
            'distance_text' => ['required', 'string', 'max:50'],
            'travel_minutes' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['is_published'] = $validated['is_published'] ?? true;
        $validated['sort_order'] ??= ((int) NearbyPlace::max('sort_order')) + 1;

        NearbyPlace::create($validated);

        return redirect()->route('admin.cms.nearby-places.index')->with('success', 'Tempat sekitar berhasil ditambahkan.');
    }

    public function update(Request $request, NearbyPlace $nearbyPlace): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:50'],
            'distance_text' => ['required', 'string', 'max:50'],
            'travel_minutes' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $nearbyPlace->update($validated);

        return redirect()->route('admin.cms.nearby-places.index')->with('success', 'Tempat sekitar berhasil diperbarui.');
    }

    public function destroy(NearbyPlace $nearbyPlace): RedirectResponse
    {
        $nearbyPlace->delete();

        return redirect()->route('admin.cms.nearby-places.index')->with('success', 'Tempat sekitar berhasil dihapus.');
    }
}
