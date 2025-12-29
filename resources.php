
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

// Read filters from query string
$filterYear     = isset($_GET['year']) && $_GET['year'] !== '' ? (int) $_GET['year'] : null;
$filterCategory = isset($_GET['category']) && $_GET['category'] !== '' ? trim($_GET['category']) : '';

// Fetch available years and categories for filter controls
$availableYears      = [];
$availableCategories = [];

$yearsResult = $conn->query("SELECT DISTINCT year FROM resources WHERE year IS NOT NULL ORDER BY year DESC");
if ($yearsResult && $yearsResult->num_rows > 0) {
    while ($row = $yearsResult->fetch_assoc()) {
        $availableYears[] = (int) $row['year'];
    }
}

$catResult = $conn->query("SELECT DISTINCT category FROM resources WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
if ($catResult && $catResult->num_rows > 0) {
    while ($row = $catResult->fetch_assoc()) {
        $availableCategories[] = $row['category'];
    }
}

// Build filtered query
$resources = [];
$conditions = [];

if ($filterYear !== null) {
    $conditions[] = "year = " . (int) $filterYear;
}
if ($filterCategory !== '') {
    $safeCategory = $conn->real_escape_string($filterCategory);
    $conditions[] = "category = '" . $safeCategory . "'";
}

$sql = "SELECT id, title, description, category, year, file_path, created_at FROM resources";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}
$sql .= " ORDER BY year DESC, created_at DESC";

