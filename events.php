<?php
// Database connection
$conn = new mysqli('localhost', 'hillsrug_hillsrug', 'M00dle??', 'hillsrug_1000hills_rugby_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch events from database
$events = [];
$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Fetch weekly schedule from database
$schedule = [];
$result = $conn->query("SELECT * FROM weekly_schedule ORDER BY FIELD(day, 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY')");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $schedule[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Our - Events</title>
    <link rel="icon" href="./images/t_icon.png" type="image/png" />
    <link rel="stylesheet" href="./style.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- Load Slick Carousel JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js"></script>
    <!-- Load Slick Carousel CSS -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.css"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
      .slider-container {
        overflow: hidden;
        white-space: nowrap;
        position: relative;
      }
      .slider-track {
        display: flex;
        gap: 20px;
        width: max-content;
        animation: scroll 30s linear infinite;
      }

      .partner-logo {
        height: 120px;
        width: auto;
        flex-shrink: 0;
      }

      @keyframes scroll {
        from {
          transform: translateX(0);
        }
        to {
          transform: translateX(-50%);
        }
      }
      
      /* Custom styles for event cards */
      .event-card {
        min-width: 300px;
        width: 300px;
        height: 400px; /* Fixed height */
        display: flex;
        flex-direction: column;
      }
      
      .event-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
      }
      
      .event-description {
        flex: 1;
        overflow-y: auto;
        padding-right: 8px;
      }
      
      /* Scrollbar styling */
      .event-description::-webkit-scrollbar {
        width: 6px;
      }
      
      .event-description::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
      }
      
      .event-description::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
      }
      
      .event-description::-webkit-scrollbar-thumb:hover {
        background: #555;
      }
    </style>
  </head>

  <style>
    #menu-toggle:checked ~ #menu {
      display: block;
    }

    /* Hide the menu if not checked */
    #menu:hover {
      display: block;
    }
    .foter {
      bottom: 0;
      left: 0;
      width: 100%;
      text-align: center;
      padding: 1rem;
      background-color: #1d6c1c;
      color: #fff;
      z-index: 1000;
      box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
    }

    .foter a {
      color: #fff;
      text-decoration: underline;
      margin-left: 0.5rem;
    }

    .foter a:hover {
      color: #c2ffc1;
      text-decoration: none;
    }
  </style>

  <body> 
    <main> 
      
    <!-- Transparent Navbar -->
    <nav
      class="navbar fixed top-0 left-0 w-full px-2 z-20 h-[10vh] flex flex-wrap justify-between items-center py-2 bg-white/90 backdrop-blur-lg shadow-lg transition-all duration-300"
    >
      <!-- Logo -->
      <div class="navbar-logo w-2/12">
        <a href="./">
          <img
            class="w-[60px] hover:w-[70px] transition-transform duration-300"
            src="./images/1000-hills-logo.png"
            alt="1000 Hills Rugby"
          />
        </a>
      </div>

      <!-- Desktop Navigation -->
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

      <!-- Mobile Menu & Social Media -->
      <div class="relative lg:hidden flex flex-wrap items-center">
        <!-- Mobile Menu Toggle -->
        <input type="checkbox" id="menu-toggle" class="hidden" />
        <label for="menu-toggle" class="cursor-pointer text-2xl text-black">
          <i class="fa-solid fa-bars" id="menu-open-icon"></i>
          <i class="fa-solid fa-times hidden" id="menu-close-icon"></i>
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

            <li>
              <a
                class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
                href="./teams"
                >Teams</a
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
      </div>
    </nav>
      <section
        class="relative h-screen max-w-full grid grid-cols-12 items-center bg-cover bg-center overflow-hidden"
        style="background-image: url('./images/events-bg.jpg')"
      >
        <!-- Gradient Overlay for Better Text Contrast -->
        <div
          class="absolute inset-0 bg-gradient-to-r from-black/30 via-transparent to-black/50"
        ></div>

        <!-- Text Content -->
        <div
          class="text-container col-span-12 z-10 text-white flex flex-col gap-4 pl-6 lg:pl-10 items-start text-left"
        >
          <p
            class="font-bold text-lg flex flex-wrap lg:text-2xl mt-[50px] uppercase tracking-widest animate__animated animate__fadeIn animate__delay-1s"
          >
            <!--  Upcoming Event: Jade Water 7s First Edition  -->
          </p>
          <h1
            class="font-extrabold leading-tight flex flex-col items-start text-left space-y-2"
          >
            <span
              class="text-[#dcbb26] lg:text-8xl md:text-6xl text-4xl block animate__animated animate__fadeIn animate__delay-2s"
            >
              Discpline
            </span>
            <span
              class="lg:text-8xl md:text-6xl text-4xl block animate__animated animate__fadeIn animate__delay-3s"
            >
              Sportsmanship
            </span>
            <span
              class="text-green-500 lg:text-8xl md:text-6xl text-4xl block animate__animated animate__fadeIn animate__delay-4s"
            >
              Integrity
            </span>
          </h1>
          <p
            class="text-base lg:text-lg mt-2 text-white/80 animate__animated animate__fadeIn animate__delay-5s lg:w-[40%] md:w-[55%] w-[70%]"
          >
            Join 1000 Hills Rugby at Don Bosco Gatenga, home to our senior men's
            and women's teams, age-grade, and grassroots squads. Watch the
            action, support rising talent, and be part of our rugby family
          </p>
        </div>

        <!-- Parallax Background Effect -->
        <div
          class="absolute inset-0 bg-[url('./images/4blripeq.png')] bg-fixed bg-cover bg-center opacity-60"
        ></div>

        <!-- Scroll-to-Top Button -->
        <a
          href="#top"
          class="fixed bottom-20 border border-white right-2 z-50 bg-[#006838] hover:bg-[#00562c] transition-all duration-300 text-white px-4 py-2 rounded-full shadow-lg text-lg flex items-center justify-center"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-6 h-6"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M19 15l-7-7-7 7" />
          </svg>
        </a>
      </section>

      <section
        class="relative flex flex-col items-center mt-24 space-y-8 py-16 bg-gradient-to-t from-white to-white shadow-3xl border-2 border-green-600 p-2 rounded-lg max-w-7xl mx-auto px-4 lg:px-8"
      >
        <!-- Animated Slogan (Hero Text) -->
        <div
          class="flex flex-col items-center text-center space-y-4 animate__animated animate__fadeInUp animate__delay-1s"
        >
          <h2
            class="text-[#006838] lg:text-5xl text-3xl font-extrabold tracking-wide uppercase"
          >
            Stay Updated with Our Events
          </h2>
          <p
            class="lg:text-xl text-md font-light text-gray-700 leading-relaxed max-w-2xl"
          >
            Experience the thrill of rugby with upcoming events that bring
            together the best teams, heart-pounding action, and unforgettable
            moments.
          </p>
        </div>

        <!-- Event Highlights Carousel (Horizontal Scroll) -->
        <div
          class="relative w-full max-w-5xl overflow-x-auto flex space-x-6 py-4 no-scrollbar items-stretch"
        >
          <?php foreach ($events as $event): ?>
          <!-- Event Card -->
          <div
            class="event-card bg-white shadow-lg rounded-lg p-4 transform hover:scale-105 transition duration-300"
          >
            <div class="flex items-center mb-4">
              <i class="fas fa-smile text-blue-500 text-2xl mr-3"></i>
              <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($event['title']); ?></h2>
            </div>
            <div class="event-content">
              <p class="text-gray-600 mb-2"><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
              <p class="text-gray-600 mb-2">
                <strong>Time:</strong> <?php echo htmlspecialchars($event['event_time']); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <strong>Participants:</strong> <?php echo htmlspecialchars($event['participants']); ?>
              </p>
              <p class="text-gray-600 mb-2">
                <strong>Frequency:</strong> <?php echo htmlspecialchars($event['frequency']); ?>
              </p>
              <p class="text-gray-600 mb-2 event-description">
                <strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?>
              </p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Newsletter Subscription Section -->
        <div class="w-full max-w-md mx-auto mt-10">
          <h3 class="text-xl font-semibold text-center text-gray-700 mb-4">
            Connect with 1000 Hills Rugby for Event Updates
          </h3>
          <p class="text-center text-gray-600 mb-4">
            Follow us on social media to get the latest updates quickly!
          </p>
          <div class="flex justify-center space-x-4">
            <a
              href="https://www.facebook.com/1000hillsrugby"
              class="text-gray-500 hover:text-[#006838] transition-colors duration-300"
            >
              <i class="fab fa-facebook-f text-2xl"></i>
            </a>
            <a
              href="https://x.com/1000hillsrugby"
              class="text-gray-500 hover:text-[#006838] transition-colors duration-300"
            >
              <i class="fab fa-twitter text-2xl"></i>
            </a>
            <a
              href="https://www.instagram.com/1000hillsrugby/"
              class="text-gray-500 hover:text-[#006838] transition-colors duration-300"
            >
              <i class="fab fa-instagram text-2xl"></i>
            </a>

            <a
              href="https://www.linkedin.com/company/1000hillsrugby/"
              class="text-gray-500 hover:text-[#006838] transition-colors duration-300"
            >
              <i class="fab fa-linkedin-in text-2xl"></i>
            </a>
            <a
              href="https://www.tiktok.com/@1000.hills.rugby?_t=ZM-8trKu7CddnL&_r=1 "
              class="text-gray-500 hover:text-[#006838] transition-colors duration-300"
            >
              <i class="fab fa-tiktok text-2xl"></i>
            </a>
          </div>
        </div>

        <!-- Decorative Background Element -->
        <div
          class="absolute top-0 left-0 w-[100px] h-[5px] bg-[#dcbb26] transform -translate-y-[50%]"
        ></div>
      </section>

      <!-- Rest of your HTML remains the same -->
      <section class="lg:mx-6 mx-2">
        <!-- Title Section -->
        <div class="text-center my-16">
          <p class="font-semibold text-gray-600 tracking-wider">
            WHAT'S HAPPENING
          </p>
          <h1 class="text-6xl font-bold">
            CLUB <span class="text-gray-600">EVENTS</span>
          </h1>
        </div>

        <!-- Event and Video Section -->
        <div
          class="flex flex-col lg:flex-row gap-10 items-start justify-center"
        >
          <!-- Event Cards Container -->
          <div class="lg:w-8/12 grid lg:grid-cols-2 gap-8">
            <?php 
            $featuredEvents = array_slice($events, 0, 2);
            foreach ($featuredEvents as $event): 
              $dateParts = explode('-', $event['event_date']);
              $day = $dateParts[2];
              $month = date('M', mktime(0, 0, 0, $dateParts[1], 10));
            ?>
            <!-- Event Card -->
            <div
              class="bg-white h-[50vh] rounded-xl shadow-xl overflow-hidden group transition-transform duration-300 hover:-translate-y-2 border-2 border-green-400"
            >
              <div
                class="flex items-center justify-center bg-green-500 text-white p-6 group-hover:bg-green-600 transition-colors duration-300"
              >
                <div class="text-center">
                  <p class="text-3xl font-extrabold"><?php echo $day; ?></p>
                  <p class="text-sm tracking-wide"><?php echo $month; ?></p>
                </div>
              </div>
              <div class="p-6">
                <h2
                  class="text-2xl font-semibold mb-3 text-black group-hover:text-green-600 transition-colors duration-300"
                >
                  <?php echo htmlspecialchars($event['title']); ?>
                </h2>
                <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($event['location']); ?> / <?php echo htmlspecialchars($event['event_time']); ?></p>
                <span
                  class="text-xs bg-yellow-100 text-yellow-800 py-1 px-3 rounded-full"
                  ><?php echo htmlspecialchars($event['participants']); ?></span
                >
              </div>
              <div
                class="relative group-hover:bg-gray-100 transition-colors duration-300"
              >
                <div
                  class="absolute inset-0 bg-black bg-opacity-30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center"
                >
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="mb-14">
        <!-- Section Header -->
        <div
          class="border-l-[10px] w-11/12 mx-auto flex my-16 flex-col gap-2 col-span-9 border-[#0B3D39] pl-4"
        >
          <p class="font-semibold text-lg tracking-widest text-[#4A4A4A]">
            TRAINING SESSIONS
          </p>
          <h1 class="lg:text-6xl text-3xl font-extrabold">
            WEEKLY <span class="text-gray-500">SCHEDULE</span>
          </h1>
        </div>

        <!-- Schedule Table -->
        <div
          class="w-11/12 lg:w-10/12 mx-auto border-2 border-[#0B3D39] rounded-3xl overflow-hidden shadow-lg bg-gradient-to-br from-white to-[#E6F4EA]"
        >
          <table class="w-full text-left text-gray-800">
            <?php foreach ($schedule as $day): ?>
            <thead>
              <tr class="bg-[#D4EDDA]">
                <th
                  class="py-4 px-4 lg:py-5 lg:px-8 text-xl lg:text-2xl font-bold tracking-wide text-[#0B3D39]"
                >
                  <?php echo strtoupper($day['day']); ?>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                class="border-b border-gray-300 hover:bg-[#F9F9F9] transition duration-300"
              >
                <td
                  class="py-4 px-4 lg:py-5 lg:px-8 flex flex-col lg:flex-row items-start gap-4 lg:gap-6"
                >
                  <i
                    class="text-3xl lg:text-5xl fa-solid fa-person-running text-[black]"
                  ></i>
                  <div class="flex flex-col">
                    <p class="text-xs lg:text-sm text-gray-500">
                      <i class="fa-solid fa-location-dot"></i><?php echo htmlspecialchars($day['location']); ?>
                    </p>
                    <div
                      class="flex flex-col lg:flex-row items-start lg:items-center gap-2 lg:gap-4"
                    >
                      <p
                        class="text-lg lg:text-2xl font-semibold text-[#6D6E71]"
                      >
                        <?php echo htmlspecialchars($day['time_range']); ?>
                      </p>
                      <span
                        class="border border-gray-300 h-4 hidden lg:inline-block mx-2"
                      ></span>
                      <p
                        class="text-lg lg:text-2xl font-semibold text-[#0B3D39]"
                      >
                        <?php echo htmlspecialchars($day['activity']); ?>
                      </p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
            <?php endforeach; ?>
          </table>
        </div>
      </section>

      <section class="mb-14 py-12">
        <div class="w-11/12 mx-auto text-center">
          <h2 class="text-5xl font-bold mb-8">Highlights</h2>
          <div class="flex flex-wrap gap-8 justify-center">
            <!-- Highlight 1 -->
            <div class="bg-gray-50 p-6 rounded-lg shadow-md w-full sm:w-1/3">
              <a href="#">
                <i class="fa-solid fa-trophy text-5xl text-[#6d6e71]"></i>
                <h3 class="text-2xl font-semibold mt-4">Competitions</h3>
                <p class="text-lg mt-2 text-gray-600">
                  Showcase your skills in our events and stand a chance to win
                  amazing prizes.
                </p>
              </a>
            </div>
            <!-- Highlight 2 -->

            <div class="bg-gray-50 p-6 rounded-lg shadow-md w-full sm:w-1/3">
              <a href="#">
                <i class="fa-solid fa-microphone text-5xl text-[#6d6e71]"></i>
                <h3 class="text-2xl font-semibold mt-4">Keynote Speakers</h3>
                <p class="text-lg mt-2 text-gray-600">
                  Hear from rugby leaders and experts as they share insights and
                  tips for success.
                </p>
              </a>
            </div>
            <!-- Highlight 3 -->
            <div class="bg-gray-50 p-6 rounded-lg shadow-md w-full sm:w-1/3">
              <a href="#">
                <i
                  class="fa-solid fa-network-wired text-5xl text-[#6d6e71]"
                ></i>
                <h3 class="text-2xl font-semibold mt-4">Networking</h3>
                <p class="text-lg mt-2 text-gray-600">
                  Connect with players, veterans, partners and volunteers in our
                  networking sessions.
                </p>
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Sponsors Section -->
      <section class="py-20 bg-[#F5F9F8] relative overflow-hidden">
        <div
          class="absolute inset-0 bg-gradient-to-b from-white via-transparent to-[#F5F9F8]"
        ></div>
        <div
          class="w-11/12 max-w-7xl mx-auto text-center flex flex-col items-center relative z-10"
        >
          <h2
            class="text-5xl font-extrabold mb-6 text-[#0B3D39] tracking-wide drop-shadow-sm"
          >
            Our Partners
          </h2>
          <p
            class="text-lg mb-12 text-gray-700 lg:w-[60%] md:w-[75%] w-[90%] leading-relaxed drop-shadow-sm lg:text-center text-start"
          >
            We are immensely proud and grateful to be supported by these trusted
            companies and organizations. Their generous contributions,
            dedication, and partnership play a crucial role in bringing all we
            do to life, ensuring 1000 Hills Rugby success, and helping us
            achieve our shared goals.
          </p>
          <div class="slider-container">
            <div class="slider-track">
              <img
                src="images/rqt.jpg"
                alt="logo"
                class="partner-logo h-48 object-cover"
              />
              <img
                src="./images/LSFG+PartnerLogos-Colour-horizontal.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="images/anymore.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="images/pwr.png"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="images/rqt.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="./images/LSFG+PartnerLogos-Colour-horizontal.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="images/anymore.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="images/pwr.png"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <!-- Duplicate slides for smooth loop -->

              <img
                src="images/rqt.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="./images/LSFG+PartnerLogos-Colour-horizontal.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="images/anymore.jpg"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
              <img
                src="images/pwr.png"
                alt="logo"
                class="partner-logo h-40 object-cover"
              />
            </div>
          </div>
        </div>
      </section>

      <section class="py-20 relative overflow-hidden">
        <!-- Enhanced Background with Particles and Gradient -->
        <div
          class="absolute inset-0 bg-gradient-to-br from-[bg-yellow-100] to-[#6d6e71] opacity-95"
        ></div>
        <div
          class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20 mix-blend-overlay"
        ></div>
        <!-- Adding a subtle cube pattern texture -->

        <!-- Particle Effect for Extra Visual Appeal -->
        <div
          class="absolute inset-0 flex justify-center items-center pointer-events-none"
        >
          <div
            class="h-[300px] w-[300px] bg-gradient-to-r from-[green] via-transparent to-[yellow] opacity-50 rounded-full blur-2xl animate-float"
          ></div>
        </div>

        <div
          class="w-11/12 max-w-4xl mx-auto text-center text-black relative z-10"
        >
          <h2
            class="text-6xl font-extrabold mb-6 tracking-wider drop-shadow-lg"
          >
            Register Now
          </h2>
          <p
            class="lg:text-center text-start text-lg mb-10 leading-relaxed max-w-2xl mx-auto drop-shadow-md"
          >
            Don't miss out on this incredible opportunity! This is your chance
            to gain invaluable skills, connect with our community, and
            collaborate with like-minded individuals. Be part of something
            special!
          </p>
          <a
            href="https://app.smartsheet.com/b/form/2d87156a9d224acbb4d770966eb6cee3"
            class="bg-white text-black px-10 py-4 rounded-full text-xl font-semibold shadow-lg transition duration-300 transform hover:bg-yellow-600 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-white hover:shadow-2xl"
          >
            Register</a
          >
        </div>
      </section>
    </main>

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
            <a href="./program#p2">Women's Rugby Program</a>
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
        <a href="privacy.html">Privacy Policy</a>
      </p>
    </section>
    <script src="./index.js"></script>
  </body>
</html>