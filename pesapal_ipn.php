<?php
declare(strict_types=1);

require_once __DIR__ . '/pesapal_bootstrap.php';

header('Content-Type: application/json');

// IPN can be GET or POST depending on what you choose when registering the IPN URL.
$payload = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '', true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (!$payload) {
    $payload = $_GET;
}

$orderTrackingId = isset($payload['OrderTrackingId']) ? (string) $payload['OrderTrackingId'] : '';
$orderMerchantReference = isset($payload['OrderMerchantReference']) ? (string) $payload['OrderMerchantReference'] : '';
$orderNotificationType = isset($payload['OrderNotificationType']) ? (string) $payload['OrderNotificationType'] : '';

try {
    if ($orderTrackingId === '') {
        throw new RuntimeException('Missing OrderTrackingId');
    }

    $token = pesapal_request_token();
    $url = pesapal_base_url() . '/Transactions/GetTransactionStatus?orderTrackingId=' . rawurlencode($orderTrackingId);
    $details = pesapal_http_json('GET', $url, null, [
        'Authorization: Bearer ' . $token
    ]);

    if (is_array($details)) {
        pesapal_record_status($orderTrackingId, $orderMerchantReference, $details);
    }

    echo json_encode([
        'orderNotificationType' => $orderNotificationType,
        'orderTrackingId' => $orderTrackingId,
        'orderMerchantReference' => $orderMerchantReference,
        'status' => 200
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (Throwable $e) {
    error_log('Pesapal IPN error: ' . $e->getMessage());

    echo json_encode([
        'orderNotificationType' => $orderNotificationType,
        'orderTrackingId' => $orderTrackingId,
        'orderMerchantReference' => $orderMerchantReference,
        'status' => 500
    ], JSON_UNESCAPED_SLASHES);
    exit;
}
