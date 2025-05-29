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
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 w-full px-4 z-50 h-16 flex items-center justify-between bg-white/90 backdrop-blur-lg shadow-md transition-all duration-300">
        <!-- Logo -->
        <div class="flex-shrink-0">
            <a href="./" class="focus:outline-none">
                <img class="h-12 w-auto hover:scale-105 transition-transform" src="./images/1000-hills-logo.png" alt="1000 Hills Rugby">
            </a>
        </div>
        
        <!-- Desktop Navigation -->
        <div class="hidden lg:flex items-center space-x-6">
            <a href="./" class="text-sm font-medium text-gray-800 hover:text-green-600 transition-colors">Home</a>
            <a href="./about" class="text-sm font-medium text-gray-800 hover:text-green-600 transition-colors">About</a>
            <a href="./program" class="text-sm font-medium text-gray-800 hover:text-green-600 transition-colors">Programs</a>
            <a href="./community" class="text-sm font-medium text-gray-800 hover:text-green-600 transition-colors">Community</a>
            <a href="./shop" class="text-sm font-medium text-gray-800 hover:text-green-600 transition-colors">Shop</a>
            
            <!-- Dropdown Menus -->
            <div class="relative group">
                <button class="text-sm font-medium text-gray-800 hover:text-green-600 transition-colors flex items-center focus:outline-none">
                    Education <i class="fas fa-chevron-down ml-1 text-xs"></i>
                </button>
                <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                    <a href="./education" class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100">Education</a>
                    <a href="./Foundation" class="block px-4 py-2 text-sm text-gray-800 hover:bg-gray-100">Career Foundation</a>
                </div>
            </div>
            
            <!-- More dropdowns... -->
        </div>
        
        <!-- Mobile Menu Button -->
        <div class="lg:hidden">
            <button id="mobile-menu-button" class="text-gray-800 focus:outline-none">
                <i class="fas fa-bars text-xl" id="menu-icon"></i>
            </button>
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
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($page_url); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 text-xl sm:text-2xl transition-colors focus:outline-none" aria-label="Share on Facebook">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($page_url); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-600 text-xl sm:text-2xl transition-colors focus:outline-none" aria-label="Share on Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($page_url); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:text-blue-900 text-xl sm:text-2xl transition-colors focus:outline-none" aria-label="Share on LinkedIn">
                    <i class="fab fa-linkedin"></i>
                </a>
                <a href="whatsapp://send?text=<?php echo urlencode($article['title'] . ' ' . $page_url); ?>" target="_blank" rel="noopener noreferrer" class="text-green-500 hover:text-green-700 text-xl sm:text-2xl transition-colors focus:outline-none" aria-label="Share on WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <button onclick="copyToClipboard('<?php echo $page_url; ?>')" class="text-gray-600 hover:text-gray-800 text-xl sm:text-2xl transition-colors focus:outline-none" aria-label="Copy link">
                    <i class="fas fa-link"></i>
                </button>
            </div>
            
            <!-- Main Image -->
            <?php if (!empty($article['main_image_path'])): ?>
                <figure class="my-8">
                    <img class="w-full h-auto max-h-[70vh] object-cover rounded-lg shadow-md" src="<?php echo htmlspecialchars($article['main_image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
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
                            $images = array_filter(array_map('trim', explode(',', $section['image_path'])));
                            $imageCount = count($images);
                            ?>
                            <?php if ($imageCount === 1): ?>
                                <!-- Single Image -->
                                <figure class="my-6">
                                    <img class="w-full h-auto max-h-[70vh] object-cover rounded-lg shadow-md" src="<?php echo htmlspecialchars($images[0], ENT_QUOTES, 'UTF-8'); ?>" alt="Article Image">
                                </figure>
                            <?php else: ?>
                                <!-- Image Gallery -->
                                <div class="image-collage my-6">
                                    <?php foreach ($images as $image): ?>
                                        <figure class="overflow-hidden rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                                            <img class="w-full h-48 sm:h-56 md:h-64 object-cover" src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="Article Image">
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

    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        
        mobileMenuButton.addEventListener('click', () => {
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
            }
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
        });
    }

    // Initialize clickable links
    document.addEventListener('DOMContentLoaded', function() {
        const contentElements = document.querySelectorAll('.article-content');
        contentElements.forEach(element => {
            element.innerHTML = makeLinksClickable(element.innerHTML);
        });
    });
</script>
</body>
</html>