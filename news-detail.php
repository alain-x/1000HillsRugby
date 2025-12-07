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
$sql = "SELECT title, category, date_published, main_image_path, content FROM articles WHERE id = ?";
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

// Get full URL of the news detail page (force HTTPS)
$page_url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Prepare description from article content (first 160 characters)
$description = !empty($article['content']) ? substr(strip_tags($article['content']), 0, 160) : '1000 Hills Rugby news article';
$description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

// Prepare image URL for social sharing with validation
function getValidImageUrl($image_path) {
    $base_url = "https://" . $_SERVER['HTTP_HOST'];
    
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        return $image_path;
    }
    
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($image_path, '/');
    
    if (file_exists($full_path) && is_readable($full_path)) {
        $image_info = @getimagesize($full_path);
        if ($image_info !== false) {
            return $base_url . '/' . ltrim($image_path, '/');
        }
    }
    
    return false;
}

// Get valid share image
$share_image = !empty($article['main_image_path']) ? getValidImageUrl($article['main_image_path']) : false;

// Fallback to logo if main image is invalid
if (!$share_image) {
    $share_image = "https://$_SERVER[HTTP_HOST]/images/1000-hills-logo.png";
    $fallback_path = $_SERVER['DOCUMENT_ROOT'] . '/images/1000-hills-logo.png';
    if (!file_exists($fallback_path)) {
        $share_image = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="./images/t_icon.png" type="image/png">
    <title><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?> | 1000 Hills Rugby</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo $description; ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo $page_url; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?> | 1000 Hills Rugby">
    <meta property="og:description" content="<?php echo $description; ?>">
    <?php if ($share_image): ?>
    <meta property="og:image" content="<?php echo $share_image; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="1000 Hills Rugby">
    <meta property="article:published_time" content="<?php echo date('c', strtotime($article['date_published'])); ?>">
    <meta name="twitter:card" content="<?php echo $share_image ? 'summary_large_image' : 'summary'; ?>">
    <meta name="twitter:site" content="@1000HillsRugby">
    <meta name="twitter:creator" content="@1000HillsRugby">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?> | 1000 Hills Rugby">
    <meta name="twitter:description" content="<?php echo $description; ?>">
    <?php if ($share_image): ?>
    <meta name="twitter:image" content="<?php echo $share_image; ?>">
    <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?php echo $page_url; ?>">
    
    <!-- CSS & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom responsive styles */
        .article-content p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .image-collage {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        @media (max-width: 640px) {
            .navbar-logo {
                width: 50% !important;
            }
            
            .article-title {
                font-size: 1.5rem !important;
                line-height: 1.3 !important;
            }
            
            .section-title {
                font-size: 1.25rem !important;
            }
            
            .social-share {
                justify-content: space-between;
            }
            
            .social-share a {
                font-size: 1.5rem !important;
            }
        }
        
        @media (min-width: 641px) and (max-width: 1023px) {
            .article-title {
                font-size: 2rem !important;
            }
        }
        
        /* Smooth scrolling for anchor links */
        html {
            scroll-behavior: smooth;
        }
        
        /* Better focus states for accessibility */
        a:focus, button:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">
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
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="fixed inset-0 z-40 bg-white transform translate-x-full lg:hidden transition-transform duration-300 ease-in-out pt-16 overflow-y-auto">
        <div class="px-4 py-6 space-y-6">
            <a href="./" class="block py-2 text-lg font-medium text-gray-800 border-b border-gray-100">Home</a>
            <a href="./about" class="block py-2 text-lg font-medium text-gray-800 border-b border-gray-100">About</a>
            <a href="./program" class="block py-2 text-lg font-medium text-gray-800 border-b border-gray-100">Programs</a>
            <a href="./community" class="block py-2 text-lg font-medium text-gray-800 border-b border-gray-100">Community</a>
            <a href="./shop" class="block py-2 text-lg font-medium text-gray-800 border-b border-gray-100">Shop</a>
            
            <!-- Mobile Dropdowns -->
            <div class="border-b border-gray-100 pb-2">
                <button class="mobile-dropdown-toggle w-full flex justify-between items-center py-2 text-lg font-medium text-gray-800 focus:outline-none">
                    Education <i class="fas fa-chevron-down text-sm"></i>
                </button>
                <div class="mobile-dropdown-content hidden pl-4 mt-2 space-y-2">
                    <a href="./education" class="block py-2 text-base text-gray-600">Education</a>
                    <a href="./Foundation" class="block py-2 text-base text-gray-600">Career Foundation</a>
                </div>
            </div>
            
            <!-- More mobile dropdowns... -->
        </div>
    </div>

    <!-- Main Content -->
    <main class="pt-16 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back Button -->
            <a href="news" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6 transition-colors text-sm sm:text-base focus:outline-none">
                <i class="fas fa-arrow-left mr-2"></i> Back to News
            </a>
            
            <!-- Article Header -->
            <header class="mb-8">
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-2 leading-tight article-title"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="flex items-center text-sm text-gray-500">
                    <span><?php echo date("F j, Y", strtotime($article['date_published'])); ?></span>
                    <?php if (!empty($article['category'])): ?>
                        <span class="mx-2">•</span>
                        <span class="bg-gray-200 px-2 py-1 rounded-full text-xs"><?php echo htmlspecialchars($article['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Social Sharing -->
            <div class="flex flex-wrap gap-4 sm:gap-6 my-6 social-share">
                <span class="text-gray-700 text-sm sm:text-base self-center">Share:</span>
                <!-- Facebook -->
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($page_url); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 transition-colors focus:outline-none" aria-label="Share on Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 sm:w-7 sm:h-7 fill-current">
                        <path d="M22.675 0H1.325C.593 0 0 .593 0 1.326v21.348C0 23.407.593 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24h-1.918c-1.504 0-1.796.715-1.796 1.765v2.316h3.59l-.467 3.622h-3.123V24h6.116C23.407 24 24 23.407 24 22.674V1.326C24 .593 23.407 0 22.675 0z"/>
                    </svg>
                </a>
                <!-- Twitter/X -->
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($page_url); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" rel="noopener noreferrer" class="text-gray-900 hover:text-black transition-colors focus:outline-none" aria-label="Share on X (Twitter)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 sm:w-7 sm:h-7 fill-current">
                        <path d="M18.154 2H21L14.25 10.01L22 22H15.828L11.11 14.94L5.7 22H3L10.18 13.39L2.75 2H9.078L13.355 8.46L18.154 2ZM17.074 20.08H18.78L7.78 3.83H5.94L17.074 20.08Z"/>
                    </svg>
                </a>
                <!-- LinkedIn -->
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($page_url); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:text-blue-900 transition-colors focus:outline-none" aria-label="Share on LinkedIn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 sm:w-7 sm:h-7 fill-current">
                        <path d="M20.447 20.452H17.21v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.447-2.136 2.942v5.664H9.0V9.0h3.112v1.561h.045c.434-.822 1.494-1.69 3.073-1.69 3.287 0 3.894 2.164 3.894 4.977v6.604zM5.337 7.433a1.81 1.81 0 1 1 0-3.62 1.81 1.81 0 0 1 0 3.62zM6.777 20.452H3.893V9.0h2.884v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.225.792 24 1.771 24h20.451C23.2 24 24 23.225 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </a>
                <!-- WhatsApp -->
                <a href="whatsapp://send?text=<?php echo urlencode($article['title'] . ' ' . $page_url); ?>" target="_blank" rel="noopener noreferrer" class="text-green-500 hover:text-green-700 transition-colors focus:outline-none" aria-label="Share on WhatsApp">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 sm:w-7 sm:h-7 fill-current">
                        <path d="M20.52 3.484A11.82 11.82 0 0 0 12.012 0C5.74 0 .74 4.999.74 11.27c0 1.989.52 3.93 1.51 5.647L0 24l7.26-2.222a11.29 11.29 0 0 0 4.75 1.058h.005c6.27 0 11.27-4.999 11.27-11.27a11.19 11.19 0 0 0-3.765-8.082zM12.015 21.22h-.004a9.43 9.43 0 0 1-4.79-1.312l-.343-.204-4.309 1.319 1.382-4.203-.224-.343a9.43 9.43 0 0 1-1.45-5.024c0-5.205 4.238-9.443 9.447-9.443 2.522 0 4.89.983 6.675 2.77a9.39 9.39 0 0 1 2.77 6.673c0 5.205-4.238 9.443-9.454 9.443zm5.17-7.118c-.283-.141-1.676-.828-1.936-.922-.26-.096-.45-.141-.64.141-.19.283-.734.922-.9 1.112-.166.19-.333.21-.616.07-.283-.141-1.193-.44-2.27-1.404-.84-.75-1.404-1.676-1.57-1.959-.166-.283-.018-.437.124-.578.127-.127.283-.333.424-.5.141-.166.188-.283.283-.472.094-.19.047-.355-.024-.497-.07-.141-.64-1.541-.878-2.112-.23-.553-.465-.478-.64-.487l-.547-.01c-.19 0-.497.07-.758.355-.26.283-1.0.977-1.0 2.383 0 1.406 1.03 2.763 1.175 2.955.141.19 2.03 3.1 4.92 4.34.688.297 1.224.474 1.642.607.69.22 1.318.189 1.816.115.554-.082 1.676-.684 1.914-1.343.237-.658.237-1.223.166-1.343-.07-.119-.26-.188-.543-.329z"/>
                    </svg>
                </a>
                <!-- Copy link -->
                <button class="copy-link-btn text-gray-600 hover:text-gray-800 transition-colors focus:outline-none" data-url="<?php echo htmlspecialchars($page_url, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Copy link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 sm:w-7 sm:h-7 fill-current">
                        <path d="M16 1H4C2.897 1 2 1.897 2 3v14h2V3h12V1z"/>
                        <path d="M19 5H8C6.897 5 6 5.897 6 7v14c0 1.103.897 2 2 2h11c1.103 0 2-.897 2-2V7c0-1.103-.897-2-2-2zm0 16H8V7h11v14z"/>
                    </svg>
                </button>
            </div>
            
            <!-- Main Image -->
            <?php if (!empty($article['main_image_path'])): ?>
                <figure class="my-8 flex justify-center">
                    <img class="w-full md:w-auto max-w-full max-h-[80vh] object-contain rounded-lg shadow-md" src="<?php echo htmlspecialchars($article['main_image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (!empty($article['image_caption'])): ?>
                        <figcaption class="text-center text-sm text-gray-500 mt-2"><?php echo htmlspecialchars($article['image_caption'], ENT_QUOTES, 'UTF-8'); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>
            
            <!-- Article Content -->
            <article class="prose max-w-none lg:prose-lg article-content">
                <?php if (!empty($article['content'])): ?>
                    <div class="mb-8">
                        <?php echo nl2br(htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Article Sections -->
                <?php foreach ($sections as $index => $section): ?>
                    <section class="mb-10" id="section-<?php echo $index; ?>">
                        <?php if (!empty($section['subtitle'])): ?>
                            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4 section-title"><?php echo htmlspecialchars($section['subtitle'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['content'])): ?>
                            <div class="text-gray-700 mb-4">
                                <?php echo nl2br(htmlspecialchars($section['content'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($section['image_path'])): ?>
                            <?php
                            $mediaItems = array_filter(array_map('trim', explode(',', $section['image_path'])));
                            $mediaCount = count($mediaItems);
                            $videoExts = ['mp4','webm','ogg','mov'];
                            ?>
                            <?php if ($mediaCount === 1): ?>
                                <?php
                                    $item = $mediaItems[0];
                                    $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                                    $isVideo = in_array($ext, $videoExts);
                                    // Detect YouTube
                                    $urlParts = parse_url($item);
                                    $host = strtolower($urlParts['host'] ?? '');
                                    $isYouTube = (strpos($host, 'youtube.com') !== false) || (strpos($host, 'youtu.be') !== false) || (strpos($host, 'youtube-nocookie.com') !== false);
                                    $youtubeEmbed = '';
                                    if ($isYouTube) {
                                        $youtubeId = '';
                                        if (!empty($urlParts['host']) && strpos($host, 'youtu.be') !== false) {
                                            $pathParts = array_values(array_filter(explode('/', $urlParts['path'] ?? '')));
                                            $youtubeId = $pathParts[0] ?? '';
                                        }
                                        parse_str($urlParts['query'] ?? '', $q);
                                        if (!$youtubeId && !empty($q['v'])) { $youtubeId = $q['v']; }
                                        $pathParts = array_values(array_filter(explode('/', $urlParts['path'] ?? '')));
                                        $shortsIdx = array_search('shorts', $pathParts);
                                        if (!$youtubeId && $shortsIdx !== false && isset($pathParts[$shortsIdx + 1])) { $youtubeId = $pathParts[$shortsIdx + 1]; }
                                        $embedIdx = array_search('embed', $pathParts);
                                        if (!$youtubeId && $embedIdx !== false && isset($pathParts[$embedIdx + 1])) { $youtubeId = $pathParts[$embedIdx + 1]; }
                                        if ($youtubeId) {
                                            $youtubeEmbed = 'https://www.youtube-nocookie.com/embed/' . htmlspecialchars($youtubeId, ENT_QUOTES, 'UTF-8');
                                        }
                                    }
                                ?>
                                <figure class="my-6">
                                    <?php if ($isYouTube && $youtubeEmbed): ?>
                                        <div class="relative w-full" style="padding-top: 56.25%;">
                                            <iframe src="<?php echo $youtubeEmbed; ?>" class="absolute top-0 left-0 w-full h-full rounded-lg shadow-md" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                        </div>
                                    <?php elseif ($isVideo): ?>
                                        <video class="w-full md:w-auto max-w-full max-h-[80vh] object-contain rounded-lg shadow-md" controls playsinline preload="metadata">
                                            <source src="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" type="video/<?php echo $ext === 'mov' ? 'mp4' : $ext; ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php else: ?>
                                        <img class="w-full md:w-auto max-w-full max-h-[80vh] object-contain rounded-lg shadow-md" src="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" alt="Article Media">
                                    <?php endif; ?>
                                </figure>
                            <?php else: ?>
                                <!-- Mixed Media Gallery -->
                                <div class="image-collage my-6">
                                    <?php foreach ($mediaItems as $item): ?>
                                        <?php
                                            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                                            $isVideo = in_array($ext, $videoExts);
                                            // Detect YouTube
                                            $urlParts = parse_url($item);
                                            $host = strtolower($urlParts['host'] ?? '');
                                            $isYouTube = (strpos($host, 'youtube.com') !== false) || (strpos($host, 'youtu.be') !== false) || (strpos($host, 'youtube-nocookie.com') !== false);
                                            $youtubeEmbed = '';
                                            if ($isYouTube) {
                                                $youtubeId = '';
                                                if (!empty($urlParts['host']) && strpos($host, 'youtu.be') !== false) {
                                                    $pathParts = array_values(array_filter(explode('/', $urlParts['path'] ?? '')));
                                                    $youtubeId = $pathParts[0] ?? '';
                                                }
                                                parse_str($urlParts['query'] ?? '', $q);
                                                if (!$youtubeId && !empty($q['v'])) { $youtubeId = $q['v']; }
                                                $pathParts = array_values(array_filter(explode('/', $urlParts['path'] ?? '')));
                                                $shortsIdx = array_search('shorts', $pathParts);
                                                if (!$youtubeId && $shortsIdx !== false && isset($pathParts[$shortsIdx + 1])) { $youtubeId = $pathParts[$shortsIdx + 1]; }
                                                $embedIdx = array_search('embed', $pathParts);
                                                if (!$youtubeId && $embedIdx !== false && isset($pathParts[$embedIdx + 1])) { $youtubeId = $pathParts[$embedIdx + 1]; }
                                                if ($youtubeId) {
                                                    $youtubeEmbed = 'https://www.youtube-nocookie.com/embed/' . htmlspecialchars($youtubeId, ENT_QUOTES, 'UTF-8');
                                                }
                                            }
                                        ?>
                                        <figure class="overflow-hidden rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 flex items-center justify-center bg-black/5">
                                            <?php if ($isYouTube && $youtubeEmbed): ?>
                                                <div class="relative w-full" style="padding-top: 56.25%;">
                                                    <iframe src="<?php echo $youtubeEmbed; ?>" class="absolute top-0 left-0 w-full h-full rounded-lg" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                                </div>
                                            <?php elseif ($isVideo): ?>
                                                <video class="w-full max-h-80 object-contain" controls playsinline preload="metadata">
                                                    <source src="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" type="video/<?php echo $ext === 'mov' ? 'mp4' : $ext; ?>">
                                                    Your browser does not support the video tag.
                                                </video>
                                            <?php else: ?>
                                                <img class="w-full max-h-80 object-contain" src="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" alt="Article Media">
                                            <?php endif; ?>
                                        </figure>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </article>
            
            <!-- Back to Top Button -->
            <div class="mt-12 text-center">
                <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-full hover:bg-green-700 transition-colors focus:outline-none">
                    <i class="fas fa-arrow-up mr-2"></i> Back to Top
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Footer sections... -->
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-sm text-gray-400">
                &copy; <?php echo date('Y'); ?> 1000 Hills Rugby. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="news-detail.js"></script>

            const isOpen = mobileMenu.classList.toggle('translate-x-full');
            menuIcon.className = isOpen ? 'fas fa-bars text-xl' : 'fas fa-times text-xl';
            document.body.style.overflow = isOpen ? 'auto' : 'hidden';
        });
        
        // Mobile dropdown toggles
        document.querySelectorAll('.mobile-dropdown-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                const icon = button.querySelector('i');
                
                content.classList.toggle('hidden');
                icon.className = content.classList.contains('hidden') ? 
                    'fas fa-chevron-down text-sm' : 'fas fa-chevron-up text-sm';
            });
        });
        
        // Copy URL to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('translate-x-full');
                menuIcon.className = 'fas fa-bars text-xl';
                document.body.style.overflow = 'auto';
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                mobileMenu.classList.add('translate-x-full');
                menuIcon.className = 'fas fa-bars text-xl';
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>
</html>