<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $faqs = Faq::orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($faqs);
        }

        return Inertia::render('Admin/Cms/Faqs/Index', [
            'faqs' => $faqs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['answer'] = HtmlSanitizer::clean($validated['answer']);
        $validated['is_published'] = $validated['is_published'] ?? true;
        $validated['sort_order'] ??= (((int) Faq::max('sort_order')) + 1);

        Faq::create($validated);

        return redirect()->route('admin.cms.faqs.index')->with('success', 'FAQ berhasil ditambahkan.');
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['answer'] = HtmlSanitizer::clean($validated['answer']);
        $faq->update($validated);

        return redirect()->route('admin.cms.faqs.index')->with('success', 'FAQ berhasil diperbarui.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.cms.faqs.index')->with('success', 'FAQ berhasil dihapus.');
    }
}
