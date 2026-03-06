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
<body class="bg-white text-gray-900">
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
                href="./Foundation"
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
                    href="./Foundation"
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
              <a class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300" href="#donate">Donate</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

  <main>
    <section class="bg-gradient-to-b from-gray-50 to-white pt-[10vh]">
      <div class="max-w-6xl mx-auto px-4 py-10 sm:py-14">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-10 items-stretch">
          <div class="rounded-3xl overflow-hidden border border-gray-100 bg-white shadow-sm">
            <div class="relative">
              <img src="./images/aboutImage.jpeg" alt="1000 Hills Rugby" class="w-full h-[240px] sm:h-[340px] object-cover" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent"></div>
              <div class="absolute bottom-0 left-0 right-0 p-5 sm:p-6">
                <div class="text-white text-3xl sm:text-4xl font-extrabold leading-tight">Support our players and community</div>
                <div class="mt-2 text-white/90 text-sm max-w-xl">Your donation helps fund training, equipment, education support, and safe spaces for youth rugby.</div>
              </div>
            </div>

            <div class="p-5 sm:p-6">
              <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                  <div class="text-2xl font-extrabold text-green-800">100+</div>
                  <div class="mt-1 text-xs text-gray-600">Youth reached through programs</div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                  <div class="text-2xl font-extrabold text-green-800">20+</div>
                  <div class="mt-1 text-xs text-gray-600">Community activities each season</div>
                </div>
              </div>
            </div>
          </div>

          <div id="donate" class="rounded-3xl border border-gray-100 bg-white shadow-lg">
            <div class="p-5 sm:p-8">
              <div class="text-xs font-extrabold tracking-widest uppercase text-green-800">Donate</div>
              <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold">Donate as an Individual</h1>
              <p class="mt-2 text-sm text-gray-600">Support our players and community. You’ll be redirected to Pesapal to complete payment securely.</p>

              <div class="mt-6">
              <form class="grid gap-5" method="POST" action="./donate-initiate.php">
                <input type="hidden" name="csrf" value="<?php echo h((string)($_SESSION['donate_csrf'] ?? '')); ?>" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700">Amount</label>
                    <div class="mt-1 grid gap-2">
                      <div class="flex rounded-lg border border-gray-400 bg-white overflow-hidden focus-within:ring-2 focus-within:ring-green-700 focus-within:border-green-700">
                        <div class="px-4 py-3 text-sm font-extrabold text-gray-700 bg-gray-50 border-r border-gray-200">RWF</div>
                        <input id="donate-amount" name="amount" inputmode="decimal" type="number" step="0.01" min="1" required class="w-full border-0 px-4 py-3 text-sm focus:ring-0" placeholder="5000" />
                      </div>
                      <div class="text-xs text-gray-500">Enter the amount you want to give (RWF).</div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-semibold text-gray-700">Purpose of donation</label>
                    <input name="message" type="text" class="mt-1 w-full rounded-lg border border-gray-400 bg-white px-4 py-3 text-sm focus:border-green-700 focus:ring-2 focus:ring-green-700" placeholder="Purpose of donation (optional)" />
                    <div class="mt-2 text-xs text-gray-500">Optional. Add a note for our team.</div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700">First name</label>
                    <input name="first_name" autocomplete="given-name" type="text" required class="mt-1 w-full rounded-lg border border-gray-400 bg-white px-4 py-3 text-sm focus:border-green-700 focus:ring-2 focus:ring-green-700" />
                  </div>
                  <div>
                    <label class="block text-sm font-semibold text-gray-700">Last name</label>
                    <input name="last_name" autocomplete="family-name" type="text" required class="mt-1 w-full rounded-lg border border-gray-400 bg-white px-4 py-3 text-sm focus:border-green-700 focus:ring-2 focus:ring-green-700" />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700">Email</label>
                    <input name="email" autocomplete="email" inputmode="email" type="email" required class="mt-1 w-full rounded-lg border border-gray-400 bg-white px-4 py-3 text-sm focus:border-green-700 focus:ring-2 focus:ring-green-700" />
                  </div>
                  <div>
                    <label class="block text-sm font-semibold text-gray-700">Phone</label>
                    <input name="phone" autocomplete="tel" inputmode="tel" type="tel" class="mt-1 w-full rounded-lg border border-gray-400 bg-white px-4 py-3 text-sm focus:border-green-700 focus:ring-2 focus:ring-green-700" placeholder="+2507XXXXXXXX" />
                    <div class="mt-2 text-xs text-gray-500">Use a number that can receive payment prompts (if mobile money is enabled).</div>
                  </div>
                </div>

                <div class="grid gap-3">
                  <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700">
                    You’ll be redirected to Pesapal to complete payment.
                  </div>
                  <button type="submit" class="w-full inline-flex items-center justify-center rounded-2xl bg-green-700 px-5 py-4 text-white font-extrabold hover:bg-green-800 active:scale-[0.99]">Pay now</button>
                  <div class="text-[11px] text-gray-500">By continuing, you agree to Pesapal’s terms during checkout.</div>
                </div>
              </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="bg-white">
      <div class="max-w-6xl mx-auto px-4 py-10 sm:py-14">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
          <div>
            <div class="text-green-800 text-3xl sm:text-4xl font-extrabold">Our Impact in Numbers</div>
            <p class="mt-3 text-sm sm:text-base text-gray-600 max-w-xl">Thanks to supporters like you, we’re building opportunities through sport, education, and community leadership.</p>

            <div class="mt-8 grid grid-cols-2 gap-4">
              <div class="rounded-3xl border border-gray-100 bg-gray-50 p-5">
                <div class="text-3xl font-extrabold text-green-800">100+</div>
                <div class="mt-2 text-sm text-gray-600">youth participants supported</div>
              </div>
              <div class="rounded-3xl border border-gray-100 bg-gray-50 p-5">
                <div class="text-3xl font-extrabold text-green-800">50+</div>
                <div class="mt-2 text-sm text-gray-600">training sessions each year</div>
              </div>
              <div class="rounded-3xl border border-gray-100 bg-gray-50 p-5">
                <div class="text-3xl font-extrabold text-green-800">10+</div>
                <div class="mt-2 text-sm text-gray-600">schools & partners engaged</div>
              </div>
              <div class="rounded-3xl border border-gray-100 bg-gray-50 p-5">
                <div class="text-3xl font-extrabold text-green-800">100%</div>
                <div class="mt-2 text-sm text-gray-600">transparent donor tracking</div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <img class="rounded-3xl w-full h-44 sm:h-52 object-cover border border-gray-100" src="./images/kids.jpeg" alt="Rugby" />
            <img class="rounded-3xl w-full h-44 sm:h-52 object-cover border border-gray-100" src="./images/rugby-3.jpg" alt="Training" />
            <img class="rounded-3xl w-full h-44 sm:h-52 object-cover border border-gray-100" src="./images/futureKids.jpg" alt="Community" />
            <img class="rounded-3xl w-full h-44 sm:h-52 object-cover border border-gray-100" src="./images/study.jpg" alt="Education" />
          </div>
        </div>
      </div>
    </section>

    <section class="bg-gray-50">
      <div class="max-w-6xl mx-auto px-4 py-10 sm:py-14">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
          <div>
            <div class="text-green-800 text-3xl sm:text-4xl font-extrabold">The Challenge</div>
            <p class="mt-3 text-sm sm:text-base text-gray-600">Many young athletes face barriers to training, mentorship, and safe competition spaces.</p>
            <div class="mt-5 grid gap-3 text-sm text-gray-700">
              <div class="flex gap-3"><div class="mt-1 h-2 w-2 rounded-full bg-green-700"></div><div>Limited access to equipment and transport</div></div>
              <div class="flex gap-3"><div class="mt-1 h-2 w-2 rounded-full bg-green-700"></div><div>Gaps in nutrition and wellbeing support</div></div>
              <div class="flex gap-3"><div class="mt-1 h-2 w-2 rounded-full bg-green-700"></div><div>Need for coaching development and safe facilities</div></div>
            </div>
            <a href="#donate" class="mt-7 inline-flex items-center justify-center rounded-xl bg-green-700 px-5 py-3 text-sm font-extrabold text-white hover:bg-green-800">Donate now</a>
          </div>
          <div class="rounded-3xl overflow-hidden border border-gray-100 bg-white shadow-sm">
            <img src="./images/achiv.jpg" alt="Chanpions" class="w-full h-[280px] sm:h-[360px] object-cover" />
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="./donate-page.js"></script>
</body>
</html>
