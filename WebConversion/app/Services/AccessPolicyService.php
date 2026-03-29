<?php

declare(strict_types=1);

namespace App\Services;

final class AccessPolicyService
{
    public function validate(string $accessMode, array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));

        if ($name === '') {
            return ['ok' => false, 'error' => 'Имя обязательно'];
        }

        if (in_array($accessMode, ['name_email', 'name_email_phone'], true) && $email === '') {
            return ['ok' => false, 'error' => 'Email обязателен'];
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Email некорректен'];
        }

        if ($accessMode === 'name_email_phone' && $phone === '') {
            return ['ok' => false, 'error' => 'Телефон обязателен'];
        }

        return [
            'ok' => true,
            'name' => $name,
            'email' => $email === '' ? null : $email,
            'phone' => $phone === '' ? null : $phone,
        ];
    }
}
