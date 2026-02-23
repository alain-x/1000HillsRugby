<?php
declare(strict_types=1);

require_once __DIR__ . '/pesapal_bootstrap.php';

function redirect_with_error(string $message): void {
    $url = 'donate.php?err=' . rawurlencode($message);
    header('Location: ' . $url, true, 302);
    exit;
}

function is_debug_enabled(): bool {
    $v = strtolower((string) (getenv('APP_DEBUG') ?: 'false'));
    return in_array($v, ['1', 'true', 'yes', 'on'], true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method Not Allowed');
}

$csrf = $_POST['csrf_token'] ?? null;
if (!csrf_validate(is_string($csrf) ? $csrf : null)) {
    redirect_with_error('Invalid session. Please refresh and try again.');
}

$amountRaw = isset($_POST['amount']) ? trim((string) $_POST['amount']) : '';
$amount = (float) preg_replace('/[^0-9.]/', '', $amountRaw);

if (!is_finite($amount) || $amount <= 0) {
    redirect_with_error('Please enter a valid amount.');
}

$minAmount = (float) (getenv('PESAPAL_DONATION_MIN') ?: 0);
$maxAmount = (float) (getenv('PESAPAL_DONATION_MAX') ?: 0);
if ($minAmount > 0 && $amount < $minAmount) {
    redirect_with_error('Minimum donation amount is ' . $minAmount . '.');
}
if ($maxAmount > 0 && $amount > $maxAmount) {
    redirect_with_error('Maximum donation amount is ' . $maxAmount . '.');
}

$currency = strtoupper((string) (getenv('PESAPAL_DEFAULT_CURRENCY') ?: 'RWF'));
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$purpose = trim((string) ($_POST['purpose'] ?? 'Donation'));

if ($email === '' && $phone === '') {
    redirect_with_error('Please provide at least an email or phone number.');
}

$notificationId = trim((string) (getenv('PESAPAL_IPN_ID') ?: ''));
if ($notificationId === '') {
    redirect_with_error('Payment is not configured yet (missing IPN ID).');
}

$merchantReference = 'DON-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
$callbackUrl = current_origin() . '/pesapal_callback.php';

$order = [
    'id' => $merchantReference,
    'currency' => $currency,
    'amount' => round($amount, 2),
    'description' => $purpose !== '' ? $purpose : 'Donation',
    'callback_url' => $callbackUrl,
    'notification_id' => $notificationId,
    'branch' => '1000 Hills Rugby',
    'billing_address' => [
        'email_address' => $email !== '' ? $email : null,
        'phone_number' => $phone !== '' ? $phone : null,
        'first_name' => $name !== '' ? $name : null
    ]
];

// Remove nulls (Pesapal is strict on payload fields)
$order['billing_address'] = array_filter($order['billing_address'], fn($v) => $v !== null);
if (count($order['billing_address']) === 0) {
    unset($order['billing_address']);
}

pesapal_record_init([
    'merchant_reference' => $merchantReference,
    'amount' => $order['amount'],
    'currency' => $currency,
    'donor_name' => $name,
    'email' => $email,
    'phone' => $phone,
    'purpose' => $purpose,
    'status_description' => 'Initiated'
]);

try {
    $token = pesapal_request_token();

    $url = pesapal_base_url() . '/Transactions/SubmitOrderRequest';
    $payload = json_encode($order, JSON_UNESCAPED_SLASHES);

    $resp = pesapal_http_json('POST', $url, $payload, [
        'Authorization: Bearer ' . $token
    ]);

    if (!isset($resp['redirect_url'], $resp['order_tracking_id'])) {
        redirect_with_error('Failed to initiate payment.');
    }

    // Store minimal context in session so callback page can display something
    $_SESSION['pesapal_last_order'] = [
        'order_tracking_id' => (string) $resp['order_tracking_id'],
        'merchant_reference' => (string) ($resp['merchant_reference'] ?? $merchantReference),
        'amount' => $order['amount'],
        'currency' => $currency
    ];

    pesapal_record_status(
        (string) $resp['order_tracking_id'],
        (string) ($resp['merchant_reference'] ?? $merchantReference),
        ['payment_status_description' => 'Submitted']
    );

    header('Location: ' . (string) $resp['redirect_url'], true, 302);
    exit;

} catch (Throwable $e) {
    error_log('Pesapal init error: ' . $e->getMessage());
    if (is_debug_enabled()) {
        redirect_with_error('Pesapal error: ' . $e->getMessage());
    }
    redirect_with_error('Could not start payment. Please try again later.');
}
