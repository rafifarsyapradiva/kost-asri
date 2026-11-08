<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservation;

use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStep4PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_scheme' => ['required', Rule::enum(PaymentScheme::class)],
            'preferred_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'custom_dp' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
