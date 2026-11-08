<?php

declare(strict_types=1);

namespace App\Http\Requests\Reservation;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreStep3ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $draft = session('reservation_draft', []);
        $rentType = $this->input('rent_type', $draft['rent_type'] ?? 'monthly');
        $isGuardianRequired = in_array($rentType, ['monthly', 'weekly'], true);

        return [
            'guardian_name' => [$isGuardianRequired ? 'required' : 'nullable', 'string', 'max:100'],
            'guardian_phone' => [
                $isGuardianRequired ? 'required' : 'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! empty($value) && PhoneNormalizer::normalize((string) $value) === null) {
                        $fail('Nomor WhatsApp wali tidak valid.');
                    }
                },
            ],
            'guardian_email' => ['nullable', 'email', 'max:100'],
            'guardian_relation' => [$isGuardianRequired ? 'required' : 'nullable', 'string', 'max:50'],
            'privacy_consent' => ['required', 'accepted'],
            'nik' => ['nullable', 'string', 'digits:16'],
            'ktp_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'origin_address' => ['nullable', 'string', 'max:500'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'institution_name' => ['nullable', 'string', 'max:150'],
        ];
    }
}
