<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
require_once __DIR__ . '/donation-store.php';

pesapal_load_env();

$host = $_SERVER['HTTP_HOST'] ?? '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($host !== '' && stripos($host, 'www.') !== 0) {
  $path = $_SERVER['REQUEST_URI'] ?? '/donate.php';
  $scheme = $isHttps ? 'https' : 'http';
  header('Location: ' . $scheme . '://www.' . $host . $path, true, 301);
  exit;
}

session_start();
if (empty($_SESSION['donate_csrf'])) {
    $_SESSION['donate_csrf'] = bin2hex(random_bytes(16));
}

header('Content-Type: text/html; charset=utf-8');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>1000 Hills Rugby | Donate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="./images/t_icon.png" type="image/png" />
</head>
<body class="bg-gradient-to-b from-gray-50 to-white">
  <div class="max-w-3xl mx-auto px-4 py-10 sm:py-12">
    <div class="rounded-3xl border border-gray-100 bg-white shadow-lg overflow-hidden">
      <div class="px-6 py-6 sm:px-8 sm:py-8 bg-gradient-to-r from-green-800 to-green-700">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-white/90 text-xs font-extrabold tracking-widest uppercase">1000 Hills Rugby</div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-white">Make a donation</h1>
            <p class="mt-2 text-sm text-white/90 max-w-xl">Support our programs. Payments are securely processed by Pesapal.</p>
          </div>
          <a class="text-sm font-semibold text-white/90 hover:text-white" href="./">Home</a>
        </div>
      </div>

      <div class="px-6 py-6 sm:px-8 sm:py-8">
      <form class="grid gap-5" method="POST" action="./donate-initiate.php">
        <input type="hidden" name="csrf" value="<?php echo h((string)($_SESSION['donate_csrf'] ?? '')); ?>" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">Amount</label>
            <div class="mt-1 grid gap-2">
              <div class="grid grid-cols-4 gap-2">
                <button type="button" data-amount="2000" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-800 hover:bg-gray-50">2k</button>
                <button type="button" data-amount="5000" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-800 hover:bg-gray-50">5k</button>
                <button type="button" data-amount="10000" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-800 hover:bg-gray-50">10k</button>
                <button type="button" data-amount="20000" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-800 hover:bg-gray-50">20k</button>
              </div>
              <input id="donate-amount" name="amount" inputmode="decimal" type="number" step="0.01" min="1" required class="w-full rounded-xl border-gray-300 focus:border-green-700 focus:ring-green-700" value="5000" />
              <div class="text-xs text-gray-500">Enter any amount you want to give.</div>
            </div>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Currency</label>
            <select name="currency" class="mt-1 w-full rounded-xl border-gray-300 focus:border-green-700 focus:ring-green-700">
              <option value="RWF" selected>RWF</option>
              <option value="KES">KES</option>
              <option value="UGX">UGX</option>
              <option value="TZS">TZS</option>
              <option value="USD">USD</option>
            </select>
            <div class="mt-2 text-xs text-gray-500">The final payment method options depend on what Pesapal has enabled for your account.</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">First name</label>
            <input name="first_name" autocomplete="given-name" type="text" required class="mt-1 w-full rounded-xl border-gray-300 focus:border-green-700 focus:ring-green-700" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Last name</label>
            <input name="last_name" autocomplete="family-name" type="text" required class="mt-1 w-full rounded-xl border-gray-300 focus:border-green-700 focus:ring-green-700" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">Email</label>
            <input name="email" autocomplete="email" inputmode="email" type="email" required class="mt-1 w-full rounded-xl border-gray-300 focus:border-green-700 focus:ring-green-700" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Phone</label>
            <input name="phone" autocomplete="tel" inputmode="tel" type="tel" class="mt-1 w-full rounded-xl border-gray-300 focus:border-green-700 focus:ring-green-700" placeholder="+2507XXXXXXXX" />
            <div class="mt-2 text-xs text-gray-500">Use a number that can receive payment prompts (if mobile money is enabled).</div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700">Message (optional)</label>
          <input name="message" type="text" maxlength="120" class="mt-1 w-full rounded-xl border-gray-300 focus:border-green-700 focus:ring-green-700" placeholder="e.g. Youth program support" />
        </div>

        <div class="grid gap-3 pt-2">
          <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700">
            You’ll be redirected to Pesapal to complete payment.
          </div>
          <button type="submit" class="w-full inline-flex items-center justify-center rounded-2xl bg-green-700 px-5 py-4 text-white font-extrabold hover:bg-green-800 active:scale-[0.99]">Donate securely</button>
          <div class="text-[11px] text-gray-500">By continuing, you agree to Pesapal’s terms during checkout.</div>
        </div>
      </form>

      <div class="mt-6 text-xs text-gray-500">
        Environment: <code class="font-mono"><?php echo h((string) pesapal_env('PESAPAL_ENV', 'sandbox')); ?></code>
      </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var amountInput = document.getElementById('donate-amount');
      if (!amountInput) return;
      var buttons = document.querySelectorAll('button[data-amount]');
      for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function (e) {
          var v = e.currentTarget.getAttribute('data-amount');
          if (!v) return;
          amountInput.value = v;
          amountInput.focus();
        });
      }
    })();
  </script>
</body>
</html>
