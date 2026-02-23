<?php
declare(strict_types=1);

require_once __DIR__ . '/pesapal_bootstrap.php';

$err = '';
$amount = '';
$email = '';
$phone = '';
$name = '';
$currency = (string) (getenv('PESAPAL_DEFAULT_CURRENCY') ?: 'RWF');
$minAmount = (float) (getenv('PESAPAL_DONATION_MIN') ?: 0);
$maxAmount = (float) (getenv('PESAPAL_DONATION_MAX') ?: 0);

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
          <input id="amount" name="amount" required inputmode="decimal" min="<?php echo htmlspecialchars((string)$minAmount, ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo htmlspecialchars((string)$maxAmount, ENT_QUOTES, 'UTF-8'); ?>" step="1" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="e.g. 10000" />
          <?php if ($minAmount > 0 || $maxAmount > 0): ?>
            <div class="mt-1 text-xs text-gray-500">
              Limits: <?php echo htmlspecialchars((string)$minAmount, ENT_QUOTES, 'UTF-8'); ?> to <?php echo htmlspecialchars((string)$maxAmount, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($currency, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>
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

        <div class="text-xs text-gray-500">Please provide at least one: email or phone.</div>

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

      <div class="mt-5">
        <div class="text-sm font-extrabold text-gray-900">Supported payment methods</div>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="rounded-xl border border-gray-200 bg-white p-3 flex items-center justify-center">
            <svg viewBox="0 0 220 70" class="h-7" aria-label="MTN Mobile Money" role="img">
              <rect x="2" y="2" width="216" height="66" rx="14" fill="#FFCC00" stroke="#111827" stroke-width="2" />
              <text x="110" y="44" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="800" fill="#111827">MTN MoMo</text>
            </svg>
          </div>
          <div class="rounded-xl border border-gray-200 bg-white p-3 flex items-center justify-center">
            <svg viewBox="0 0 220 70" class="h-7" aria-label="Airtel Money" role="img">
              <rect x="2" y="2" width="216" height="66" rx="14" fill="#E11D48" stroke="#111827" stroke-width="2" />
              <text x="110" y="44" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="800" fill="#FFFFFF">Airtel</text>
              <text x="186" y="44" text-anchor="end" font-family="Arial, sans-serif" font-size="18" font-weight="800" fill="#FFFFFF">Money</text>
            </svg>
          </div>
          <div class="rounded-xl border border-gray-200 bg-white p-3 flex items-center justify-center">
            <svg viewBox="0 0 220 70" class="h-7" aria-label="Visa" role="img">
              <rect x="2" y="2" width="216" height="66" rx="14" fill="#FFFFFF" stroke="#111827" stroke-width="2" />
              <text x="110" y="46" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" font-weight="900" fill="#1A1F71">VISA</text>
            </svg>
          </div>
          <div class="rounded-xl border border-gray-200 bg-white p-3 flex items-center justify-center">
            <svg viewBox="0 0 220 70" class="h-7" aria-label="Mastercard" role="img">
              <rect x="2" y="2" width="216" height="66" rx="14" fill="#FFFFFF" stroke="#111827" stroke-width="2" />
              <circle cx="98" cy="35" r="18" fill="#EB001B" />
              <circle cx="122" cy="35" r="18" fill="#F79E1B" fill-opacity="0.95" />
              <text x="110" y="62" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="800" fill="#111827">mastercard</text>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
