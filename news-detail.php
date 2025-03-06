<?php
header('Content-Type: text/html; charset=utf-8');


// Database connection
$conn = new mysqli("localhost", "hillsrug_gasore", "M00dle??", "hillsrug_db", 3306);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Get article ID from URL
$article_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$article_id) {
    header("Location: news.php");
    exit;
}

// Fetch main article details
$sql = "SELECT title, category, date_published, main_image_path FROM articles WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $article_id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();
$stmt->close();

// Redirect if no article is found
if (!$article) {
    header("Location: news.php");
    exit;
}

// Fetch all sections related to this article
$sql_sections = "SELECT subtitle, content, image_path FROM article_details WHERE article_id = ?";
$stmt_sections = $conn->prepare($sql_sections);
$stmt_sections->bind_param("s", $article_id);
$stmt_sections->execute();
$result_sections = $stmt_sections->get_result();
$sections = $result_sections->fetch_all(MYSQLI_ASSOC);
$stmt_sections->close();

$conn->close();

// Get full URL of the news detail page
$page_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./images/t_icon.png" type="image/png" />
    <title><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?> | 1000 Hills Rugby</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 text-gray-900">
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
              >Education</a
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
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none"
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
          class="hover:text-green-600 hover:border-b-2 hover:border-green-600 transition-all duration-300 pointer-events-none"
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
              >Women's Senior</a
            >
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
    </div>
    </nav>
 

    <div class="max-w-5xl mt-[80px] mx-auto p-4">
        <!-- Back Button -->
        <a href="news" class="flex items-center text-blue-500 hover:text-blue-700 mb-4">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>

        <!-- Article Title and Metadata -->
        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-gray-500 text-sm mt-2"><?php echo date("F j, Y", strtotime($article['date_published'])); ?></p>

        <!-- Social Share Buttons -->
        <div class="flex gap-4 my-4">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($page_url); ?>&title=<?php echo urlencode($article['title']); ?>" target="_blank" class="text-blue-600 text-2xl">
                <i class="fab fa-facebook"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($page_url); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" class="text-blue-400 text-2xl">
                <i class="fab fa-twitter"></i>
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($page_url); ?>" target="_blank" class="text-blue-700 text-2xl">
                <i class="fab fa-linkedin"></i>
            </a>
            <a href="whatsapp://send?text=<?php echo urlencode($article['title'] . ' ' . $page_url); ?>" target="_blank" class="text-green-500 text-2xl">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="#" onclick="copyToClipboard('<?php echo $page_url; ?>')" class="text-gray-600 text-2xl">
                <i class="fas fa-link"></i>
            </a>
        </div>

        <!-- Main Image -->
        <?php if (!empty($article['main_image_path'])): ?>
            <img class="w-full h-auto object-cover my-6" src="<?php echo htmlspecialchars($article['main_image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <!-- Article Sections -->
        <?php foreach ($sections as $section): ?>
            <section class="my-6">
                <?php if (!empty($section['subtitle'])): ?>
                    <h3 class="text-2xl font-semibold"><?php echo htmlspecialchars($section['subtitle'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php endif; ?>
                <?php if (!empty($section['content'])): ?>
                    <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($section['content'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php endif; ?>
                <?php if (!empty($section['image_path'])): ?>
                    <?php
                    // Handle multiple images (comma-separated)
                    $images = explode(',', $section['image_path']);
                    $imageCount = count($images);
                    ?>
                    <?php if ($imageCount === 1): ?>
                        <!-- Single Image: Display Large -->
                        <img class="w-full h-auto object-cover rounded-lg mt-4" src="<?php echo htmlspecialchars(trim($images[0]), ENT_QUOTES, 'UTF-8'); ?>" alt="Section Image">
                    <?php else: ?>
                        <!-- Multiple Images: Display as Collage -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                         <?php foreach ($images as $image): ?>
                         <div class="overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
                         <img class="w-full h-80 object-cover rounded-lg" src="<?php echo htmlspecialchars(trim($image), ENT_QUOTES, 'UTF-8'); ?>" alt="Section Image">
                       </div>
                   <?php endforeach; ?>
              </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert("Link copied to clipboard!");
            }).catch(err => console.error("Failed to copy: ", err));
        }
    </script>
    
    <script src="./index.js"></script>
</body>
</html>