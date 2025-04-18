const matches = [
  {
    date: "SAT FEB 15 - 05:00",
    stadium: "IPRC Kigali Ground",
    homeTeam: { name: "1HR", logo: "./logos_/logoT.jpg" },
    awayTeam: {
      name: "LIONS DE FER",
      logo: "./logos_/lions.jpg",
    },
    score: "14 - 21",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SAT FEB 15 - 04:30",
    stadium: "IPRC Kigali Ground",
    homeTeam: { name: "1HR", logo: "./logos_/logoT.jpg" },
    awayTeam: {
      name: "GITISI TSS A",
      logo: "",
    },
    score: "21 - 19",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SAT FEB 15 - 02:00",
    stadium: "IPRC Kigali Ground",
    homeTeam: { name: "1HR", logo: "./logos_/logoT.jpg" },
    awayTeam: {
      name: "PUMA RFC",
      logo: "",
    },
    score: "63 - 07",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SAT FEB 15 - 09:15",
    stadium: "IPRC Kigali Ground",
    homeTeam: { name: "1HR", logo: "./logos_/logoT.jpg" },
    awayTeam: {
      name: "GITISI TSS B",
      logo: "",
    },
    score: "40 - 00",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "WED SEP 07 - 02:15",
    stadium: "Don Bosco Gatenga Ground",
    homeTeam: { name: "1HR", logo: "./logos_/logoT.jpg" },
    awayTeam: {
      name: "A. Kagugu",
      logo: "./logos_/alpa_logo.jpeg",
    },
    score: "41 - 00",
    goalScorers: [],
    gender: "MEN",
    competition: "7league",
    location: "Home",
    season: "2025",
  },
  {
    date: "THU SEP 23 - 22:00",
    stadium: "Don Bosco Gatenga Ground",
    homeTeam: {
      name: "1HR",
      logo: "./logos_/logoT.jpg",
    },
    awayTeam: {
      name: "Lions De Fer",
      logo: "./logos_/lions.jpg",
    },
    score: "24 - 21",
    goalScorers: [],
    gender: "MEN",
    competition: "7league",
    location: "Away",
    season: "2025",
  },
];

// Filter matches based on the selected filters
function filterMatches() {
  const gender = document.getElementById("genderFilter").value;
  const competition = document.getElementById("competitionFilter").value;
  const location = document.getElementById("homeAwayFilter").value;
  const season = document.getElementById("seasonFilter").value;

  // Filter the matches array based on the selected filter values
  const filteredMatches = matches.filter((match) => {
    return (
      (gender === "ALL" || match.gender === gender) &&
      (competition === "ALL" || match.competition === competition) &&
      (location === "ALL" || match.location === location) &&
      match.season === season
    );
  });

  displayMatches(filteredMatches);
}

// Display the matches in the results section
function displayMatches(filteredMatches) {
  const matchResults = document.getElementById("matchResults");
  matchResults.innerHTML = `<h2>December 2024</h2>`; // Reset the heading

  filteredMatches.forEach((match) => {
    const matchDiv = document.createElement("div");
    matchDiv.classList.add("match");

    matchDiv.innerHTML = `
      <div class="match-info">
        <p>${match.date}</p>
        <p>${match.stadium}</p>
      </div>
      <div class="teams">
        <div class="team">
          ${
            match.homeTeam.logo
              ? `<img src="${match.homeTeam.logo}" alt="${match.homeTeam.name}" />`
              : `<div class="gray-icon">${match.homeTeam.name.charAt(0)}</div>`
          }
          <span>${match.homeTeam.name}</span>
        </div>
        <span class="score">${match.score}</span>
        <div class="team">
          ${
            match.awayTeam.logo
              ? `<img src="${match.awayTeam.logo}" alt="${match.awayTeam.name}" />`
              : `<div class="gray-icon">${match.awayTeam.name.charAt(0)}</div>`
          }
          <span>${match.awayTeam.name}</span>
        </div>
      </div>
      <div class="goal-scorers">
        ${match.goalScorers.map((goal) => `<p>${goal}</p>`).join("")}
      </div>
    `;

    matchResults.appendChild(matchDiv);
  });
}

// Attach filter change event listeners
document
  .getElementById("genderFilter")
  .addEventListener("change", filterMatches);
document
  .getElementById("competitionFilter")
  .addEventListener("change", filterMatches);
document
  .getElementById("homeAwayFilter")
  .addEventListener("change", filterMatches);
document
  .getElementById("seasonFilter")
  .addEventListener("change", filterMatches);

// Initial display of all matches
displayMatches(matches);

// Mobile menu functionality
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
