<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function __construct(
        protected MediaUploadService $mediaService
    ) {
    }

    public function index(Request $request): Response|JsonResponse
    {
        $galleries = Gallery::orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($galleries);
        }

        return Inertia::render('Admin/Cms/Galleries/Index', [
            'galleries' => $galleries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:kamar,fasilitas,lingkungan'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $path = $this->mediaService->uploadAndOptimize($request->file('image'), 'galleries');

        Gallery::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'image_path' => $path,
            'caption' => $validated['caption'] ?? null,
            'is_published' => $validated['is_published'] ?? true,
            'sort_order' => $validated['sort_order'] ?? (((int) Gallery::max('sort_order')) + 1),
        ]);

        return redirect()->route('admin.cms.galleries.index')->with('success', 'Foto galeri berhasil ditambahkan.');
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:kamar,fasilitas,lingkungan'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if ($request->hasFile('image')) {
            $this->mediaService->delete($gallery->image_path);
            $validated['image_path'] = $this->mediaService->uploadAndOptimize($request->file('image'), 'galleries');
        }

        $gallery->update($validated);

        return redirect()->route('admin.cms.galleries.index')->with('success', 'Foto galeri berhasil diperbarui.');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->mediaService->delete($gallery->image_path);
        $gallery->delete();

        return redirect()->route('admin.cms.galleries.index')->with('success', 'Foto galeri berhasil dihapus.');
    }
}
