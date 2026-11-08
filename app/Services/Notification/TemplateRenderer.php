<?php

declare(strict_types=1);

namespace App\Services\Notification;

class TemplateRenderer
{
    /**
     * Replace all placeholders {key} with safe string values (BE-037, PRD §11.2).
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $template, array $data): string
    {
        $replacements = [];

        foreach ($data as $key => $value) {
            $replacements['{'.$key.'}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }
}
