
<?php
// Ensure UTF-8 output
header('Content-Type: text/html; charset=utf-8');

// Database connection (match news.php)
$conn = new mysqli("localhost", "hillsrug_gasore", "M00dle??", "hillsrug_db", 3306);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// Ensure resources table exists (in case upload_resources.php has not been run yet)
$createTableSql = "CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    year INT NULL,
    file_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createTableSql);

// Fetch resources
$resources = [];
$sql       = "SELECT id, title, description, category, year, file_path, created_at
             FROM resources
             ORDER BY year DESC, created_at DESC";
$result    = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1000 Hills Rugby | Resources</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="./images/t_icon.png" type="image/png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
  <!-- Navbar (copied from news.php for consistency) -->
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
              class="block px-4 py-2  hover:text-green-600 hover:bg-gray-100 transition-all duration-300"
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

  <!-- Resources Content -->
  <div class="container mx-auto px-4 mt-[80px] mb-12">
    <div class="bg-white rounded-xl shadow-lg p-6 md:p-10 mt-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-3xl md:text-4xl font-bold text-gray-800 flex items-center gap-3">
            <i class="fas fa-folder-open text-[#dcbb26]"></i>
            Club Resources
          </h1>
          <p class="text-gray-600 mt-2 max-w-2xl">
            Download our official documents such as annual reports, strategic plans, and key policies
            that guide the 1000 Hills Rugby Club.
          </p>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-500">
          <i class="fas fa-file-pdf text-red-500 text-xl"></i>
          <span>All documents are provided in PDF format.</span>
        </div>
      </div>

      <?php if (empty($resources)): ?>
        <div class="border border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-500">
          <i class="fas fa-info-circle text-3xl mb-2 text-gray-400"></i>
          <p>No resources are available yet. Please check back later.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($resources as $res): ?>
            <div class="border rounded-lg shadow-sm hover:shadow-md transition-shadow bg-gray-50 flex flex-col">
              <div class="p-4 flex-1 flex flex-col">
                <div class="flex items-start justify-between gap-2 mb-2">
                  <h2 class="font-semibold text-lg text-gray-800 line-clamp-2">
                    <?php echo htmlspecialchars($res['title']); ?>
                  </h2>
                  <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-red-50 text-red-600">
                    <i class="fas fa-file-pdf"></i>
                  </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 mb-3">
                  <?php if (!empty($res['category'])): ?>
                    <span class="px-2 py-1 rounded-full bg-green-100 text-green-700 font-medium">
                      <?php echo htmlspecialchars($res['category']); ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($res['year'])): ?>
                    <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">
                      <?php echo htmlspecialchars($res['year']); ?>
                    </span>
                  <?php endif; ?>
                  <span>
                    Added on <?php echo htmlspecialchars(date('M j, Y', strtotime($res['created_at']))); ?>
                  </span>
                </div>

                <?php if (!empty($res['description'])): ?>
                  <p class="text-sm text-gray-700 mb-4 line-clamp-3">
                    <?php echo htmlspecialchars($res['description']); ?>
                  </p>
                <?php else: ?>
                  <p class="text-sm text-gray-500 mb-4">
                    No description provided.
                  </p>
                <?php endif; ?>
              </div>

              <div class="border-t px-4 py-3 bg-white flex items-center justify-between">
                <a
                  href="<?php echo htmlspecialchars($res['file_path']); ?>"
                  target="_blank"
                  class="inline-flex items-center gap-2 text-sm font-semibold text-[#dcbb26] hover:text-gray-900"
                >
                  <i class="fas fa-arrow-down"></i>
                  View / Download
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function toggleDropdown(id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.toggle('hidden');
    }

    // Mobile menu icon toggle
    const menuToggle = document.getElementById('menu-toggle');
    const menu       = document.getElementById('menu');
    const openIcon   = document.getElementById('menu-open-icon');
    const closeIcon  = document.getElementById('menu-close-icon');

    if (menuToggle) {
      menuToggle.addEventListener('change', () => {
        if (menu) {
          menu.classList.toggle('hidden', !menuToggle.checked);
        }
        if (openIcon && closeIcon) {
          openIcon.classList.toggle('hidden', menuToggle.checked);
          closeIcon.classList.toggle('hidden', !menuToggle.checked);
        }
      });
    }
  </script>
</body>
</html>

<?php $conn->close(); ?>

