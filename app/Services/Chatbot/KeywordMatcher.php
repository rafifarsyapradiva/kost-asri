<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Models\ChatbotKeyword;
use Illuminate\Support\Str;

class KeywordMatcher
{
    /**
     * Match normalized user message to active ChatbotKeyword (BE-040, TST-013, FR-BOT-01).
     */
    public function match(string $message): ?ChatbotKeyword
    {
        // Normalize: uppercase, remove non-alphanumeric except spaces, squish
        $normalized = Str::of($message)
            ->upper()
            ->replaceMatches('/[^A-Z0-9 ]/', ' ')
            ->squish()
            ->toString();

        if (empty($normalized)) {
            return $this->fallbackMenu();
        }

        $keywords = ChatbotKeyword::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // 1. Exact match on keyword or menu_number
        foreach ($keywords as $keyword) {
            if (strtoupper($keyword->keyword) === $normalized) {
                return $keyword;
            }
            if ($keyword->menu_number !== null && (string) $keyword->menu_number === $normalized) {
                return $keyword;
            }
        }

        // 2. Synonym match
        $normalizedWords = explode(' ', $normalized);

        foreach ($keywords as $keyword) {
            $synonyms = $keyword->synonyms ?? [];
            if (! is_array($synonyms)) {
                continue;
            }

            foreach ($synonyms as $synonym) {
                $cleanSynonym = Str::of((string) $synonym)->upper()->replaceMatches('/[^A-Z0-9 ]/', ' ')->squish()->toString();

                if ($cleanSynonym === '') {
                    continue;
                }

                // Exact synonym match or phrase match
                if ($cleanSynonym === $normalized || str_contains($normalized, $cleanSynonym)) {
                    return $keyword;
                }

                // Single word match in sentence
                if (in_array($cleanSynonym, $normalizedWords, true)) {
                    return $keyword;
                }
            }
        }

        return $this->fallbackMenu();
    }

    /**
     * Fallback to MENU keyword.
     */
    protected function fallbackMenu(): ?ChatbotKeyword
    {
        return ChatbotKeyword::where('keyword', 'MENU')->first();
    }
}
