<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="Rugby News">
    <meta property="og:description" content="Latest rugby news and updates.">
    <meta property="og:image" content="">
    <meta property="og:url" content="">
    <meta property="og:type" content="article">
    <title>Rugby News</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        .article-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    
    <main class="container mx-auto px-4 py-6">
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
        <div class="grid grid-cols-1 mt-[60px] sm:grid-cols-2 lg:grid-cols-3 gap-6" id="news-cards">
            <?php
$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "SELECT a.id, a.title, a.category, a.date_published, a.main_image_path, a.author, 
                           ad.subtitle, ad.content, ad.image_path
                    FROM articles a
                    LEFT JOIN article_details ad ON a.id = ad.article_id
                    ORDER BY a.date_published DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $articles = [];

                while ($row = $result->fetch_assoc()) {
                    $article_id = $row["id"];
                    if (!isset($articles[$article_id])) {
                        $articles[$article_id] = [
                            "title" => $row["title"],
                            "category" => $row["category"],
                            "date_published" => date("F j, Y", strtotime($row["date_published"])),
                            "main_image_path" => $row["main_image_path"],
                            "author" => $row["author"],
                            "details" => []
                        ];
                    }
                    if (!empty($row["subtitle"]) || !empty($row["content"]) || !empty($row["image_path"])) {
                        $articles[$article_id]["details"][] = [
                            "subtitle" => $row["subtitle"],
                            "content" => $row["content"],
                            "image_path" => $row["image_path"]
                        ];
                    }
                }

                foreach ($articles as $id => $article) {
                    echo '<div class="article-card bg-white rounded-lg shadow-sm overflow-hidden cursor-pointer" onclick="updateURLAndShowArticle(\'' . $id . '\')">';
                    echo '<img class="w-full h-49 object-cover" src="' . $article["main_image_path"] . '" alt="' . htmlspecialchars($article["title"]) . '" />';
                    echo '<div class="p-4">';
                    echo '<h2 class="text-xl font-bold mb-2">' . $article["title"] . '</h2>';
                    echo '<p class="text-sm text-gray-600 mb-2 font-bold">' . $article["category"] . '</p>';
                    echo '<p class="text-gray-500 text-sm">' . $article["date_published"] . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo "<p class='text-center text-xl font-semibold mt-10'>No articles found.</p>";
            }

            $conn->close();
            ?>
        </div>

        <!-- Full Article View -->
        <div id="full-article-view" class="hidden fixed inset-0 bg-white p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto relative">
                <button onclick="hideFullArticle()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    
                </button>
                <div id="full-article-content"></div>
            </div>
        </div>
    </main>

    <footer class="bg-[#1b75bc] text-white py-6 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2023 Rugby News. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const articles = <?php echo json_encode($articles); ?>;

        function updateURLAndShowArticle(articleId) {
            history.pushState(null, null, `#${articleId}`);
            showFullArticle(articleId);
        }

        function showFullArticle(articleId) {
            if (!articles[articleId]) return;

            const article = articles[articleId];
            const fullArticleContent = `
                <div class="max-w-7xl mx-auto p-4 mt-[60px]">
                    <a href="#" onclick="goBack()" class="text-lg font-bold">&larr; BACK</a>
                    <h1 class="text-4xl font-bold mt-4">${article.title}</h1>
                    <p class="text-gray-500 text-sm mt-2">${article["date_published"]}</p>
                    <div class="flex space-x-4 mt-4">
                        <button onclick="shareArticle('email')" class="bg-gray-200 p-2 rounded"><i class="fas fa-envelope"></i></button>
                        <button onclick="shareArticle('facebook')" class="bg-gray-200 p-2 rounded"><i class="fab fa-facebook-f"></i></button>
                        <button onclick="shareArticle('twitter')" class="bg-gray-200 p-2 rounded"><i class="fab fa-x-twitter"></i></button>
                        <button onclick="shareArticle('linkedin')" class="bg-gray-200 p-2 rounded"><i class="fab fa-linkedin-in"></i></button>
                    </div>
                    <img class="w-full h-auto object-cover my-6" src="${article.main_image_path}" alt="${article.title}" />
                    <div class="space-y-4">
                        ${article.details.map(detail => `
                            ${detail.subtitle ? `<h3 class="text-xl font-semibold">${detail.subtitle}</h3>` : ''}
                            ${detail.content ? `<p class="text-gray-700">${detail.content}</p>` : ''}
                            ${detail.image_path ? `<img class="w-full h-auto object-cover rounded-lg mt-4" src="${detail.image_path}" alt="${detail.subtitle || 'Image'}" />` : ''}
                        `).join('')}
                    </div>
                </div>
            `;

            document.getElementById('full-article-content').innerHTML = fullArticleContent;
            document.getElementById('full-article-view').classList.remove('hidden');
        }

        function goBack() {
            document.getElementById('full-article-view').classList.add('hidden');
        }

        function shareArticle(platform) {
            const url = window.location.href;
            switch (platform) {
                case 'email':
                    window.location.href = `mailto:?subject=Check out this article&body=${url}`;
                    break;
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
                    break;
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?url=${url}`, '_blank');
                    break;
                case 'linkedin':
                    window.open(`https://www.linkedin.com/shareArticle?mini=true&url=${url}`, '_blank');
                    break;
            }
        }
    </script>
</body>
</html>
