<?php
declare(strict_types=1);

require_once __DIR__ . '/pesapal_bootstrap.php';

$err = '';
$amount = '';
$email = '';
$phone = '';
$name = '';
$currency = (string) (getenv('PESAPAL_DEFAULT_CURRENCY') ?: 'RWF');

if (isset($_GET['err'])) {
    $err = (string) $_GET['err'];
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Donate - 1000 Hills Rugby</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
  <div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white rounded-2xl shadow p-6">
      <h1 class="text-2xl font-extrabold text-gray-900">Donate</h1>
      <p class="text-sm text-gray-600 mt-1">Support 1000 Hills Rugby securely via mobile money or card.</p>

      <?php if ($err !== ''): ?>
        <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
          <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form class="mt-6 grid gap-4" method="post" action="pesapal_init.php" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />

        <div>
          <label class="block text-sm font-semibold text-gray-700" for="amount">Amount (<?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?>)</label>
          <input id="amount" name="amount" required inputmode="decimal" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="e.g. 10000" />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700" for="name">Full name</label>
          <input id="name" name="name" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Your name" />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700" for="email">Email</label>
          <input id="email" name="email" type="email" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="you@example.com" />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700" for="phone">Phone</label>
          <input id="phone" name="phone" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="e.g. 2507xxxxxxxx" />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700" for="purpose">Purpose (optional)</label>
          <input id="purpose" name="purpose" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="Donation" />
        </div>

        <div class="pt-2 flex items-center justify-end gap-3">
          <a href="/" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
          <button type="submit" class="rounded-lg bg-green-700 text-white px-5 py-2 font-extrabold hover:bg-green-800">Continue to payment</button>
        </div>
      </form>

      <div class="mt-6 text-xs text-gray-500">
        Payment methods (MTN, Airtel Money, Visa, Mastercard) are shown by Pesapal based on what is enabled on your Pesapal merchant account.
      </div>
    </div>
  </div>
</body>
</html>
