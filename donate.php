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
<body class="bg-gray-50">
  <div class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900">Donate</h1>
          <p class="text-sm text-gray-600 mt-1">Support 1000 Hills Rugby. Secure payments are processed by Pesapal.</p>
        </div>
        <a class="text-sm font-semibold text-green-700 hover:text-green-800" href="./">Home</a>
      </div>

      <form class="mt-6 grid gap-4" method="POST" action="./donate-initiate.php">
        <input type="hidden" name="csrf" value="<?php echo h((string)($_SESSION['donate_csrf'] ?? '')); ?>" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">Amount</label>
            <input name="amount" type="number" step="0.01" min="1" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" value="5000" />
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">First name</label>
            <input name="first_name" type="text" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Last name</label>
            <input name="last_name" type="text" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700">Email</label>
            <input name="email" type="email" required class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700">Phone</label>
            <input name="phone" type="text" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" placeholder="+2507XXXXXXXX" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700">Message (optional)</label>
          <input name="message" type="text" maxlength="120" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-600 focus:ring-green-600" placeholder="e.g. Youth program support" />
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
          <div class="text-xs text-gray-500">Secure checkout via Pesapal</div>
          <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-green-700 px-5 py-3 text-white font-bold hover:bg-green-800">Donate now</button>
        </div>
      </form>

      <div class="mt-6 text-xs text-gray-500">
        Environment: <code class="font-mono"><?php echo h((string) pesapal_env('PESAPAL_ENV', 'sandbox')); ?></code>
      </div>
    </div>
  </div>
</body>
</html>
