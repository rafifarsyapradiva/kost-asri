<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotKeyword extends Model
{
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'keyword',
        'menu_number',
        'synonyms',
        'action',
        'reply_template',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'synonyms' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'matched_keyword_id');
    }
}