$result = $conn->query($sql);
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
  <div class="container mx-auto px-4 mt-[90px] mb-16">
    <section class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
      <!-- Header strip -->
      <div class="bg-gradient-to-r from-[#006838] via-[#0b8748] to-[#dcbb26] px-6 md:px-10 py-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <p class="uppercase tracking-[0.2em] text-xs md:text-[0.7rem] text-emerald-100 mb-1">Club Library</p>
          <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-white flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/15">
              <i class="fas fa-folder-open"></i>
            </span>
            <span>Resources & Reports</span>
          </h1>
          <p class="text-emerald-50/90 mt-2 text-sm md:text-base max-w-2xl">
            Access official documents including annual reports, strategic plans, safeguarding policies,
            and other key publications from 1000 Hills Rugby.
          </p>
        </div>
        <div class="flex flex-col items-start md:items-end gap-2 text-xs md:text-sm text-emerald-50">
          <div class="inline-flex items-center gap-2 bg-black/15 rounded-full px-3 py-1">
            <i class="fas fa-file-alt text-yellow-300"></i>
            <span>Formats: PDF, Word, Excel, PowerPoint</span>
          </div>
          <?php if (!empty($resources)): ?>
            <span class="opacity-90">
              <?php echo count($resources); ?> document(s) available
            </span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Content area -->
      <div class="px-4 sm:px-6 md:px-10 py-6 md:py-8 bg-gray-50/80">
        <!-- Filters -->
        <form method="GET" action="resources.php" class="mb-6 bg-white/80 border border-gray-200 rounded-xl px-4 sm:px-5 py-4 flex flex-col md:flex-row gap-4 md:items-end md:justify-between">
          <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 tracking-wide mb-1">Year</label>
              <select name="year" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#dcbb26] focus:border-[#dcbb26]">
                <option value="">All years</option>
                <?php foreach ($availableYears as $yearOption): ?>
                  <option value="<?php echo $yearOption; ?>" <?php echo ($filterYear === (int) $yearOption) ? 'selected' : ''; ?>>
                    <?php echo $yearOption; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 tracking-wide mb-1">Category</label>
              <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#dcbb26] focus:border-[#dcbb26]">
                <option value="">All categories</option>
                <?php foreach ($availableCategories as $catOption): ?>
                  <option value="<?php echo htmlspecialchars($catOption); ?>" <?php echo ($filterCategory === $catOption) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($catOption); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 bg-[#006838] hover:bg-[#03512a] text-white text-sm font-semibold px-4 py-2 rounded-full shadow-sm hover:shadow-md transition-all">
              <i class="fas fa-filter text-xs"></i>
              <span>Apply filters</span>
            </button>
            <?php if ($filterYear !== null || $filterCategory !== ''): ?>
              <a href="resources.php" class="text-xs text-gray-500 hover:text-gray-700 underline">Clear</a>
            <?php endif; ?>
          </div>
        </form>
        <?php if (empty($resources)): ?>
          <div class="border border-dashed border-gray-300 rounded-2xl p-10 text-center text-gray-500 bg-white/70 max-w-xl mx-auto">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
              <i class="fas fa-info-circle text-3xl text-gray-400"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-700 mb-2">No documents published yet</h2>
            <p class="text-sm text-gray-500">
              Resources will appear here as soon as the club publishes official documents. Please check back soon.
            </p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($resources as $res): ?>
              <article class="group relative bg-white rounded-xl shadow-sm hover:shadow-lg border border-gray-100 hover:border-[#dcbb26]/60 transition-all duration-200 flex flex-col">
                <!-- Accent bar -->
                <div class="absolute left-0 top-0 h-full w-1 rounded-l-xl bg-gradient-to-b from-[#dcbb26] via-[#006838] to-transparent opacity-60 group-hover:opacity-100"></div>

                <div class="p-4 sm:p-5 flex-1 flex flex-col">
                  <div class="flex items-start justify-between gap-3 mb-3">
                    <h2 class="font-semibold text-base md:text-lg text-gray-900 leading-snug">
                      <?php echo htmlspecialchars($res['title']); ?>
                    </h2>
                    <div class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-emerald-50 text-emerald-700 group-hover:bg-emerald-100">
                      <i class="fas fa-file-alt text-sm"></i>
                    </div>
                  </div>

                  <div class="flex flex-wrap items-center gap-2 text-[0.68rem] md:text-xs text-gray-500 mb-3">
                    <?php if (!empty($res['category'])): ?>
                      <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium">
                        <i class="fas fa-tag text-[0.6rem]"></i>
                        <?php echo htmlspecialchars($res['category']); ?>
                      </span>
                    <?php endif; ?>
                    <?php if (!empty($res['year'])): ?>
                      <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">
                        <i class="fas fa-calendar-alt text-[0.6rem]"></i>
                        <?php echo htmlspecialchars($res['year']); ?>
                      </span>
                    <?php endif; ?>
                    <span class="inline-flex items-center gap-1 text-gray-500">
                      <i class="far fa-clock text-[0.6rem]"></i>
                      Added <?php echo htmlspecialchars(date('M j, Y', strtotime($res['created_at']))); ?>
                    </span>
                  </div>

                  <?php if (!empty($res['description'])): ?>
                    <p class="text-sm text-gray-700 mb-4 line-clamp-3">
                      <?php echo htmlspecialchars($res['description']); ?>
                    </p>
                  <?php else: ?>
                    <p class="text-sm text-gray-500 mb-4">
                      No description provided for this document.
                    </p>
                  <?php endif; ?>
                </div>

                <div class="px-4 sm:px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                  <div class="flex items-center gap-2 text-[0.7rem] md:text-xs text-gray-500">
                    <i class="fas fa-shield-alt text-emerald-500"></i>
                    <span>Official 1000 Hills Rugby document</span>
                  </div>
                  <a
                    href="<?php echo htmlspecialchars($res['file_path']); ?>"
                    target="_blank"
                    class="inline-flex items-center gap-2 text-xs md:text-sm font-semibold text-white bg-[#dcbb26] hover:bg-[#c1a321] px-3 py-1.5 rounded-full shadow-sm hover:shadow-md transition-all"
                  >
                    <i class="fas fa-arrow-down text-[0.7rem] md:text-xs"></i>
                    <span>View / Download</span>
                  </a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
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

