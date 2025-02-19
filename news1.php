<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ALL NEWS</title>
    <link rel="icon" href="./images/t_icon.png" type="image/png" /> 
    <link rel="stylesheet" href="./style.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <!-- Transparent Navbar -->
    <nav
    class="navbar top-0 left-0 w-full z-10 h-[10vh] flex justify-between items-center py-1 px-2 text-[#fff] fixed"
   >
    <div class="navbar-logo w-2/12">
      <a href="./">
        <img
          class="w-[60px] hover:w-[70px] mt-6 ml-6"
          src="./images/1000-hills-logo.png"
          alt="Logo"
        />
      </a>
    </div>

    <!-- Desktop Navigation -->
    <ul
      class="hidden lg:flex lg:space-x-8 font-600 text-gray-800 text-sm tracking-wider"
    >
      <li>
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./"
          >Home</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./about"
          >About</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./program"
          >Programs</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./community"
          >Community</a
        >
      </li>
      <li>
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300"
          href="./shop"
          >Shop</a
        >
      </li>
      <li class="relative group">
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none"
          >Education <i class="fas fa-chevron-down text-sm"></i
        ></a>
        <ul
          class="absolute left-0 hidden group-hover:block bg-white text-gray-800 text-sm shadow-md"
        >
          <li>
            <a
              class="block px-4 py-2 w-[180px] hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./education"
              >Education</a
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
      <li class="relative group">
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none"
          >Events <i class="fas fa-chevron-down text-sm"></i
        ></a>
        <ul
          class="absolute left-0 hidden group-hover:block bg-white text-gray-800 text-sm shadow-md"
        >
          <li>
            <a
              class="block px-4 py-2 w-[180px] hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
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

      <li class="relative group">
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none"
          >Result<i class="fas fa-chevron-down text-sm"></i
        ></a>
        <ul
          class="absolute left-0 hidden group-hover:block bg-white text-gray-800 text-sm shadow-md"
        >
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./fixtures"
              >Fixtures</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./results"
              >Results</a
            >
          </li>
        </ul>
      </li>

      <li class="relative group">
        <a
          class="hover:text-green-600 hover:border-b-2 text-[white] hover:border-green-600 transition-all duration-300 pointer-events-none"
          >Teams<i class="fas fa-chevron-down text-sm"></i
        ></a>
        <ul
          class="absolute left-0 hidden group-hover:block bg-white text-gray-800 text-sm shadow-md"
        >
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./menprofiles"
              >Men's Senior</a
            >
          </li>
          <li>
            <a
              class="block px-4 py-2 w-[150px] hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./womenprofiles"
              >Women's Senior
            </a>
          </li>

          <li class="relative">
            <!-- Parent Dropdown -->
            <button
              class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
              onclick="toggleDropdown('academy-menu')"
            >
              Academy  </i>
            </button>
            <ul
              id="academy-menu"
              class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
            >
              <!-- Under 18 Dropdown -->
              <li class="relative">
                <button
                  class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
                  onclick="toggleDropdown('under18-menu')"
                >
                  Under 18 <i class="fas fa-chevron-down text-sm"></i>
                </button>
                <ul
                  id="under18-menu"
                  class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
                >
                  <li>
                    <a
                      class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                      href="./under18Boys"
                      >Boys</a
                    >
                  </li>
                  <li>
                    <a
                      class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                      href="./under18Girls"
                      >Girls</a
                    >
                  </li>
                </ul>
              </li>
          
              <!-- Under 16 Dropdown -->
              <li class="relative">
                <button
                  class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
                  onclick="toggleDropdown('under16-menu')"
                >
                  Under 16 <i class="fas fa-chevron-down text-sm"></i>
                </button>
                <ul
                  id="under16-menu"
                  class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
                >
                  <li>
                    <a
                      class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                      href="./under16Boys"
                      >Boys</a
                    >
                  </li>
                  <li>
                    <a
                      class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                      href="./under16Girls"
                      >Girls</a
                    >
                  </li>
                </ul>
              </li>
            </ul>
          </li>
        </ul>
      </li>
      <li class="relative group">
        <a
          class="hover:text-green-600 text-[white] hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none mr-10"
          >Contact <i class="fas fa-chevron-down text-sm"></i
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
    </ul>

    <!-- Mobile Menu Toggle (hidden on large screens) -->
    <input type="checkbox" id="menu-toggle" class="hidden" />
    <label
      for="menu-toggle"
      class="cursor-pointer text-2xl text-black lg:hidden"
    >
      <i class="fa-solid fa-bars text-white" id="menu-open-icon"></i>
      <i class="fa-solid fa-times hidden text-white" id="menu-close-icon"></i>
    </label>

    <!-- Mobile Menu -->
    <div
      id="menu"
      class="absolute top-full right-0 bg-white text-gray-800 w-48 mt-2 rounded-md shadow-lg hidden transition-all duration-300"
    >
      <ul class="flex flex-col text-left space-y-1">
        <!-- Main Menu Items -->
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

        <!-- Dropdown for Education -->
        <li class="relative">
          <button
            class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
            onclick="toggleDropdown('education-menu-mobile')"
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
                >Education</a
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

        <!-- Dropdown for Events -->
        <li class="relative">
          <button
            class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
            onclick="toggleDropdown('events-menu-mobile')"
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

        <!-- Dropdown for Results -->
        <li class="relative">
          <button
            class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
            onclick="toggleDropdown('results-menu-mobile')"
          >
            Results <i class="fas fa-chevron-down text-sm"></i>
          </button>
          <ul
            id="results-menu-mobile"
            class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
          >
            <li>
              <a
                class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                href="./fixtures"
                >Fixtures</a
              >
            </li>
            <li>
              <a
                class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                href="./results"
                >Results</a
              >
            </li>
          </ul>
        </li>

         
        <!-- Dropdown for Teams -->

      <li class="relative">
       <button
        class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
        onclick="toggleDropdown('teams-menu-mobile')"
       >
        Teams <i class="fas fa-chevron-down text-sm"></i>
       </button>
       <ul
        id="teams-menu-mobile"
        class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
       >
        <li>
          <a
            class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
            href="./menprofiles"
            >Men's Senior</a
          >
        </li>

        <li>
          <a
            class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
            href="./womenprofiles"
            >Women's Senior</a
          >
        </li>
        <li class="relative">
          <!-- Parent Dropdown -->
          <button
            class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
            onclick="toggleDropdown('academy-menu-mobile')"
          >
            Academy </i>
          </button>
          <ul
            id="academy-menu-mobile"
            class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
          >
            <!-- Under 18 Dropdown -->
            <li class="relative">
              <button
                class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
                onclick="toggleDropdown('under18-menu-mobile')"
              >
                Under 18 <i class="fas fa-chevron-down text-sm"></i>
              </button>
              <ul
                id="under18-menu-mobile"
                class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
              >
                <li>
                  <a
                    class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                    href="./under18Boys"
                    >Boys</a
                  >
                </li>
                <li>
                  <a
                    class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                    href="./under18Girls"
                    >Girls</a
                  >
                </li>
              </ul>
            </li>
        
            <!-- Under 16 Dropdown -->
            <li class="relative">
              <button
                class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
                onclick="toggleDropdown('under16-menu-mobile')"
              >
                Under 16 <i class="fas fa-chevron-down text-sm"></i>
              </button>
              <ul
                id="under16-menu-mobile"
                class="dropdown hidden bg-white text-gray-800 text-sm shadow-md rounded-lg mt-1"
              >
                <li>
                  <a
                    class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                    href="./under16Boys"
                    >Boys</a
                  >
                </li>
                <li>
                  <a
                    class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                    href="./under16Girls"
                    >Girls</a
                  >
                </li>
              </ul>
            </li>
          </ul>
        </li>
        
        </ul>
    </li>

        <!-- Contact -->
        <li class="relative">
          <button
            class="dropdown-toggle hover:text-green-600 px-4 py-2 flex items-center justify-between transition-all duration-300 cursor-pointer w-full"
            onclick="toggleDropdown('contact-menu-mobile')"
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
      </ul>
    </div>
  </nav>

    <style>
      /* Toggle Mobile Menu Display */
      #menu-toggle:checked + label + #menu {
        display: block;
      }
    </style>

