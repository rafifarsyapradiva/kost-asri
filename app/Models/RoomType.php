<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoomClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RoomType extends Model
{
    /** @use HasFactory<\Database\Factories\RoomTypeFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'room_class',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'size_m2',
        'facilities_json',
        'description',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'room_class' => RoomClass::class,
            'daily_rate' => 'integer',
            'weekly_rate' => 'integer',
            'monthly_rate' => 'integer',
            'size_m2' => 'float',
            'facilities_json' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomTypeImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(RoomTypeImage::class)->where('is_primary', true);
    }
}
