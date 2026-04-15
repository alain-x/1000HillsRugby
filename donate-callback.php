<?php

declare(strict_types=1);

require_once __DIR__ . '/pesapal-lib.php';
require_once __DIR__ . '/donation-store.php';

pesapal_load_env();

// Keep canonical host consistent (www)
$host = $_SERVER['HTTP_HOST'] ?? '';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if ($host !== '' && stripos($host, 'www.') !== 0) {
    $path = $_SERVER['REQUEST_URI'] ?? '/donate-callback.php';
    $scheme = $isHttps ? 'https' : 'http';
    header('Location: ' . $scheme . '://www.' . $host . $path, true, 301);
    exit;
}

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
  <link rel="stylesheet" href="./style.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="./images/t_icon.png" type="image/png" />
  <style>
    #menu-toggle:checked ~ #menu {
      display: block;
    }
    #menu:hover {
      display: block;
    }
  </style>
</head>
<body class="bg-gradient-to-b from-gray-50 to-white">
  <nav
    class="navbar fixed top-0 left-0 w-full px-2 z-20 h-[10vh] flex flex-wrap justify-between items-center py-2 bg-white/90 backdrop-blur-lg shadow-lg transition-all duration-300"
  >
    <div class="navbar-logo w-2/12">
      <a href="./">
        <img
          class="w-[60px] hover:w-[70px] transition-transform duration-300"
          src="./images/1000-hills-logo.png"
          alt="1000 Hills Rugby"
        />
      </a>
    </div>

    <ul
      class="hidden lg:flex lg:space-x-8 font-600 text-gray-800 text-sm tracking-wider"
    >
      <li>
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./"
          >Home</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./about"
          >About</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./program"
          >Programs</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./community"
          >Community</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./shop"
          >Shop</a
        >
      </li>

      <li>
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./teams"
          >Teams</a
        >
      </li>
      <li class="relative group">
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none"
          >Education<i class="fas fa-chevron-down text-sm"></i
        ></a>
        <ul
          class="absolute left-0 hidden group-hover:block bg-white text-gray-800 text-sm shadow-md"
        >
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./education"
              >1HR Education Program</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 w-[180px] hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./foundation"
              >Career Foundation</a
            >
          </li>
        </ul>
      </li>
      <li class="relative group">
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none"
          >Events<i class="fas fa-chevron-down text-sm"></i
        ></a>
        <ul
          class="absolute left-0 hidden group-hover:block bg-white text-gray-800 text-sm shadow-md"
        >
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./events"
              >Events</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 w-[150px] hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./news"
              >News & Media</a
            >
          </li>
        </ul>
      </li>

      <li class="relative group">
        <a
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none mr-10"
          >Contact<i class="fas fa-chevron-down text-sm"></i
        ></a>
        <ul
          class="absolute left-0 hidden group-hover:block bg-white text-gray-800 text-sm shadow-md"
        >
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./staff"
              >Staff</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./contact"
              >Contact us</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./register#reForm"
              >Register</a
            >
          </li>
        </ul>
      </li>
    </ul>

    <div class="relative lg:hidden flex flex-wrap items-center">
      <input type="checkbox" id="menu-toggle" class="hidden" />
      <label for="menu-toggle" class="cursor-pointer text-2xl text-black">
        <i class="fa-solid fa-bars" id="menu-open-icon"></i>
        <i class="fa-solid fa-times hidden" id="menu-close-icon"></i>
      </label>

      <div
        id="menu"
        class="absolute top-full right-0 bg-white text-gray-800 w-48 mt-2 rounded-md shadow-lg hidden transition-all duration-300"
      >
        <ul class="flex flex-col text-left space-y-1">
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./"
              >Home</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./about"
              >About</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./program"
              >Programs</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./community"
              >Community</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./shop"
              >Shop</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./teams"
              >Teams</a
            >
          </li>

          <li class="relative">
            <button
              class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
              type="button"
              data-dropdown-toggle="education-menu-mobile"
            >
              Education <i class="fas fa-chevron-down text-sm"></i>
            </button>
            <ul
              id="education-menu-mobile"
              class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
            >
              <li>
                <a
                  class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                  href="./education"
                  >1HR Education Program</a
                >
              </li>
              <li>
                <a
                  class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                  href="./foundation"
                  >Career Foundation</a
                >
              </li>
            </ul>
          </li>

          <li class="relative">
            <button
              class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
              type="button"
              data-dropdown-toggle="events-menu-mobile"
            >
              Events <i class="fas fa-chevron-down text-sm"></i>
            </button>
            <ul
              id="events-menu-mobile"
              class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
            >
              <li>
                <a
                  class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                  href="./events"
                  >Events</a
                >
              </li>
              <li>
                <a
                  class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                  href="./news"
                  >News & Media</a
                >
              </li>
            </ul>
          </li>

          <li class="relative">
            <button
              class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
              type="button"
              data-dropdown-toggle="contact-menu-mobile"
            >
              Contact <i class="fas fa-chevron-down text-sm"></i>
            </button>
            <ul
              id="contact-menu-mobile"
              class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
            >
              <li>
                <a
                  class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                  href="./staff"
                  >Staff</a
                >
              </li>
              <li>
                <a
                  class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                  href="./contact"
                  >Contact Us</a
                >
              </li>
              <li>
                <a
                  class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                  href="./register#reForm"
                  >Register</a
                >
              </li>
            </ul>
          </li>

          <li>
            <a class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300" href="./donate.php#donate">Donate</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="max-w-2xl mx-auto px-4 py-10 sm:py-12 pt-[10vh]">
    <div class="rounded-3xl border border-gray-100 bg-white shadow-lg overflow-hidden">
      <div class="px-6 py-6 sm:px-8 sm:py-8 bg-gradient-to-r from-green-800 to-green-700">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-white/90 text-xs font-extrabold tracking-widest uppercase">1000 Hills Rugby</div>
            <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-white">Donation status</h1>
            <p class="mt-2 text-sm text-white/90">Thank you for your support.</p>
          </div>
          <a class="text-sm font-semibold text-white/90 hover:text-white" href="./donate.php">New donation</a>
        </div>
      </div>

      <div class="px-6 py-6 sm:px-8 sm:py-8">

      <div class="grid gap-3 text-sm">
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
          <div class="grid gap-2">
            <div class="flex justify-between gap-4"><div class="text-gray-500">OrderTrackingId</div><div class="font-mono text-right break-all"><?php echo h($orderTrackingId); ?></div></div>
            <div class="flex justify-between gap-4"><div class="text-gray-500">Merchant reference</div><div class="font-mono text-right break-all"><?php echo h($orderMerchantReference); ?></div></div>
          </div>
        </div>
      </div>

      <?php if ($orderTrackingId === ''): ?>
        <div class="mt-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
          Missing <code class="font-mono">OrderTrackingId</code> in the callback URL.
        </div>
      <?php elseif ($error !== null): ?>
        <div class="mt-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-sm">
          <?php echo h($error); ?>
        </div>
      <?php elseif (is_array($status)): ?>
        <?php
          $paymentDesc = (string)($status['payment_status_description'] ?? '');
          $badgeClass = 'bg-gray-100 text-gray-800 border-gray-200';
          $descLower = strtolower($paymentDesc);
          if (str_contains($descLower, 'complete') || str_contains($descLower, 'paid') || str_contains($descLower, 'success')) {
              $badgeClass = 'bg-green-50 text-green-800 border-green-200';
          } elseif (str_contains($descLower, 'fail') || str_contains($descLower, 'cancel') || str_contains($descLower, 'invalid')) {
              $badgeClass = 'bg-red-50 text-red-800 border-red-200';
          } elseif (str_contains($descLower, 'pending') || str_contains($descLower, 'processing')) {
              $badgeClass = 'bg-amber-50 text-amber-800 border-amber-200';
          }
        ?>

        <div class="mt-6 grid gap-4">
          <div class="rounded-2xl border <?php echo h($badgeClass); ?> px-4 py-3">
            <div class="text-xs font-extrabold uppercase tracking-widest">Payment status</div>
            <div class="mt-1 text-lg font-extrabold"><?php echo h($paymentDesc); ?></div>
          </div>

          <div class="rounded-2xl border border-gray-200 bg-white p-4">
            <div class="text-sm font-extrabold text-gray-900">Transaction details</div>
            <div class="mt-3 grid gap-2 text-sm">
              <div class="flex justify-between gap-4"><div class="text-gray-500">Amount</div><div class="font-mono text-right"><?php echo h((string)($status['amount'] ?? '')); ?> <?php echo h((string)($status['currency'] ?? '')); ?></div></div>
              <div class="flex justify-between gap-4"><div class="text-gray-500">Method</div><div class="font-mono text-right"><?php echo h((string)($status['payment_method'] ?? '')); ?></div></div>
              <div class="flex justify-between gap-4"><div class="text-gray-500">Confirmation</div><div class="font-mono text-right break-all"><?php echo h((string)($status['confirmation_code'] ?? '')); ?></div></div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700">
        If you have any issue, email <a class="text-green-800 font-extrabold" href="mailto:info@1000hillsrugby.rw">info@1000hillsrugby.rw</a> and include your merchant reference.
      </div>
      </div>
    </div>
  </div>

  <script src="./donate-page.js"></script>
</body>
</html>
