<?php

declare(strict_types=1);

use App\Services\Setting\SettingService;

if (! function_exists('setting')) {
    /**
     * Global helper to retrieve or set system settings (BE-006).
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingService::class);

        if ($key === null) {
            return $service;
        }

        return $service->get($key, $default);
    }
}
