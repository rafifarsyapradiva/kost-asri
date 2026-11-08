<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Support\PhoneNormalizer;

class ChatbotEngine
{
    public function __construct(
        protected KeywordMatcher $matcher,
        protected ChatbotReplies $replies
    ) {
    }

    /**
     * Handle incoming WhatsApp message and produce an automated reply (BE-040, FR-BOT-01..04).
     */
    public function handle(string $sender, string $text): string
    {
        $normalizedSender = PhoneNormalizer::normalize($sender) ?? $sender;

        /** @var ChatbotSession $session */
        $session = ChatbotSession::firstOrCreate(['phone' => $normalizedSender]);

        // Handover mode: bot stays silent for 60 minutes while admin responds (FR-BOT-04)
        if ($session->handover_until !== null && $session->handover_until->isFuture()) {
            return '';
        }

        $keyword = $this->matcher->match($text);

        // Record inbound message
        ChatbotMessage::create([
            'phone' => $normalizedSender,
            'direction' => 'in',
            'message' => $text,
            'matched_keyword_id' => $keyword?->id,
            'created_at' => now(),
        ]);

        $reply = match ($keyword?->action) {
            'availability' => $this->replies->availability(),
            'prices' => $this->replies->prices(),
            'invoice' => $this->replies->invoiceFor($normalizedSender),
            'pay' => $this->replies->payInfo($normalizedSender),
            'location' => $this->replies->location(),
            'faq' => $this->replies->faq(),
            'handover' => $this->handleHandover($session),
            'static' => (string) ($keyword->reply_template ?: $this->replies->menu()),
            default => $this->replies->menu(),
        };

        if ($reply !== '') {
            ChatbotMessage::create([
                'phone' => $normalizedSender,
                'direction' => 'out',
                'message' => $reply,
                'matched_keyword_id' => $keyword?->id,
                'created_at' => now(),
            ]);

            $session->update([
                'last_keyword' => $keyword?->keyword,
                'updated_at' => now(),
            ]);
        }

        return $reply;
    }

    /**
     * Initiate handover to human admin for 60 minutes (FR-BOT-04).
     */
    protected function handleHandover(ChatbotSession $session): string
    {
        $session->update([
            'handover_until' => now()->addMinutes(60),
            'last_keyword' => 'ADMIN',
            'updated_at' => now(),
        ]);

        return "👨‍💼 *Permintaan Terhubung ke Admin / Penjaga Kost:*

Pesan Anda telah diteruskan ke Admin ASRI BOARDING HOUSE.
Chatbot dijeda selama 60 menit agar Admin dapat membalas pesan Anda secara langsung.

Terima kasih atas kesabaran Anda!";
    }
}

