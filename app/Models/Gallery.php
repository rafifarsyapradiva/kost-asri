<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'category',
        'image_path',
        'thumbnail_path',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublishedOrdered(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }
}
