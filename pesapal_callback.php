<?php
declare(strict_types=1);

require_once __DIR__ . '/pesapal_bootstrap.php';

$orderTrackingId = isset($_GET['OrderTrackingId']) ? (string) $_GET['OrderTrackingId'] : '';
$merchantReference = isset($_GET['OrderMerchantReference']) ? (string) $_GET['OrderMerchantReference'] : '';

$details = null;
$error = '';

if ($orderTrackingId !== '') {
    try {
        $token = pesapal_request_token();
        $url = pesapal_base_url() . '/Transactions/GetTransactionStatus?orderTrackingId=' . rawurlencode($orderTrackingId);
        $details = pesapal_http_json('GET', $url, null, [
            'Authorization: Bearer ' . $token
        ]);

        if (is_array($details)) {
            pesapal_record_status($orderTrackingId, $merchantReference, $details);
        }
    } catch (Throwable $e) {
        error_log('Pesapal callback status error: ' . $e->getMessage());
        $error = 'Unable to confirm payment status yet. Please refresh in a moment.';
    }
} else {
    $error = 'Missing payment reference.';
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Donation Status - 1000 Hills Rugby</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
  <div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow p-6">
      <h1 class="text-2xl font-extrabold text-gray-900">Donation status</h1>

      <?php if ($error !== ''): ?>
        <div class="mt-4 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-900 text-sm">
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <div class="mt-6 grid gap-2 text-sm text-gray-700">
        <div><span class="font-semibold">Order Tracking ID:</span> <?php echo htmlspecialchars($orderTrackingId, ENT_QUOTES, 'UTF-8'); ?></div>
        <div><span class="font-semibold">Merchant Reference:</span> <?php echo htmlspecialchars($merchantReference, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      <?php if (is_array($details)): ?>
        <div class="mt-6 rounded-xl border border-gray-200 p-4">
          <div class="text-sm text-gray-700"><span class="font-semibold">Status:</span> <?php echo htmlspecialchars((string)($details['payment_status_description'] ?? ($details['message'] ?? 'Unknown')), ENT_QUOTES, 'UTF-8'); ?></div>
          <?php if (isset($details['payment_method'])): ?>
            <div class="text-sm text-gray-700 mt-1"><span class="font-semibold">Method:</span> <?php echo htmlspecialchars((string)$details['payment_method'], ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
          <?php if (isset($details['amount'], $details['currency'])): ?>
            <div class="text-sm text-gray-700 mt-1"><span class="font-semibold">Amount:</span> <?php echo htmlspecialchars((string)$details['amount'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string)$details['currency'], ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="mt-6 flex items-center justify-end gap-3">
        <a class="text-sm font-semibold text-gray-600 hover:text-gray-900" href="donate.php">Make another donation</a>
        <a class="rounded-lg bg-green-700 text-white px-5 py-2 font-extrabold hover:bg-green-800" href="/">Back to site</a>
      </div>
    </div>
  </div>
</body>
</html>
