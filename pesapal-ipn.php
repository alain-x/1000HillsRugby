<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
if (file_exists(__DIR__ . '/donation-store.php')) {
    require_once __DIR__ . '/donation-store.php';
}
pesapal_load_env();

header('Content-Type: application/json; charset=utf-8');

$rawBody = file_get_contents('php://input');
$jsonBody = null;
if (is_string($rawBody) && trim($rawBody) !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $jsonBody = $decoded;
    }
}

$orderTrackingId = '';
if (isset($_GET['OrderTrackingId'])) $orderTrackingId = (string) $_GET['OrderTrackingId'];
if (isset($_GET['orderTrackingId'])) $orderTrackingId = (string) $_GET['orderTrackingId'];
if (isset($_POST['OrderTrackingId'])) $orderTrackingId = (string) $_POST['OrderTrackingId'];
if (isset($_POST['orderTrackingId'])) $orderTrackingId = (string) $_POST['orderTrackingId'];
if ($orderTrackingId === '' && is_array($jsonBody)) {
    if (isset($jsonBody['OrderTrackingId'])) $orderTrackingId = (string) $jsonBody['OrderTrackingId'];
    if (isset($jsonBody['orderTrackingId'])) $orderTrackingId = (string) $jsonBody['orderTrackingId'];
}

$orderMerchantReference = '';
if (isset($_GET['OrderMerchantReference'])) $orderMerchantReference = (string) $_GET['OrderMerchantReference'];
if (isset($_GET['orderMerchantReference'])) $orderMerchantReference = (string) $_GET['orderMerchantReference'];
if (isset($_POST['OrderMerchantReference'])) $orderMerchantReference = (string) $_POST['OrderMerchantReference'];
if (isset($_POST['orderMerchantReference'])) $orderMerchantReference = (string) $_POST['orderMerchantReference'];
if ($orderMerchantReference === '' && is_array($jsonBody)) {
    if (isset($jsonBody['OrderMerchantReference'])) $orderMerchantReference = (string) $jsonBody['OrderMerchantReference'];
    if (isset($jsonBody['orderMerchantReference'])) $orderMerchantReference = (string) $jsonBody['orderMerchantReference'];
}

try {
    if ($orderTrackingId !== '') {
        $token = pesapal_request_token();
        $status = pesapal_get_transaction_status($token, $orderTrackingId);

        if (function_exists('donation_update_by_tracking_id') && is_array($status)) {
            $desc = (string)($status['payment_status_description'] ?? '');
            donation_update_by_tracking_id($orderTrackingId, [
                'payment_status_description' => $desc,
                'payment_method' => (string)($status['payment_method'] ?? ''),
                'confirmation_code' => (string)($status['confirmation_code'] ?? ''),
                'status' => $desc !== '' ? $desc : 'UPDATED',
                'updated_at' => gmdate('c'),
            ]);
        }
    }

    echo json_encode([
        'orderNotificationType' => 'IPNCHANGE',
        'orderTrackingId' => $orderTrackingId,
        'orderMerchantReference' => $orderMerchantReference,
        'status' => 200,
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'orderNotificationType' => 'IPNCHANGE',
        'orderTrackingId' => $orderTrackingId,
        'orderMerchantReference' => $orderMerchantReference,
        'status' => 500,
        'message' => $e->getMessage(),
    ]);
    exit;
}
