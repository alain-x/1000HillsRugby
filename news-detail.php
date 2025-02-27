<?php
$conn = new mysqli("localhost", "hillsrug_gasore", "M00dle??", "hillsrug_db");

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Get news ID from URL
$news_id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch main news article
$sql = "SELECT title, subtitle, category, content, img, created_at FROM news WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $news_id);
$stmt->execute();
$result = $stmt->get_result();
$news = $result->fetch_assoc();

// Fetch additional sections for the news article
$sql_sections = "SELECT id, title, subtitle, content FROM news_sections WHERE news_id = ?";
$stmt_sections = $conn->prepare($sql_sections);
$stmt_sections->bind_param("s", $news_id);
$stmt_sections->execute();
$sections_result = $stmt_sections->get_result();
$sections = $sections_result->fetch_all(MYSQLI_ASSOC);

// Fetch images for each section
$section_images = [];
foreach ($sections as $section) {
    $section_id = $section['id'];
    $sql_images = "SELECT img FROM news_images WHERE section_id = ?";
    $stmt_images = $conn->prepare($sql_images);
    $stmt_images->bind_param("s", $section_id);
    $stmt_images->execute();
    $images_result = $stmt_images->get_result();
    $section_images[$section_id] = $images_result->fetch_all(MYSQLI_ASSOC);
}

$stmt->close();
$stmt_sections->close();
$conn->close();

// Redirect if no news found
if (!$news) {
    header("Location: news.php");
    exit;
}

// Generate Share URL
$base_url = "https://www.1000hillsrugby.rw/news-detail?id=" . $news_id;
$share_title = urlencode($news['title']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?></title>
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
        <a href="news.php" class="flex items-center text-blue-500 hover:text-blue-700 mb-4">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>

        <!-- Main News Section -->
        <div>
            <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($news['title']); ?></h1>
            <?php if (!empty($news['subtitle'])): ?>
                <h5 class="text-gray-500 mt-1"><?php echo htmlspecialchars($news['subtitle']); ?></h5>
            <?php endif; ?>
            <p class="text-gray-400 text-sm mt-2"><?php echo date("l, d F Y", strtotime($news['created_at'])); ?></p>

            <!-- Share Icons -->
            <div class="share-icons mt-4">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($base_url); ?>&title=<?php echo $share_title; ?>" target="_blank" class="mr-4 text-blue-600 hover:text-blue-800 text-lg">
                    <i class="fab fa-facebook"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($base_url); ?>&text=<?php echo $share_title; ?>" target="_blank" class="mr-4 text-blue-400 hover:text-blue-600 text-lg">
                    <i class="fab fa-twitter"></i> Twitter
                </a>
                <a href="https://api.whatsapp.com/send?text=<?php echo $share_title . ' ' . urlencode($base_url); ?>" target="_blank" class="mr-4 text-green-500 hover:text-green-700 text-lg">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($base_url); ?>" target="_blank" class="text-blue-700 hover:text-blue-900 text-lg">
                    <i class="fab fa-linkedin"></i> LinkedIn
                </a>
            </div>

            <!-- Main Image -->
            <?php if (!empty($news['img'])): ?>
                <img src="<?php echo htmlspecialchars($news['img']); ?>" alt="News Image" class="w-full mt-4 rounded-lg shadow">
            <?php endif; ?>

            <!-- Main Content -->
            <p class="mt-6 text-lg"><?php echo nl2br(htmlspecialchars($news['content'])); ?></p>
        </div>

        <!-- Additional Sections -->
        <?php if (!empty($sections)): ?>
            <?php foreach ($sections as $section): ?>
                <div class="mt-8">
                    <!-- Section Images -->
                    <?php if (!empty($section_images[$section['id']])): ?>
                        <?php
                        $images = $section_images[$section['id']];
                        $image_count = count($images);
                        ?>
                        <?php if ($image_count == 1): ?>
                            <!-- Single Image (Large) -->
                            <img src="<?php echo htmlspecialchars($images[0]['img']); ?>" alt="Section Image" class="w-full rounded-lg shadow">
                        <?php else: ?>
                            <!-- Multiple Images (Collage) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                                <?php foreach ($images as $image): ?>
                                    <img src="<?php echo htmlspecialchars($image['img']); ?>" alt="Section Image" class="rounded-lg shadow">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Section Title and Content -->
                    <h4 class="text-2xl font-semibold mt-6"><?php echo htmlspecialchars($section['title']); ?></h4>
                    <?php if (!empty($section['subtitle'])): ?>
                        <h5 class="text-gray-500 mt-1"><?php echo htmlspecialchars($section['subtitle']); ?></h5>
                    <?php endif; ?>
                    <p class="mt-4 text-lg"><?php echo nl2br(htmlspecialchars($section['content'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>