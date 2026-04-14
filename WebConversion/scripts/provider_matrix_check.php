<?php

declare(strict_types=1);

use App\Services\PaymentService;

require_once __DIR__ . '/../app/bootstrap.php';

$advisory = in_array('--advisory', $argv, true);

$providers = (new PaymentService())->pspMatrix();
$required = ['stripe', 'paypal', 'braintree', 'wayforpay'];
$map = [];
foreach ($providers as $provider) {
    $map[(string) ($provider['provider'] ?? '')] = (bool) ($provider['e2e_ready'] ?? false);
}

$missing = [];
foreach ($required as $name) {
    if (($map[$name] ?? false) !== true) {
        $missing[] = $name;
    }
}

$result = [
    'generated_at' => gmdate(DATE_ATOM),
    'all_required_providers_ready' => $missing === [],
    'missing' => $missing,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

if ($missing !== [] && !$advisory) {
    exit(1);
}

exit(0);
