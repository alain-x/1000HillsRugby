<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
pesapal_load_env();

header('Content-Type: text/html; charset=utf-8');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0.0;
$currency = isset($_POST['currency']) ? trim((string) $_POST['currency']) : 'RWF';
$description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
$firstName = isset($_POST['first_name']) ? trim((string) $_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim((string) $_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim((string) $_POST['phone']) : '';

if ($amount <= 0 || $description === '' || $firstName === '' || $lastName === '' || $email === '') {
    http_response_code(400);
    echo 'Invalid request. Please go back and fill all required fields.';
    exit;
}

$merchantReference = 'RUGBY-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
$callbackUrl = pesapal_base_url() . '/pesapal-callback.php';
$ipnUrl = pesapal_base_url() . '/pesapal-ipn.php';

try {
    $token = pesapal_request_token();

    $ipnId = pesapal_register_ipn($token, $ipnUrl, 'GET');

    $payload = [
        'id' => $merchantReference,
        'currency' => $currency,
        'amount' => $amount,
        'description' => $description,
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

    $resp = pesapal_submit_order($token, $payload);

    $redirectUrl = (string) $resp['redirect_url'];

    if (!headers_sent()) {
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    echo '<a href="' . h($redirectUrl) . '">Continue to Pesapal</a>';
    exit;
} catch (Throwable $e) {
    http_response_code(500);

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />';
    echo '<title>Pesapal Init Error</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50">';
    echo '<div class="max-w-2xl mx-auto px-4 py-10"><div class="bg-white border border-red-200 rounded-2xl shadow p-6">';
    echo '<div class="text-lg font-extrabold text-gray-900">Pesapal initiation failed</div>';
    echo '<div class="mt-3 text-sm text-gray-700">' . h($e->getMessage()) . '</div>';
    echo '<div class="mt-6"><a class="text-sm font-bold text-green-700 hover:text-green-800" href="./pesapal-test.php">Back to test page</a></div>';
    echo '</div></div></body></html>';
    exit;
}
