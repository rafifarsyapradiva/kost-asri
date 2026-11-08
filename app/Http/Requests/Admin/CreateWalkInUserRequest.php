<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class CreateWalkInUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:150', 'unique:' . User::class],
            'phone' => ['required', 'string', 'max:25', function ($attribute, $value, $fail) {
                if (! PhoneNormalizer::isValid((string) $value)) {
                    $fail('Nomor WhatsApp walk-in tidak valid.');
                }
            }],
        ];
    }
}
