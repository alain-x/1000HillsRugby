// Latest news rendering logic extracted from index.html to satisfy CSP

async function fetchLatestNews() {
  try {
    const response = await fetch('get_latest_news.php');
    const news = await response.json();
    displayLatestNews(news);
  } catch (error) {
    console.error('Error fetching latest news:', error);
  }
}

function truncateText(text, maxLength = 50) {
  if (!text) return '';
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function displayLatestNews(news) {
  const newsContainer = document.querySelector('.news-container');
  if (!newsContainer || !Array.isArray(news) || news.length === 0) return;

  newsContainer.innerHTML = '';

  const largeCard = `
    <div class="w-full lg:w-[50%]">
      <div class="relative flex flex-col gap-4 p-2 lg:p-6 rounded-xl transition-all duration-300">
        <div class="relative group w-full">
          <a href="news-detail?id=${news[0].id}">
            <img
              class="rounded-xl h-[250px] md:h-[350px] lg:h-[400px] w-full object-cover transition-transform duration-500 ease-in-out transform group-hover:scale-110"
              src="${news[0].main_image_path}"
              alt="${news[0].title}"
              loading="lazy" decoding="async" width="1200" height="800"
            />
          </a>
          <div class="absolute bottom-4 left-4 bg-black/70 text-white p-2 md:p-4 rounded-lg">
            <span class="font-medium text-sm md:text-lg">Now</span>
            <h3 class="font-semibold text-xl md:text-2xl mt-2">
              ${truncateText(news[0].title, 50)}
            </h3>
          </div>
        </div>
        <div class="flex justify-between items-center bg-white p-4 mt-4 rounded-lg shadow-md">
          <div class="flex gap-2">
            <a href="#" class="text-gray-600 hover:text-[#dcbb26]"><i class="fab fa-facebook-square"></i></a>
            <a href="#" class="text-gray-600 hover:text-[#dcbb26]"><i class="fab fa-twitter-square"></i></a>
            <a href="#" class="text-gray-600 hover:text-[#dcbb26]"><i class="fab fa-linkedin"></i></a>
          </div>
          <a href="news" class="text-[#dcbb26] font-semibold hover:text-black transition-all duration-300">
            Read More <i class="fas fa-arrow-right ml-2"></i>
          </a>
        </div>
      </div>
    </div>
  `;

  let mediumCards = '<div class="w-full lg:w-[50%] grid grid-cols-1 md:grid-cols-2 gap-4">';
  for (let i = 1; i < Math.min(news.length, 5); i++) {
    mediumCards += `
      <a href="news-detail?id=${news[i].id}">
        <div class="bg-white p-4 w-full rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:transform hover:-translate-y-2 flex flex-col gap-2">
          <div class="relative">
            <img
              class="rounded-xl h-[220px] md:h-[230px] lg:h-[240px] w-full object-cover transition-transform duration-500 ease-in-out transform hover:scale-110"
              src="${news[i].main_image_path}"
              alt="${news[i].title}"
              loading="lazy" decoding="async" width="800" height="533"
            />
          </div>
          <div class="text-center">
            <span class="text-sm md:text-md text-gray-600">${new Date(news[i].date_published).toLocaleDateString()}</span>
            <h3 class="font-bold text-base md:text-lg mt-1">${truncateText(news[i].title, 40)}</h3>
          </div>
        </div>
      </a>
    `;
  }
  mediumCards += '</div>';

  newsContainer.innerHTML = `<div class="flex flex-col lg:flex-row gap-6">${largeCard}${mediumCards}</div>`;
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', fetchLatestNews);
} else {
  fetchLatestNews();
}
