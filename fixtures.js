const matches = [
  {
    date: "SUN MAR 23 - 02:00 PM",
    stadium: "",
    homeTeam: { name: "GITISI TSS", logo: "" },
    awayTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SUN MAR 30 - 03:00 PM",
    stadium: "",
    homeTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    awayTeam: {
      name: "UR Grizzlies  ",
      logo: "",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Home",
    season: "2025",
  },
  {
    date: "SAT APL 26 - 02:00 PM",
    stadium: "",
    homeTeam: { name: "Puma RFC ", logo: "" },
    awayTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SAT MAY 03 - 01:00 PM",
    stadium: "",
    homeTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    awayTeam: {
      name: "Resilience RFC",
      logo: "",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Home",
    season: "2025",
  },
  {
    date: "SUN JUN 29 - 02:00 PM",
    stadium: "",
    homeTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    awayTeam: {
      name: "Gitisi TSS",
      logo: "",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Home",
    season: "2025",
  },
  {
    date: "SAT JUL 12 - 03:00 PM",
    stadium: "",
    homeTeam: { name: "UR Grizzlies", logo: "" },
    awayTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SAT SEP 06 - 01:00 PM",
    stadium: "",
    awayTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    homeTeam: {
      name: "Resilience RFC",
      logo: "",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SAT AUG 30 - 02:00 PM",
    stadium: "",
    awayTeam: { name: "Puma RFC ", logo: "" },
    homeTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Home",
    season: "2025",
  },
  // women's

  {
    date: "SAT MAY 17 - 01:00 PM",
    stadium: "",
    awayTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    homeTeam: {
      name: "Resilience RFC",
      logo: "",
    },
    score: "VS",
    goalScorers: [],
    gender: "WOMEN",
    competition: "league",
    location: "Away",
    season: "2025",
  },
  {
    date: "SAT MAY 31 - 02:00 PM",
    stadium: "",
    awayTeam: {
      name: "Ruhango Zebras Women RFC",
      logo: "",
    },
    homeTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    score: "VS",
    goalScorers: [],
    gender: "WOMEN",
    competition: "league",
    location: "Home",
    season: "2025",
  },
  {
    date: "SAT AUG 09 - 01:00 PM",
    stadium: "",
    awayTeam: {
      name: "Resilience RFC",
      logo: "",
    },
    homeTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    score: "VS",
    goalScorers: [],
    gender: "WOMEN",
    competition: "league",
    location: "Home",
    season: "2025",
  },
  {
    date: "SAT AUG 23 - 01:00 PM",
    stadium: "",
    awayTeam: {
      name: "1000 Hills Rugby",
      logo: "./logos_/logoT.jpg",
    },
    homeTeam: {
      name: "Ruhango Zebras Women RFC",
      logo: "",
    },
    score: "VS",
    goalScorers: [],
    gender: "WOMEN",
    competition: "league",
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
  matchResults.innerHTML = ""; // Clear previous results

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
                  : `<div class="gray-icon">${match.homeTeam.name.charAt(
                      0
                    )}</div>`
              }
              <span>${match.homeTeam.name}</span>
            </div>
            <span class="score">${match.score}</span>
            <div class="team">
              ${
                match.awayTeam.logo
                  ? `<img src="${match.awayTeam.logo}" alt="${match.awayTeam.name}" />`
                  : `<div class="gray-icon">${match.awayTeam.name.charAt(
                      0
                    )}</div>`
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
