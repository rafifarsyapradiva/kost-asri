<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if ($setting === null || $setting->value === null) {
            return $default;
        }

        return match ($setting->type) {
            'int', 'integer' => (int) $setting->value,
            'bool', 'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode((string) $setting->value, true),
            'float' => (float) $setting->value,
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();

        $type = match (true) {
            is_int($value) => 'int',
            is_bool($value) => 'bool',
            is_array($value) => 'json',
            is_float($value) => 'float',
            default => 'string',
        };

        $storedValue = is_array($value) ? json_encode($value) : (string) $value;

        if ($setting !== null) {
            $setting->update([
                'value' => $storedValue,
                'type' => $type,
                'updated_at' => now(),
            ]);
        } else {
            static::create([
                'key' => $key,
                'value' => $storedValue,
                'type' => $type,
                'updated_at' => now(),
            ]);
        }
    }
}
