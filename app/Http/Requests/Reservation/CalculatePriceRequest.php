<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservation;

use App\Enums\ProrataOption;
use App\Enums\RentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalculatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'rent_type' => ['required', Rule::enum(RentType::class)],
            'check_in_date' => ['required', 'date'],
            'duration_qty' => ['required', 'integer', 'min:1'],
            'prorata_option' => ['nullable', Rule::enum(ProrataOption::class)],
        ];
    }
}
