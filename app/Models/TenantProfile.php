<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProfile extends Model
{
    /** @use HasFactory<\Database\Factories\TenantProfileFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nik',
        'nik_hash',
        'ktp_photo_path',
        'gender',
        'birth_date',
        'origin_address',
        'occupation',
        'institution_name',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'guardian_relation',
        'emergency_notes',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'birth_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $profile): void {
            if ($profile->nik !== null && $profile->nik !== '') {
                $profile->nik_hash = hash('sha256', (string) $profile->nik);
            }
        });
    }

    /**
     * Masked NIK for secure UI presentation (SEC-009).
     */
    protected function maskedNik(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->nik === null || strlen($this->nik) < 6) {
                    return null;
                }

                $len = strlen($this->nik);
                $prefix = substr($this->nik, 0, 4);
                $suffix = substr($this->nik, -2);
                $maskCount = max(0, $len - 6);

                return $prefix . str_repeat('*', $maskCount) . $suffix;
            }
        );
    }

    /**
     * Normalize guardian WhatsApp number to 628xxx format.
     */
    protected function guardianPhone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => \App\Support\PhoneNormalizer::normalize($value) ?? ($value ? preg_replace('/[^0-9]/', '', $value) : null)
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
