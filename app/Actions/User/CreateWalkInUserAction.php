<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateWalkInUserAction
{
    /**
     * Create a walk-in user account with mandatory phone and optional email.
     *
     * @param  array{name: string, phone: string, email?: ?string, role?: UserRole|string}  $data
     * @return array{user: User, plain_password: string}
     */
    public function execute(array $data): array
    {
        $normalizedPhone = PhoneNormalizer::normalize($data['phone'] ?? null);

        if ($normalizedPhone === null) {
            throw new InvalidArgumentException('Nomor WhatsApp wajib valid untuk akun walk-in.');
        }

        $plainPassword = Str::password(12);

        $role = $data['role'] ?? UserRole::Prospect;
        if (! ($role instanceof UserRole)) {
            $role = UserRole::from((string) $role);
        }

        $user = new User([
            'name' => $data['name'],
            'email' => ! empty($data['email']) ? strtolower(trim((string) $data['email'])) : null,
            'phone' => $normalizedPhone,
            'password' => Hash::make($plainPassword),
        ]);

        $user->forceFill([
            'role' => $role,
            'is_active' => true,
            'email_verified_at' => ! empty($data['email']) ? now() : null,
        ])->save();

        return [
            'user' => $user,
            'plain_password' => $plainPassword,
        ];
    }
}
