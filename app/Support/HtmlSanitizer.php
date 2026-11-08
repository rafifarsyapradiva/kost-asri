<?php

declare(strict_types=1);

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Allowed HTML tags for CMS rich-text content (SEC-014).
     */
    protected const ALLOWED_TAGS = '<p><br><strong><b><em><i><ul><ol><li><a><h3><h4><blockquote>';

    /**
     * Sanitize HTML content using an allowlist approach.
     */
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // 1. Remove script, style, iframe blocks completely with contents
        $cleaned = preg_replace('/<(script|style|iframe|object|embed)[^>]*>.*?<\/\1>/is', '', $html) ?? '';

        // 2. Strip tags not in allowlist
        $stripped = strip_tags($cleaned, self::ALLOWED_TAGS);

        // 3. Remove inline JavaScript event handlers (e.g. onclick, onerror, onload)
        $noEvents = preg_replace('/\s*on[a-zA-Z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', $stripped) ?? '';

        // 4. Remove javascript:, data:, and vbscript: protocols (quoted or unquoted)
        $noDangerousProtocols = preg_replace(
            '/\b(href|src)\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^\s>"\']*\2?/i',
            '$1="#"',
            $noEvents
        ) ?? '';

        return trim($noDangerousProtocols);
    }
}
