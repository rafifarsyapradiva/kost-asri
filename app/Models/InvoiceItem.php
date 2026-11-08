<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceItemFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'type',
        'description',
        'qty',
        'unit_amount',
        'amount',
        'is_waived',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceItemType::class,
            'qty' => 'integer',
            'unit_amount' => 'integer',
            'amount' => 'integer',
            'is_waived' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
