<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
require_once __DIR__ . '/donation-store.php';

pesapal_load_env();

header('Content-Type: text/html; charset=utf-8');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$orderTrackingId = '';
if (isset($_GET['OrderTrackingId'])) $orderTrackingId = (string) $_GET['OrderTrackingId'];
if (isset($_GET['orderTrackingId'])) $orderTrackingId = (string) $_GET['orderTrackingId'];

$orderMerchantReference = '';
if (isset($_GET['OrderMerchantReference'])) $orderMerchantReference = (string) $_GET['OrderMerchantReference'];
if (isset($_GET['orderMerchantReference'])) $orderMerchantReference = (string) $_GET['orderMerchantReference'];

$status = null;
$error = null;

if ($orderTrackingId !== '') {
    try {
        $token = pesapal_request_token();
        $status = pesapal_get_transaction_status($token, $orderTrackingId);

        if (is_array($status)) {
            $desc = (string)($status['payment_status_description'] ?? '');
            donation_update_by_tracking_id($orderTrackingId, [
                'payment_status_description' => $desc,
                'payment_method' => (string)($status['payment_method'] ?? ''),
                'confirmation_code' => (string)($status['confirmation_code'] ?? ''),
                'status' => $desc !== '' ? $desc : 'UPDATED',
                'updated_at' => gmdate('c'),
            ]);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>1000 Hills Rugby | Donation Status</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="./images/t_icon.png" type="image/png" />
</head>
<body class="bg-gray-50">
  <div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900">Donation Status</h1>
          <p class="text-sm text-gray-600 mt-1">Thank you for supporting 1000 Hills Rugby.</p>
        </div>
        <a class="text-sm font-semibold text-green-700 hover:text-green-800" href="./donate.php">New donation</a>
      </div>

      <div class="mt-6 grid gap-3 text-sm">
        <div class="flex justify-between gap-4"><div class="text-gray-500">OrderTrackingId</div><div class="font-mono text-right break-all"><?php echo h($orderTrackingId); ?></div></div>
        <div class="flex justify-between gap-4"><div class="text-gray-500">Merchant reference</div><div class="font-mono text-right break-all"><?php echo h($orderMerchantReference); ?></div></div>
      </div>

      <?php if ($orderTrackingId === ''): ?>
        <div class="mt-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
          Missing <code class="font-mono">OrderTrackingId</code> in the callback URL.
        </div>
      <?php elseif ($error !== null): ?>
        <div class="mt-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-sm">
          <?php echo h($error); ?>
        </div>
      <?php elseif (is_array($status)): ?>
        <div class="mt-6 p-4 rounded-xl bg-gray-50 border border-gray-200">
          <div class="text-sm font-bold text-gray-900">Transaction status</div>
          <div class="mt-3 grid gap-2 text-sm">
            <div class="flex justify-between gap-4"><div class="text-gray-500">Payment status</div><div class="font-semibold text-right"><?php echo h((string)($status['payment_status_description'] ?? '')); ?></div></div>
            <div class="flex justify-between gap-4"><div class="text-gray-500">Amount</div><div class="font-mono text-right"><?php echo h((string)($status['amount'] ?? '')); ?> <?php echo h((string)($status['currency'] ?? '')); ?></div></div>
            <div class="flex justify-between gap-4"><div class="text-gray-500">Method</div><div class="font-mono text-right"><?php echo h((string)($status['payment_method'] ?? '')); ?></div></div>
            <div class="flex justify-between gap-4"><div class="text-gray-500">Confirmation</div><div class="font-mono text-right break-all"><?php echo h((string)($status['confirmation_code'] ?? '')); ?></div></div>
          </div>
        </div>
      <?php endif; ?>

      <div class="mt-6 text-xs text-gray-500">
        If you have any issue, email <a class="text-green-700 font-bold" href="mailto:info@1000hillsrugby.rw">info@1000hillsrugby.rw</a>.
      </div>
    </div>
  </div>
</body>
</html>
