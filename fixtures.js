const matches = [
  {
    date: "WED DEC 4 - 20:15",
    stadium: "Red Cross Kacyiru",
    homeTeam: { name: "1HR", logo: "./logos_/logoT.jpg" },
    awayTeam: {
      name: "A. Kagugu",
      logo: "./logos_/alpa_logo.jpeg",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Home",
    season: "2024-2025",
  },
  {
    date: "THU DEC 5 - 22:00",
    stadium: "Camp Kigali STADIUM",
    homeTeam: {
      name: "Muhanga RFC",
      logo: "./logos_/muhanga.jpg",
    },
    awayTeam: {
      name: "1HR ",
      logo: "./logos_/logoT.jpg",
    },
    score: "VS",
    goalScorers: [],
    gender: "MEN",
    competition: "league",
    location: "Away",
    season: "2024-2025",
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
                <img src="${match.homeTeam.logo}" alt="${
      match.homeTeam.name
    }" />
                <span>${match.homeTeam.name}</span>
              </div>
              <span class="score">${match.score}</span>
              <div class="team">
                <img src="${match.awayTeam.logo}" alt="${
      match.awayTeam.name
    }" />
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
