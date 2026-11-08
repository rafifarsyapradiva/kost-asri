<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * Explicit mass assignment protection (SEC-007).
     * Sensitive attributes (role, google_id, is_active) are excluded and must be set via explicit methods or forceFill.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar_url',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Normalize Indonesian phone number to 628xxx format (FR-AUTH-01, TST-005).
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                if ($value === null || trim($value) === '') {
                    return null;
                }

                $cleaned = preg_replace('/[^0-9]/', '', $value) ?? '';

                if (str_starts_with($cleaned, '08')) {
                    return '628' . substr($cleaned, 2);
                }

                if (str_starts_with($cleaned, '628')) {
                    return $cleaned;
                }

                if (str_starts_with($cleaned, '8')) {
                    return '628' . substr($cleaned, 1);
                }

                return $cleaned;
            }
        );
    }

    public function tenantProfile(): HasOne
    {
        return $this->hasOne(TenantProfile::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActiveTenant(): bool
    {
        return $this->role === UserRole::Tenant;
    }

    public function isProspect(): bool
    {
        return $this->role === UserRole::Prospect;
    }
}
