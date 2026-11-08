<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * Get a setting by key, cached indefinitely until modified (BE-006).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            return Setting::get($key, $default);
        });
    }

    /**
     * Set/update a setting and flush its cache.
     */
    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $setting = Setting::where('key', $key)->first();

        $type ??= $setting?->type ?? 'string';
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        if ($setting !== null) {
            $setting->update([
                'value' => $stringValue,
                'type' => $type,
                'updated_at' => now(),
            ]);
        } else {
            Setting::create([
                'key' => $key,
                'value' => $stringValue,
                'type' => $type,
                'group' => 'general',
                'description' => $key,
                'updated_at' => now(),
            ]);
        }

        Cache::forget("settings.{$key}");
        Cache::forget('settings.__all__');
    }

    /**
     * Retrieve all settings mapped as key => value.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever('settings.__all__', function (): array {
            $results = [];
            foreach (Setting::all() as $setting) {
                $results[$setting->key] = Setting::get($setting->key);
            }

            return $results;
        });
    }

    /**
     * Flush all setting caches.
     */
    public function flush(): void
    {
        Cache::forget('settings.__all__');
        foreach (Setting::pluck('key') as $key) {
            Cache::forget("settings.{$key}");
        }
    }
}
