const players = [
  {
    name: "Muhisha Robert",
    img: "",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: " Harerimana Robert",
    img: "",
    performance: 80,
    triesScored: 4,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "RUKUNDO Roger",
    img: "",
    performance: 50,
    triesScored: 2,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Izabayo Jermiy",
    img: "",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Prince Shema ",
    img: "player4.png",
    performance: 50,
    triesScored: 2,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Rukundo Jackson",
    img: "player4.png",
    performance: 50,
    triesScored: 2,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Mwenedata Fabrice",
    img: "player4.png",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Kuneshanukwayo Shalom",
    img: "player4.png",
    performance: 50,
    triesScored: 2,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Dusenge M Diego",
    img: "player4.png",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Mbarushimana Ennock",
    img: "player4.png",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },
  {
    name: "Singizwa J Sauveur",
    img: "player4.png",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Tries",
  },

  {
    name: "Muhisha Robert",
    img: "",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Ranking",
  },
  {
    name: " Harererimana Robert",
    img: "",
    performance: 80,
    triesScored: 2,
    competition: "League",
    year: "2025",
    ranking: "Ranking",
  },
  {
    name: "RUKUNDO Roger",
    img: "",
    performance: 50,
    triesScored: 1,
    competition: "League",
    year: "2025",
    ranking: "Ranking",
  },
  ,
  {
    name: "Prince Shema ",
    img: "player4.png",
    performance: 50,
    triesScored: 2,
    competition: "League",
    year: "2025",
    ranking: "Ranking",
  },
  {
    name: "Rukundo Jackson  ",
    img: "player4.png",
    performance: 50,
    triesScored: 2,
    competition: "League",
    year: "2025",
    ranking: "Ranking",
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
  const rankingType = document.getElementById("ranking-filter").value;

  const table = document.getElementById("ranking-table");
  const rankingTitle = document.getElementById("ranking-title");
  const rankingHeader = document.getElementById("ranking-header");

  table.innerHTML = `<tr><th>Player</th><th id="ranking-header">Performance</th></tr>`;

  if (rankingType === "Tries") {
    rankingTitle.innerText = "Tries Ranking | Rugby Team";
    rankingHeader.innerText = "Tries Scored";
  } else {
    rankingTitle.innerText = "Player Ranking | Rugby Team";
    rankingHeader.innerText = "Performance";
  }

  const filteredPlayers = players.filter((player) => {
    return (
      player.year === year &&
      player.competition === competition &&
      player.ranking === rankingType
    );
  });

  filteredPlayers.forEach((player) => {
    const row = document.createElement("tr");
    if (rankingType === "Tries") {
      row.innerHTML = `
              <td class="player">
                <img src="${player.img}" alt="${player.name}" />
                ${player.name}
              </td>
              <td>${player.triesScored} Tries</td>
            `;
    } else {
      row.innerHTML = `
              <td class="player">
                <img src="${player.img}" alt="${player.name}" />
                ${player.name}
              </td>
              <td>
                <div class="bar-container">
                  <span class="bar" style="width: ${player.performance}%"></span>
                  <span class="percentage">${player.performance}%</span>
                </div>
              </td>
            `;
    }
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
  newsTitle.innerText = `${competition} ${year} News`; // Dynamically change the title

  const filteredNews = news.filter((newsItem) => {
    return newsItem.year === year && newsItem.competition === competition;
  });

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

// Toggle mobile menu visibility on hamburger click
const menuToggle = document.getElementById("menu-toggle");
const hamburgerIcon = document.getElementById("hamburger-icon");
const closeIcon = document.getElementById("close-icon");
const mobileMenu = document.getElementById("mobile-menu");

menuToggle.addEventListener("click", () => {
  // Toggle mobile menu visibility
  mobileMenu.classList.toggle("hidden");

  // Switch between hamburger and close icons
  hamburgerIcon.classList.toggle("hidden");
  closeIcon.classList.toggle("hidden");
});
