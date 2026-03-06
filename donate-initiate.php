<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
require_once __DIR__ . '/donation-store.php';

pesapal_load_env();

// Keep canonical host consistent (www) to avoid origin/CSP confusion
$host = $_SERVER['HTTP_HOST'] ?? '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($host !== '' && stripos($host, 'www.') !== 0) {
    $path = $_SERVER['REQUEST_URI'] ?? '/donate-initiate.php';
    $scheme = $isHttps ? 'https' : 'http';
    header('Location: ' . $scheme . '://www.' . $host . $path, true, 301);
    exit;
}

header('Content-Type: text/html; charset=utf-8');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Location: ./donate.php#donate', true, 303);
        exit;
    }

    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$csrf = (string) ($_POST['csrf'] ?? '');
$expectedCsrf = (string) ($_SESSION['donate_csrf'] ?? '');
if ($csrf === '' || $expectedCsrf === '' || !hash_equals($expectedCsrf, $csrf)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

$amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0.0;
$currency = isset($_POST['currency']) ? trim((string) $_POST['currency']) : 'RWF';
$firstName = isset($_POST['first_name']) ? trim((string) $_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim((string) $_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim((string) $_POST['phone']) : '';
$message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';

if ($amount <= 0 || $firstName === '' || $lastName === '' || $email === '') {
    http_response_code(400);
    echo 'Invalid request. Please go back and fill all required fields.';
    exit;
}

$currency = strtoupper($currency);
$originalAmount = $amount;
$originalCurrency = $currency;
$fxRateToRwf = 1.0;

// Pesapal Rwanda contracts commonly settle in RWF and can reject large foreign-currency amounts.
// Convert non-RWF amounts to RWF using fixed FX rates configured in .env.
if ($currency !== 'RWF') {
    $fxKey = 'FX_' . preg_replace('/[^A-Z]/', '', $currency) . '_RWF';
    $rateStr = pesapal_env($fxKey);
    $rate = $rateStr !== null ? (float) $rateStr : 0.0;
    if ($rate <= 0) {
        http_response_code(400);
        echo 'Unsupported currency. Please select RWF or contact support.';
        exit;
    }

    $fxRateToRwf = $rate;
    $amount = round($originalAmount * $fxRateToRwf, 2);
    $currency = 'RWF';
}

$donationId = 'DON-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
$merchantReference = $donationId;

$callbackUrl = pesapal_base_url() . '/donate-callback.php';
$ipnUrl = pesapal_base_url() . '/pesapal-ipn.php';

$donationRow = [
    'id' => $donationId,
    'created_at' => gmdate('c'),
    'amount' => $amount,
    'currency' => $currency,
    'original_amount' => $originalAmount,
    'original_currency' => $originalCurrency,
    'fx_rate_to_rwf' => $fxRateToRwf,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'merchant_reference' => $merchantReference,
    'status' => 'PENDING',
];

try {
    donation_create($donationRow);

    $token = pesapal_request_token();

    $ipnId = pesapal_env('PESAPAL_IPN_ID');
    if (!$ipnId) {
        $ipnId = pesapal_register_ipn($token, $ipnUrl, 'GET');
    }

    $payload = [
        'id' => $merchantReference,
        'currency' => $currency,
        'amount' => $amount,
        'description' => 'Donation - 1000 Hills Rugby',
        'callback_url' => $callbackUrl,
        'notification_id' => $ipnId,
        'branch' => '1000 Hills Rugby',
        'billing_address' => [
            'email_address' => $email,
            'phone_number' => $phone,
            'country_code' => 'RW',
            'first_name' => $firstName,
            'middle_name' => '',
            'last_name' => $lastName,
            'line_1' => '',
            'line_2' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'zip_code' => '',
        ],
    ];

    try {
        $resp = pesapal_submit_order($token, $payload);
    } catch (Throwable $e) {
        $payload['notification_id'] = pesapal_register_ipn($token, $ipnUrl, 'GET');
        $resp = pesapal_submit_order($token, $payload);
    }

    $redirectUrl = (string) ($resp['redirect_url'] ?? '');
    $orderTrackingId = (string) ($resp['order_tracking_id'] ?? '');

    donation_update($donationId, [
        'order_tracking_id' => $orderTrackingId,
        'redirect_url' => $redirectUrl,
        'updated_at' => gmdate('c'),
    ]);

    if (!headers_sent()) {
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    echo '<a href="' . h($redirectUrl) . '">Continue to payment</a>';
    exit;
} catch (Throwable $e) {
    http_response_code(500);

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />';
    echo '<title>Donation Error</title></head><body>';
    echo '<div>';
    echo '<h1>Donation initiation failed</h1>';
    echo '<p>' . h($e->getMessage()) . '</p>';
    echo '<p><a href="./donate.php">Back</a></p>';
    echo '</div></body></html>';
    exit;
}
