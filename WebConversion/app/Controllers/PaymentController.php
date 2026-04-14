<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\AuditLogRepository;
use App\Models\OfferRepository;
use App\Models\PaymentRepository;
use App\Models\WebinarRepository;
use App\Services\AdminAuthService;
use App\Services\OfferService;
use App\Services\PaymentService;

final class PaymentController
{
    public function createCheckout(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();

        $externalId = (string) ($payload['webinar_id'] ?? '');
        $leadToken = (string) ($payload['lead_token'] ?? '');
        $amount = (int) ($payload['amount_cents'] ?? 0);
        $currency = (string) ($payload['currency'] ?? 'USD');
        $provider = (string) ($payload['provider'] ?? 'stripe');
        $paymentService = new PaymentService();
        $provider = $paymentService->normalizeProvider($provider);
        $currency = $paymentService->normalizeCurrency($currency);

        if ($externalId === '' || $leadToken === '' || !$paymentService->validateAmountCents($amount)) {
            Response::json(['error' => 'webinar_id, lead_token, amount_cents обязательны'], 422);
            return;
        }

        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $repo = new PaymentRepository();
        $paymentId = $repo->create((int) $webinar['id'], $leadToken, $amount, $currency, $provider, 'pending');
        $checkoutUrl = $paymentService->buildCheckoutUrl($provider, $paymentId);

        (new AuditLogRepository())->write('admin_api', 'checkout_created', ['payment_id' => $paymentId, 'provider' => $provider]);

        Response::json(['payment_id' => $paymentId, 'checkout_url' => $checkoutUrl], 201);
    }

    public function checkoutInRoom(): void
    {
        $payload = $this->readJsonBody();
        $externalId = (string) ($payload['webinar_id'] ?? '');
        $leadToken = (string) ($payload['lead_token'] ?? '');
        $provider = (string) ($payload['provider'] ?? 'stripe');
        $paymentService = new PaymentService();
        $provider = $paymentService->normalizeProvider($provider);

        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null || $leadToken === '') {
            Response::json(['error' => 'Некорректный запрос checkout'], 422);
            return;
        }

        $offers = (new OfferRepository())->activeByWebinar((int) $webinar['id']);
        if ($offers === []) {
            Response::json(['error' => 'Нет активного offer для checkout'], 404);
            return;
        }

        $offer = $offers[0];
        $amount = (int) (($payload['amount_cents'] ?? 0) ?: 9900);
        if (!$paymentService->validateAmountCents($amount)) {
            Response::json(['error' => 'Некорректная сумма checkout'], 422);
            return;
        }

        $paymentId = (new PaymentRepository())->create((int) $webinar['id'], $leadToken, $amount, 'USD', $provider, 'pending');
        $checkoutUrl = $paymentService->buildCheckoutUrl($provider, $paymentId);

        $expiresAt = null;
        if (!empty($offer['activated_at'])) {
            $expiresAt = (new OfferService())->expiresAt((string) $offer['activated_at'], (int) $offer['ttl_sec']);
        }

