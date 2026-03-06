<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
pesapal_load_env();

header('Content-Type: application/json; charset=utf-8');

$orderTrackingId = '';
if (isset($_GET['OrderTrackingId'])) $orderTrackingId = (string) $_GET['OrderTrackingId'];
if (isset($_GET['orderTrackingId'])) $orderTrackingId = (string) $_GET['orderTrackingId'];
if (isset($_POST['OrderTrackingId'])) $orderTrackingId = (string) $_POST['OrderTrackingId'];
if (isset($_POST['orderTrackingId'])) $orderTrackingId = (string) $_POST['orderTrackingId'];

$orderMerchantReference = '';
if (isset($_GET['OrderMerchantReference'])) $orderMerchantReference = (string) $_GET['OrderMerchantReference'];
if (isset($_GET['orderMerchantReference'])) $orderMerchantReference = (string) $_GET['orderMerchantReference'];
if (isset($_POST['OrderMerchantReference'])) $orderMerchantReference = (string) $_POST['OrderMerchantReference'];
if (isset($_POST['orderMerchantReference'])) $orderMerchantReference = (string) $_POST['orderMerchantReference'];

try {
    if ($orderTrackingId !== '') {
        $token = pesapal_request_token();
        pesapal_get_transaction_status($token, $orderTrackingId);
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
