<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1000 Hills Rugby | News</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        /* Custom styles for cards and expanded view */
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
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="news-cards">
            <?php
            $servername = "localhost:3306";
            $username = "hillsrug_gasore";
            $password = "M00dle??";
            $dbname = "hillsrug_db";

            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Fetch articles and their details
            $sql = "SELECT a.id, a.title, a.category, a.date_published, a.main_image_path, ad.subtitle, ad.content, ad.image_path
                    FROM articles a
                    LEFT JOIN article_details ad ON a.id = ad.article_id
                    ORDER BY a.date_published DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $articles = [];

                // Group article details by article ID
                while ($row = $result->fetch_assoc()) {
                    $article_id = $row["id"];
                    if (!isset($articles[$article_id])) {
                        $articles[$article_id] = [
                            "title" => $row["title"],
                            "category" => $row["category"],
                            "date_published" => $row["date_published"],
                            "main_image_path" => $row["main_image_path"],
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

                // Display article cards
                foreach ($articles as $id => $article) {
                    echo '<div class="article-card bg-white rounded-lg shadow-sm overflow-hidden cursor-pointer" onclick="showFullArticle(' . $id . ')">';
                    echo '<img class="w-full h-49 object-cover" src="' . htmlspecialchars($article["main_image_path"]) . '" alt="' . htmlspecialchars($article["title"]) . '" />';
                    echo '<div class="p-4">';
                    echo '<h2 class="text-xl font-bold mb-2">' . htmlspecialchars($article["title"]) . '</h2>';
                    echo '<p class="text-gray-500 text-sm">' . date("F j, Y", strtotime($article["date_published"])) . '</p>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo "<p class='text-center text-xl font-semibold mt-10'>No articles found.</p>";
            }

            $conn->close();
            ?>
        </div>

        <!-- Full Article View (Hidden by Default) -->
        <div id="full-article-view" class="hidden fixed inset-0 bg-black bg-opacity-75 p-8 overflow-y-auto">
            <div class="bg-white rounded-lg p-6 max-w-3xl mx-auto relative">
                <button onclick="hideFullArticle()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                <div id="full-article-content"></div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#1b75bc] text-white py-6 bottom-0 w-full">
        <div class="container mx-auto text-center px-4">
            <p>&copy; <span id="year"></span> 1000 Hills Rugby News. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Set current year in footer
        document.getElementById("year").textContent = new Date().getFullYear();

        // Preload articles data
        const articles = <?php echo json_encode($articles, JSON_HEX_TAG); ?>;

        function showFullArticle(articleId) {
            if (!articles[articleId]) return;

            // Generate full article content
            let fullArticleContent = `
                <h1 class="text-2xl font-bold mb-4">${articles[articleId].title}</h1>
                <img class="w-full h-auto object-cover mb-6" src="${articles[articleId].main_image_path}" alt="${articles[articleId].title}" />
                <div class="space-y-4">
                    ${articles[articleId].details.map(detail => `
                        ${detail.subtitle ? `<h3 class="text-xl font-semibold">${detail.subtitle}</h3>` : ''}
                        ${detail.content ? `<p class="text-gray-700">${detail.content}</p>` : ''}
                        ${detail.image_path ? `<img class="w-full h-auto object-cover mt-4" src="${detail.image_path}" alt="${detail.subtitle}" />` : ''}
                    `).join('')}
                </div>
            `;

            // Display the full article
            document.getElementById('full-article-content').innerHTML = fullArticleContent;
            document.getElementById('full-article-view').classList.remove('hidden');
        }

        function hideFullArticle() {
            document.getElementById('full-article-view').classList.add('hidden');
        }
    </script>
</body>
</html>