        Response::json([
            'payment_id' => $paymentId,
            'checkout_url' => $checkoutUrl,
            'offer' => [
                'title' => $offer['title'],
                'description' => $offer['description'],
                'expires_at' => $expiresAt,
            ],
            'post_payment' => [
                'success_redirect' => '/thank-you?payment_id=' . $paymentId,
                'failure_redirect' => '/checkout-failed?payment_id=' . $paymentId,
            ],
        ], 201);
    }

    public function webhook(): void
    {
        $provider = (string) ($_GET['provider'] ?? 'stripe');
        $raw = file_get_contents('php://input');
        $payload = is_string($raw) ? $raw : '';
        $signature = (string) ($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '');
        $keyId = isset($_SERVER['HTTP_X_WEBHOOK_KEY_ID']) ? (string) $_SERVER['HTTP_X_WEBHOOK_KEY_ID'] : null;

        $secretMap = (string) (getenv('PAYMENT_WEBHOOK_SECRETS') ?: getenv('PAYMENT_WEBHOOK_SECRET'));
        $service = new PaymentService();
        if (!$service->verifyWebhookSignature($payload, $signature, $secretMap, $keyId)) {
            Response::json(['error' => 'Некорректная подпись webhook'], 401);
            return;
        }

        $parsed = $service->parseWebhookPayload($payload);
        if ($parsed === null) {
            Response::json(['error' => 'Некорректный payload webhook'], 422);
            return;
        }

        $repo = new PaymentRepository();
        $isNewEvent = $repo->recordWebhookEventIfNew($provider, $parsed['event_id'], $parsed['payment_id'], $payload);
        if (!$isNewEvent) {
            Response::json(['ok' => true, 'deduplicated' => true]);
            return;
        }

        $payment = $repo->findByPaymentId($parsed['payment_id']);
        if ($payment === null) {
            Response::json(['error' => 'payment_id not found'], 404);
            return;
        }

        $repo->updateStatus($parsed['payment_id'], $parsed['status'], $payload);
        (new AuditLogRepository())->write('webhook:' . $provider, 'payment_status_updated', [
            'payment_id' => $parsed['payment_id'],
            'status' => $parsed['status'],
            'event_id' => $parsed['event_id'],
        ]);

        Response::json(['ok' => true]);
    }

    public function reconciliation(): void
    {
        (new AdminAuthService())->requireAdmin();
        $externalId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $repo = new PaymentRepository();
        Response::json([
            'payments' => $repo->listByWebinar((int) $webinar['id']),
            'summary' => $repo->reconciliationSummary((int) $webinar['id']),
        ]);
    }

    public function pspE2eMatrix(): void
    {
        (new AdminAuthService())->requireAdmin();
        Response::json([
            'providers' => (new PaymentService())->pspMatrix(),
            'edge_cases' => [
                'invalid_signature' => 'covered',
                'duplicate_webhook_event' => 'covered',
                'invalid_amount' => 'covered',
                'retry_limit_exceeded' => 'covered',
            ],
        ]);
    }


    public function retryCheckout(): void
    {
        (new AdminAuthService())->requireAdmin();
        $payload = $this->readJsonBody();
        $paymentId = (string) ($payload['payment_id'] ?? '');
        $reason = (string) ($payload['reason'] ?? 'retry_requested');

        if ($paymentId === '') {
            Response::json(['error' => 'payment_id обязателен'], 422);
            return;
        }

        $repo = new PaymentRepository();
        $payment = $repo->findByPaymentId($paymentId);
        if ($payment === null) {
            Response::json(['error' => 'Платеж не найден'], 404);
            return;
        }

        $maxRetries = (new PaymentService())->maxRetryAttempts();
        $currentRetries = (int) ($payment['retry_count'] ?? 0);
        if ($currentRetries >= $maxRetries) {
            Response::json(['error' => 'Превышен лимит retry attempts'], 409);
            return;
        }

        $newRetries = $repo->incrementRetry($paymentId, $reason);
        $checkoutUrl = (new PaymentService())->buildCheckoutUrl((string) ($payment['provider'] ?? 'stripe'), $paymentId);

        (new AuditLogRepository())->write('admin_api', 'checkout_retry_requested', [
            'payment_id' => $paymentId,
            'reason' => $reason,
            'retry_count' => $newRetries,
        ]);

        Response::json([
            'payment_id' => $paymentId,
            'retry_count' => $newRetries,
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function opsDashboard(): void
    {
        (new AdminAuthService())->requireAdmin();
        $externalId = (string) ($_GET['webinar_id'] ?? '');
        $webinar = (new WebinarRepository())->findByExternalId($externalId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $repo = new PaymentRepository();
        Response::json([
            'webinar_id' => $externalId,
            'reconciliation' => $repo->reconciliationSummary((int) $webinar['id']),
            'providers' => $repo->providerBreakdown((int) $webinar['id']),
            'retry_queue' => $repo->listRetryCandidates(100),
        ]);
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
