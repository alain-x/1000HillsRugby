<?php
header("Content-Type: application/json");

$servername = "localhost:3306";
$username = "hillsrug_gasore";
$password = "M00dle??";
$dbname = "hillsrug_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

if (isset($_GET['id'])) {
    $articleId = intval($_GET['id']);
    $sql = "SELECT a.id, a.title, a.category, a.date_published, a.main_image_path, ad.subtitle, ad.content, ad.image_path
            FROM articles a
            LEFT JOIN article_details ad ON a.id = ad.article_id
            WHERE a.id = $articleId
            ORDER BY a.date_published DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $article = [];
        while ($row = $result->fetch_assoc()) {
            if (empty($article)) {
                $article = [
                    "id" => $row["id"],
                    "title" => $row["title"],
                    "category" => $row["category"],
                    "date_published" => $row["date_published"],
                    "main_image_path" => $row["main_image_path"],
                    "details" => []
                ];
            }
            if (!empty($row["subtitle"]) || !empty($row["content"]) || !empty($row["image_path"])) {
                $article["details"][] = [
                    "subtitle" => $row["subtitle"],
                    "content" => $row["content"],
                    "image_path" => $row["image_path"]
                ];
            }
        }
        echo json_encode($article);
    } else {
        echo json_encode(["error" => "Article not found"]);
    }
} else {
    $sql = "SELECT id, title, date_published, main_image_path FROM articles ORDER BY date_published DESC LIMIT 5";
    $result = $conn->query($sql);
    $news = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $news[] = [
                "id" => $row["id"],
                "title" => $row["title"],
                "date_published" => $row["date_published"],
                "main_image_path" => $row["main_image_path"]
            ];
        }
    }
    echo json_encode($news);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1000 Hills Rugby | News</title>
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="news-cards"></div>
        <div id="full-article-view" class="hidden fixed inset-0 bg-black bg-opacity-75 p-8 overflow-y-auto">
            <div class="bg-white rounded-lg p-6 max-w-3xl mx-auto relative">
                <button onclick="hideFullArticle()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                <div id="full-article-content"></div>
            </div>
        </div>
    </main>

    <footer class="bg-[#1b75bc] text-white py-6 bottom-0 w-full">
        <div class="container mx-auto text-center px-4">
            <p>&copy; <span id="year"></span> 1000 Hills Rugby News. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
        
        function fetchNews() {
            fetch('news.php')
                .then(response => response.json())
                .then(news => {
                    const newsCards = document.getElementById("news-cards");
                    newsCards.innerHTML = news.map(article => `
                        <div class="article-card bg-white rounded-lg shadow-sm overflow-hidden cursor-pointer" onclick="fetchArticle(${article.id})">
                            <img class="w-full h-49 object-cover" src="${article.main_image_path}" alt="${article.title}" />
                            <div class="p-4">
                                <h2 class="text-xl font-bold mb-2">${article.title}</h2>
                                <p class="text-gray-500 text-sm">${new Date(article.date_published).toDateString()}</p>
                            </div>
                        </div>
                    `).join('');
                });
        }

        function fetchArticle(articleId) {
            fetch(`news.php?id=${articleId}`)
                .then(response => response.json())
                .then(article => {
                    const content = `
                        <h1 class="text-2xl font-bold mb-4">${article.title}</h1>
                        <img class="w-full h-auto object-cover mb-6" src="${article.main_image_path}" alt="${article.title}" />
                        <div class="space-y-4">
                            ${article.details.map(detail => `
                                ${detail.content ? `<p class="text-gray-700">${detail.content}</p>` : ''}
                                ${detail.subtitle ? `<h3 class="text-xl font-semibold">${detail.subtitle}</h3>` : ''}
                                ${detail.image_path ? `<img class="w-full h-auto object-cover mt-4" src="${detail.image_path}" alt="${detail.subtitle}" />` : ''}
                            `).join('')}
                        </div>
                    `;
                    document.getElementById('full-article-content').innerHTML = content;
                    document.getElementById('full-article-view').classList.remove('hidden');
                });
        }
        
        function hideFullArticle() {
            document.getElementById('full-article-view').classList.add('hidden');
        }
        
        fetchNews();
    </script>
</body>
</html>