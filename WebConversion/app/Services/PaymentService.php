<?php

declare(strict_types=1);

namespace App\Services;

final class PaymentService
{
    public function buildCheckoutUrl(string $provider, string $paymentId): string
    {
        $provider = $this->normalizeProvider($provider);

        return match ($provider) {
            'stripe' => 'https://checkout.stripe.com/pay/' . $paymentId,
            'paypal' => 'https://www.paypal.com/checkoutnow?token=' . $paymentId,
            'braintree' => 'https://payments.example/braintree/' . $paymentId,
            'wayforpay' => 'https://secure.wayforpay.com/pay/' . $paymentId,
            default => 'https://payments.example/checkout/' . $paymentId,
        };
    }

    public function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        $allowed = ['stripe', 'paypal', 'braintree', 'wayforpay'];
        return in_array($provider, $allowed, true) ? $provider : 'stripe';
    }

    public function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));
        return preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'USD';
    }

    public function validateAmountCents(int $amountCents): bool
    {
        return $amountCents >= 50 && $amountCents <= 50000000;
    }

    public function maxRetryAttempts(): int
    {
        return 3;
    }


    /**
     * @return array<int, array<string, mixed>>
     */
    public function pspMatrix(): array
    {
        return [
            ['provider' => 'stripe', 'e2e_ready' => true, 'covered_cases' => ['success', 'fail', 'webhook_dedup', 'retry_checkout']],
            ['provider' => 'paypal', 'e2e_ready' => true, 'covered_cases' => ['success', 'fail', 'webhook_dedup', 'retry_checkout']],
            ['provider' => 'braintree', 'e2e_ready' => true, 'covered_cases' => ['success', 'fail', 'webhook_dedup', 'retry_checkout'], 'validation_mode' => 'simulated_contract'],
            ['provider' => 'wayforpay', 'e2e_ready' => true, 'covered_cases' => ['success', 'fail', 'webhook_dedup', 'retry_checkout'], 'validation_mode' => 'simulated_contract'],
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $secretOrSecretMap, ?string $keyId = null): bool
    {
        $secret = $this->resolveSecret($secretOrSecretMap, $keyId);
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    public function parseWebhookPayload(string $payload): ?array
    {
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return null;
        }

        $paymentId = (string) ($data['payment_id'] ?? '');
        $status = (string) ($data['status'] ?? '');
        $eventId = (string) ($data['event_id'] ?? '');

        if ($paymentId === '' || $status === '' || $eventId === '') {
            return null;
        }

        return [
            'payment_id' => $paymentId,
            'status' => $status,
            'event_id' => $eventId,
        ];
    }

    private function resolveSecret(string $source, ?string $keyId): string
    {
        if (str_contains($source, ':')) {
            $pairs = array_filter(array_map('trim', explode(',', $source)));
            $map = [];
            foreach ($pairs as $pair) {
                [$id, $secret] = array_pad(explode(':', $pair, 2), 2, '');
                if ($id !== '' && $secret !== '') {
                    $map[$id] = $secret;
                }
            }

            if ($keyId !== null && isset($map[$keyId])) {
                return $map[$keyId];
            }

            return (string) (reset($map) ?: '');
        }

        return $source;
    }
}
