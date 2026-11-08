<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $videos = Video::orderBy('sort_order')->get();

        if ($request->wantsJson()) {
            return response()->json($videos);
        }

        return Inertia::render('Admin/Cms/Videos/Index', [
            'videos' => $videos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'youtube_url' => ['required', 'string', 'url'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $youtubeId = $this->extractYoutubeId($validated['youtube_url']);
        if ($youtubeId === null) {
            return back()->withErrors(['youtube_url' => 'Format URL YouTube tidak valid.']);
        }

        $video = new Video([
            'title' => $validated['title'],
            'youtube_url' => $validated['youtube_url'],
            'youtube_id' => $youtubeId,
            'thumbnail_url' => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? (((int) Video::max('sort_order')) + 1),
            'is_published' => $validated['is_published'] ?? true,
        ]);
        $video->save();

        return redirect()->route('admin.cms.videos.index')->with('success', 'Video YouTube berhasil ditambahkan.');
    }

    public function update(Request $request, Video $video): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'youtube_url' => ['required', 'string', 'url'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $youtubeId = $this->extractYoutubeId($validated['youtube_url']);
        if ($youtubeId === null) {
            return back()->withErrors(['youtube_url' => 'Format URL YouTube tidak valid.']);
        }

        $video->update([
            'title' => $validated['title'],
            'youtube_url' => $validated['youtube_url'],
            'youtube_id' => $youtubeId,
            'thumbnail_url' => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $video->sort_order,
            'is_published' => $validated['is_published'] ?? $video->is_published,
        ]);

        return redirect()->route('admin.cms.videos.index')->with('success', 'Video YouTube berhasil diperbarui.');
    }

    public function destroy(Video $video): RedirectResponse
    {
        $video->delete();

        return redirect()->route('admin.cms.videos.index')->with('success', 'Video berhasil dihapus.');
    }

    /**
     * Extract 11-character YouTube video ID (FR-CMS-03).
     */
    protected function extractYoutubeId(string $url): ?string
    {
        $pattern = '%^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$%x';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
