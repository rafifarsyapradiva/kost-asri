<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\ReservationStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationStepController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $steps = ReservationStep::orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($steps);
        }

        return Inertia::render('Admin/Cms/Steps/Index', [
            'steps' => $steps,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'step_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['icon'] ??= 'check';
        $validated['sort_order'] ??= $validated['step_number'];
        $validated['is_published'] = $validated['is_published'] ?? true;

        ReservationStep::create($validated);

        return redirect()->route('admin.cms.steps.index')->with('success', 'Langkah reservasi berhasil ditambahkan.');
    }

    public function update(Request $request, ReservationStep $step): RedirectResponse
    {
        $validated = $request->validate([
            'step_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['icon'] ??= 'check';
        $step->update($validated);

        return redirect()->route('admin.cms.steps.index')->with('success', 'Langkah reservasi berhasil diperbarui.');
    }

    public function destroy(ReservationStep $step): RedirectResponse
    {
        $step->delete();

        return redirect()->route('admin.cms.steps.index')->with('success', 'Langkah reservasi berhasil dihapus.');
    }
}
