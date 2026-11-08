<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotKeyword;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatbotController extends Controller
{
    /**
     * Display chatbot management dashboard (BE-041, FR-BOT-05).
     */
    public function index(Request $request): Response
    {
        $keywords = ChatbotKeyword::orderBy('sort_order')->get();
        $sessions = ChatbotSession::latest('updated_at')->take(30)->get();
        $messages = ChatbotMessage::with('matchedKeyword')->latest('id')->paginate(30);

        return Inertia::render('Admin/Chatbot/Index', [
            'keywords' => $keywords,
            'sessions' => $sessions,
            'messages' => $messages,
        ]);
    }

    /**
     * Store new chatbot keyword.
     */
    public function storeKeyword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:40', 'unique:chatbot_keywords,keyword'],
            'menu_number' => ['nullable', 'string', 'max:5'],
            'synonyms' => ['nullable', 'array'],
            'action' => ['required', 'string', 'max:20'],
            'reply_template' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        ChatbotKeyword::create($validated);

        return back()->with('success', 'Keyword chatbot berhasil ditambahkan.');
    }

    /**
     * Update an existing chatbot keyword.
     */
    public function updateKeyword(Request $request, ChatbotKeyword $keyword): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:40', 'unique:chatbot_keywords,keyword,'.$keyword->id],
            'menu_number' => ['nullable', 'string', 'max:5'],
            'synonyms' => ['nullable', 'array'],
            'action' => ['required', 'string', 'max:20'],
            'reply_template' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $keyword->update($validated);

        return back()->with('success', 'Keyword chatbot berhasil diperbarui.');
    }

    /**
     * Delete a chatbot keyword.
     */
    public function destroyKeyword(ChatbotKeyword $keyword): RedirectResponse
    {
        $keyword->delete();

        return back()->with('success', 'Keyword chatbot berhasil dihapus.');
    }

    /**
     * Release handover session early so chatbot resumes responding immediately (BE-041, FR-BOT-04).
     */
    public function releaseHandover(ChatbotSession $session): RedirectResponse
    {
        $session->update([
            'handover_until' => null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Handover bot diakhiri. Chatbot kini aktif kembali untuk nomor tersebut.');
    }
}
