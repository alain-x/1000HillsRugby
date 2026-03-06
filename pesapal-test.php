<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
pesapal_load_env();

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>1000 Hills Rugby | Test Pesapal Payment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="./images/t_icon.png" type="image/png" />
</head>
<body class="bg-gray-50">
  <div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900">Test Pesapal Payment</h1>
          <p class="text-sm text-gray-600 mt-1">This is a test page that initiates a Pesapal checkout from your server.</p>
        </div>
        <a class="text-sm font-semibold text-green-700 hover:text-green-800" href="./">Home</a>
      </div>

      <div class="mt-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
        <div class="font-bold">Important</div>
        <div class="mt-1">Your Pesapal consumer secret must be stored in <code class="font-mono">.env</code>. It is never sent to the browser.</div>
      </div>

      <form class="mt-6 grid gap-4" method="POST" action="<?php echo htmlspecialchars(pesapal_base_url() . '/pesapal-initiate.php', ENT_QUOTES, 'UTF-8'); ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">Amount</label>
            <input name="amount" type="number" step="0.01" min="1" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" value="1000" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Currency</label>
            <select name="currency" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600">
              <option value="RWF" selected>RWF</option>
              <option value="KES">KES</option>
              <option value="UGX">UGX</option>
              <option value="TZS">TZS</option>
              <option value="USD">USD</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700">Description</label>
          <input name="description" type="text" maxlength="100" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" value="1000 Hills Rugby - Test payment" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">First name</label>
            <input name="first_name" type="text" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" value="Test" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Last name</label>
            <input name="last_name" type="text" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" value="Customer" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">Email</label>
            <input name="email" type="email" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" value="test@example.com" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Phone</label>
            <input name="phone" type="text" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" value="+250700000000" />
          </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
          <div class="text-xs text-gray-500">Callback: <code class="font-mono"><?php echo htmlspecialchars(pesapal_base_url() . '/pesapal-callback.php', ENT_QUOTES, 'UTF-8'); ?></code></div>
          <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-green-700 px-5 py-3 text-white font-bold hover:bg-green-800">Start Pesapal Payment</button>
        </div>
      </form>

      <div class="mt-6 text-xs text-gray-500">
        Environment: <code class="font-mono"><?php echo htmlspecialchars((string) pesapal_env('PESAPAL_ENV', 'sandbox'), ENT_QUOTES, 'UTF-8'); ?></code>
      </div>
    </div>
  </div>
</body>
</html>
