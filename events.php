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
      
      .event-description-container {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
      }
      
      .event-description {
        flex: 1;
        overflow-y: auto;
        padding-right: 8px;
        max-height: 150px; /* Adjust as needed */
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
            <!--<li>
            <a
              class="block px-4 py-2 w-[180px] hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./Foundation"
              >Career Foundation</a
            >
          </li>-->
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
                <!-- 
          <li>
            <a
              class="block px-4 py-2 hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
              href="./Foundation"
              >Career Foundation</a
            >
          </li> -->
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
      <!-- Previous sections remain unchanged -->

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
              <div class="event-description-container">
                <p class="text-gray-600 mb-2"><strong>Description:</strong></p>
                <p class="text-gray-600 event-description">
                  <?php echo htmlspecialchars($event['description']); ?>
                </p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Rest of the content remains unchanged -->
      </section>

      <!-- Rest of your HTML remains the same -->
    </main>
  </body>
</html>