<?php
$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM articles ORDER BY date_published DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<section class="w-10/12 flex flex-col mx-auto gap-6 mt-28 bg-white p-6 rounded-lg shadow-lg">';
        echo '<div class="flex gap-2">';
        echo '<i class="text-[#1b75bc] text-2xl fa-regular fa-bookmark"></i>';
        echo '<p class="lg:text-lg font-semibold">' . strtoupper($row["category"]) . '</p>';
        echo '</div>';
        echo '<div>';
        echo '<p class="lg:w-11/12 text-[40px] font-bold hover:text-[#1b75bc] uppercase">' . $row["title"] . '</p>';
        echo '</div>';
        echo '<div class="flex gap-2 items-center">';
        echo '<i class="text-[#1b75bc] text-2xl fa-regular fa-clock"></i>';
        echo '<p class="lg:text-lg">' . $row["date_published"] . '</p>';
        echo '</div>';

        // Display Image
        if (!empty($row["image_path"]) && file_exists($row["image_path"])) {
            echo '<div>';
            echo '<img class="w-full h-full object-cover rounded-lg" src="' . $row["image_path"] . '" alt="' . $row["title"] . '" />';
            echo '</div>';
        }

        echo '<div class="w-10/12">';
        echo '<p class="text-gray-700 leading-relaxed">' . $row["content"] . '</p>';
        echo '<div class="flex gap-3 items-center">';
        echo '<p class="text-sm font-bold">FOLLOW US:</p>';
        echo '<ul class="flex gap-3 text-xl text-[#1b75bc]">';
        echo '<li><a class="hover:text-2xl" href="https://www.facebook.com/1000hillsrugby/"><i class="fa-brands fa-facebook-f"></i></a></li>';
        echo '<li><a class="hover:text-2xl" href="https://www.instagram.com/1000hillsrugby/"><i class="fa-brands fa-instagram"></i></a></li>';
        echo '<li><a class="hover:text-2xl" href="https://x.com/1000HillsRugby?t=S0PTUa88AFrp6SJs5meJ6A&s=08"><i class="fa-brands fa-x-twitter"></i></a></li>';
        echo '<li><a class="hover:text-2xl" href="https://www.youtube.com/@1000HillsRugby"><i class="fa-brands fa-youtube"></i></a></li>';
        echo '</ul>'; 
        echo '</div>';
        echo '</section>';
    }
} else {
    echo "<p class='text-center text-xl font-semibold mt-10'>No articles found.</p>";
}

