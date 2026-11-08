<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\InvoiceItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(InvoiceItemType::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
