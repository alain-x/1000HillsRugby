const players = [
  {
    name: "Harerimana Robert",
    img: "",
    performance: 80,
    triesScored: 4,
    competition: "League",
    year: "2025",
  },
  {
    name: "Kuneshanukwayo Shalom",
    img: "./plofilesM/KUNESHANUKWAYO Shalom.JPG",
    performance: 65,
    triesScored: 2,
    competition: "League",
    year: "2025",
  },
  {
    name: "Mbarushimana Enock",
    img: "./plofilesM/MBARUSHIMANA Enock.JPG",
    performance: 65,
    triesScored: 2,
    competition: "League",
    year: "2025",
  },
  {
    name: "Prince Shema",
    img: "./plofilesM/SHEMA Prince.JPG",
    performance: 65,
    triesScored: 2,
    competition: "League",
    year: "2025",
  },
  {
    name: "Rukundo Jackson",
    img: "",
    performance: 65,
    triesScored: 2,
    competition: "League",
    year: "2025",
  },
  {
    name: "RUKUNDO Roger",
    img: "./plofilesM/RUKUNDO Roger.JPG",
    performance: 65,
    triesScored: 2,
    competition: "League",
    year: "2025",
  },
  {
    name: "Muhisha Robert",
    img: "",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
  },
  {
    name: "Izabayo Jermiy",
    img: "./plofilesM/IZABAYO JEREMY.png",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
  },
  {
    name: "Mwenedata Fabrice",
    img: "player4.png",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
  },
  {
    name: "Dusenge M Diego",
    img: "./plofilesM/MUCUNGURA D Diego.JPG",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
  },
  {
    name: "Singizwa J Sauveur",
    img: "./plofilesM/SINGIZWA JEAN SAUVEUR.png",
    performance: 65,
    triesScored: 2,
    competition: "League",
    year: "2025",
  },
];

const news = [
  {
    title: "Preview: Upcoming Match",
    img: "match.png",
    competition: "League",
    year: "2024-2025",
  },
  {
    title: "Player of the Month: Player 1",
    img: "player1.png",
    competition: "Championship",
    year: "2023-2024",
  },
  {
    title: "Team Wins Championship",
    img: "trophy.png",
    competition: "League",
    year: "2024-2025",
  },
];
function updateRankingTable() {
  const year = document.getElementById("year-filter").value;
  const competition = document.getElementById("competition-filter").value;

  const table = document.getElementById("ranking-table");
  const rankingTitle = document.getElementById("ranking-title");

  // Clear the table and set the title
  table.innerHTML = `
    <tr>
      <th>Player</th>
      <th>Tries Scored</th>
      <th>Performance</th>
    </tr>
  `;
  rankingTitle.innerText = `Player Rankings | Rugby Team`;

  // Filter players by year and competition
  const filteredPlayers = players.filter(
    (player) => player.year === year && player.competition === competition
  );

  // Sort players by tries scored (descending) for display
  filteredPlayers.sort((a, b) => b.triesScored - a.triesScored);

  // Add each player's data to the table
  filteredPlayers.forEach((player) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td class="player">
        <img src="${player.img || "default.png"}" alt="${player.name}" />
        ${player.name}
      </td>
      <td>${player.triesScored} Try</td>
      <td>
        <div class="bar-container">
          <span class="bar" style="width: ${player.performance}%"></span>
          <span class="percentage">${player.performance}%</span>
        </div>
      </td>
    `;
    table.appendChild(row);
  });
}

function updateNews() {
  const year = document.getElementById("year-filter").value;
  const competition = document.getElementById("competition-filter").value;

  const newsContainer = document.getElementById("news-container");
  newsContainer.innerHTML = "";

  // Update the title dynamically based on the selected year and competition
  const newsTitle = document.querySelector(".news h3");
  newsTitle.innerText = `${competition} ${year} News`;

  const filteredNews = news.filter(
    (newsItem) => newsItem.year === year && newsItem.competition === competition
  );

  filteredNews.forEach((newsItem) => {
    const newsElement = document.createElement("div");
    newsElement.classList.add("news-item");
    newsElement.innerHTML = `
      <img src="${newsItem.img}" alt="${newsItem.title}" />
      <h4>${newsItem.title}</h4>
    `;
    newsContainer.appendChild(newsElement);
  });
}

document.getElementById("year-filter").addEventListener("change", () => {
  updateRankingTable();
  updateNews();
});

document.getElementById("competition-filter").addEventListener("change", () => {
  updateRankingTable();
  updateNews();
});

document.getElementById("ranking-filter").addEventListener("change", () => {
  updateRankingTable();
});
updateRankingTable();
updateNews();

const menuToggle = document.getElementById("menu-toggle");
const hamburgerIcon = document.getElementById("hamburger-icon");
const closeIcon = document.getElementById("close-icon");
const mobileMenu = document.getElementById("mobile-menu");

menuToggle.addEventListener("click", () => {
  mobileMenu.classList.toggle("hidden");
  hamburgerIcon.classList.toggle("hidden");
  closeIcon.classList.toggle("hidden");
});