$conn->close();
?>
<footer
      class="w-full mb-[1px] px-6 py-12 grid grid-cols-12 gap-y-12 lg:gap-8 text-white bg-gradient-to-b from-black to-gray-900 lg:h-auto"
    >
      <!-- Logo Section -->
      <div
        class="lg:col-span-3 col-span-6 flex items-center justify-center lg:justify-start lg:mx-0 mb-8 lg:mb-0"
      >
        <img
          class="lg:w-[150px] h-[150px] w-[120px] lg:h-[120px] object-contain transition-transform duration-300 hover:scale-105"
          src="./images/1000-hills-logo.png"
          alt="Logo"
        />
      </div>

      <!-- Team Info Section -->
      <div
        class="lg:col-span-3 col-span-6 flex flex-col items-center lg:items-start mb-8 lg:mb-0"
      >
        <p class="text-lg font-bold tracking-wide text-[#dcbb26] uppercase">
          Teams
        </p>
        <ul class="mt-4 text-[#a5a6a8] text-sm font-light flex flex-col gap-2">
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./program#p1">Player Development Program</a>
          </li>
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./program#p2">Women’s Rugby Program</a>
          </li>
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./program#p3">Rugby & Life Skills Program</a>
          </li>
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./program#p4">Grassroots Rugby Initiative</a>
          </li>
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./staff">Coaching Staff</a>
          </li>
        </ul>
      </div>

      <!-- Club Info Section -->
      <div
        class="lg:col-span-3 col-span-6 flex flex-col items-center lg:items-start mb-8 lg:mb-0"
      >
        <p class="text-lg font-bold tracking-wide text-[#dcbb26] uppercase">
          Community
        </p>
        <ul class="mt-4 text-[#a5a6a8] text-sm font-light flex flex-col gap-2">
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./about">About Us</a>
          </li> 
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./program">Programs</a>
          </li>
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./news">News & Media</a>
          </li>
          <li class="hover:text-[#dcbb26] transition-colors duration-300">
            <a href="./contact">Contact</a>
          </li>
        </ul>
      </div>

      <!-- Contact & Social Media Section -->
      <div
        class="lg:col-span-3 col-span-6 flex flex-col items-center lg:items-start"
      >
        <div class="mb-6">
          <p class="text-lg font-bold tracking-wide text-[#dcbb26] uppercase">
            Contact
          </p>
          <ul
            class="mt-4 text-[#a5a6a8] text-sm font-light flex flex-col gap-2"
          >
            <li
              class="flex items-center hover:text-[#dcbb26] transition-colors duration-300"
            >
              <i class="text-[#006838] mr-2 fa-solid fa-phone"></i>+250 788 261
              386
            </li>
            <li
              class="flex items-center hover:text-[#dcbb26] transition-colors duration-300"
            >
              <i class="text-[#006838] mr-2 fa-solid fa-envelope"></i
              >thillsrugby@gmail.com
            </li>
            <li
              class="flex items-center hover:text-[#dcbb26] transition-colors duration-300"
            >
              <i class="text-[#006838] mr-2 fa-solid fa-location-dot"></i>KK 591
              St, Kigali
            </li>
          </ul>
        </div>

        <div>
          <p class="text-lg font-bold tracking-wide text-[#dcbb26] uppercase">
            Connect
          </p>
          <ul class="mt-4 flex gap-4 text-2xl">
            <li class="hover:text-[#1877F2] transition-colors duration-300">
              <a
                href="https://www.facebook.com/1000hillsrugby/"
                aria-label="Facebook"
              >
                <i class="fa-brands fa-facebook-f"></i>
              </a>
            </li>
            <li class="hover:text-[#E4405F] transition-colors duration-300">
              <a
                href="https://www.instagram.com/1000hillsrugby/"
                aria-label="Instagram"
              >
                <i class="fa-brands fa-instagram"></i>
              </a>
            </li>
            <li class="hover:text-[#1DA1F2] transition-colors duration-300">
              <a href="https://x.com/1000HillsRugby" aria-label="Twitter">
                <i class="fa-brands fa-x-twitter"></i>
              </a>
            </li>
            <li class="hover:text-[#FF0000] transition-colors duration-300">
              <a
                href="https://www.youtube.com/@1000HillsRugby"
                aria-label="YouTube"
              >
                <i class="fa-brands fa-youtube"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
    
    </footer>
    
    <section class="foter">
      <p>
        &copy; 2024 1000HillsRugby. All Rights Reserved|
        <a href="privacy">Privacy Policy</a>
      </p>
    </section>
    <script src="./index.js"></script>
  

</body>
</html>
