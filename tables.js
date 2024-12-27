// Sample data for the league table
const leagueData = [
  {
    position: 1,
    iconType: "no",
    teamLogo: "team1.png",
    teamName: "Sabyinyo TRFC",
    matches: 6,
    wins: 4,
    draws: 1,
    losses: 1,
    pointsFor: 54,
    pointsAgainst: 10,
    pointDifference: "+44",
    points: 45,
    teamType: "men",
    competition: "7s-festival",
    season: "2024",
  },
  {
    position: 2,
    iconType: "up",
    teamLogo: "team1.png",
    teamName: "Gahinga TRFC",
    matches: 6,
    wins: 10,
    draws: 1,
    losses: 1,
    pointsFor: 28,
    pointsAgainst: 11,
    pointDifference: "+17",
    points: 43,
    teamType: "men",
    competition: "7s-festival",
    season: "2024",
  },
  {
    position: 3,
    iconType: "down",
    teamLogo: "team2.png",
    teamName: "Muhabura TRFC",
    matches: 6,
    wins: 8,
    draws: 2,
    losses: 2,
    pointsFor: 24,
    pointsAgainst: 12,
    pointDifference: "+12",
    points: 40,
    teamType: "men",
    competition: "7s-festival",
    season: "2024",
  },
  {
    position: 4,
    iconType: "no",
    teamLogo: "team3.png",
    teamName: " Nyiragongo TRFC  ",
    matches: 6,
    wins: 6,
    draws: 3,
    losses: 3,
    pointsFor: 22,
    pointsAgainst: 18,
    pointDifference: "+4",
    points: 35,
    teamType: "men",
    competition: "7s-festival",
    season: "2024",
  },
];

// Function to populate season options dynamically
function populateSeasonOptions() {
  const teamFilter = document.getElementById("team-filter").value;
  const seasonFilter = document.getElementById("season-filter");

  // Clear existing options
  seasonFilter.innerHTML = "";

  // Populate options based on the team type
  if (teamFilter === "men") {
    ["2024", "2023", "2022"].forEach((year) => {
      const option = document.createElement("option");
      option.value = year;
      option.textContent = year;
      seasonFilter.appendChild(option);
    });
  } else if (teamFilter === "women") {
    ["2025", "2024"].forEach((year) => {
      const option = document.createElement("option");
      option.value = year;
      option.textContent = year;
      seasonFilter.appendChild(option);
    });
  }
}

// Function to render the table based on filtered data
function renderTable(data) {
  const tableBody = document.querySelector("table tbody");
  tableBody.innerHTML = ""; // Clear current table rows

  data.forEach((row) => {
    addRow(
      row.position,
      row.iconType,
      row.teamLogo,
      row.teamName,
      row.matches,
      row.wins,
      row.draws,
      row.losses,
      row.pointsFor,
      row.pointsAgainst,
      row.pointDifference,
      row.points
    );
  });
}

// Function to add a new row to the table
function addRow(
  position,
  iconType,
  teamLogo,
  teamName,
  matches,
  wins,
  draws,
  losses,
  pointsFor,
  pointsAgainst,
  pointDifference,
  points
) {
  const tableBody = document.querySelector("table tbody");

  const row = document.createElement("tr");
  const iconHTML =
    iconType === "up"
      ? '<span class="icon-up">⬆</span>'
      : iconType === "down"
      ? '<span class="icon-down">⬇</span>'
      : "";

  row.innerHTML = `
          <td>${iconHTML} ${position}</td>
          <td class="team-name">
            <img src="${teamLogo}" alt="${teamName} Logo" class="team-logo">
            ${teamName}
          </td>
          <td>${matches}</td>
          <td>${wins}</td>
          <td>${draws}</td>
          <td>${losses}</td>
          <td>${pointsFor}</td>
          <td>${pointsAgainst}</td>
          <td>${pointDifference}</td>
          <td>${points}</td>
        `;

  tableBody.appendChild(row);
}

// Event listeners for filters
document.querySelectorAll(".filters select").forEach((filter) => {
  filter.addEventListener("change", () => {
    if (filter.id === "team-filter") {
      populateSeasonOptions();
    }
    applyFilters();
  });
});

// Function to filter data based on selected filters
function applyFilters() {
  const teamFilter = document.getElementById("team-filter").value;
  const competitionFilter = document.getElementById(
    "competitions-filter"
  ).value;
  const seasonFilter = document.getElementById("season-filter").value;

  const filteredData = leagueData.filter(
    (team) =>
      team.teamType === teamFilter &&
      team.competition === competitionFilter &&
      team.season === seasonFilter
  );

  renderTable(filteredData);
}

// Add rows to the table on page load
document.addEventListener("DOMContentLoaded", () => {
  populateSeasonOptions();
  applyFilters(); // Default filter is applied for Men, 15s League, 2025
});

// Toggle Dropdown
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
