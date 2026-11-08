<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Enums\ReservationSource;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required_without:user_id', 'nullable', 'string', 'max:100'],
            'phone' => [
                'required_without:user_id',
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! empty($value) && PhoneNormalizer::normalize((string) $value) === null) {
                        $fail('Nomor WhatsApp walk-in tidak valid.');
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:100'],
            'room_id' => ['required', 'exists:rooms,id'],
            'rent_type' => ['required', Rule::enum(RentType::class)],
            'check_in_date' => ['required', 'date'],
            'duration_qty' => ['required', 'integer', 'min:1'],
            'prorata_option' => ['nullable', Rule::enum(ProrataOption::class)],
            'payment_scheme' => ['required', Rule::enum(PaymentScheme::class)],
            'preferred_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'source' => ['nullable', Rule::enum(ReservationSource::class)],
            'custom_dp' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'pay_now_cash' => ['nullable', 'boolean'],
        ];
    }
}
