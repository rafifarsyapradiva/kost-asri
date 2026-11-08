<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    /** @use HasFactory<\Database\Factories\TestimonialFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'avatar_path',
        'origin_info',
        'rating',
        'content',
        'moderation_status',
        'approved_by',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePublishedOrdered(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('moderation_status', 'approved')
            ->orderBy('sort_order');
    }
}
