// Toggle Menu Visibility
const menuToggle = document.getElementById("menu-toggle");
const menu = document.getElementById("menu");
const openIcon = document.getElementById("menu-open-icon");
const closeIcon = document.getElementById("menu-close-icon");

menuToggle.addEventListener("change", function () {
  if (this.checked) {
    menu.classList.remove("hidden");
    openIcon.classList.add("hidden");
    closeIcon.classList.remove("hidden");
  } else {
    menu.classList.add("hidden");
    openIcon.classList.remove("hidden");
    closeIcon.classList.add("hidden");
  }
});
// Toggle Dropdown
function toggleDropdown(menuId) {
  const dropdown = document.getElementById(menuId);
  dropdown.classList.toggle("hidden");
}

// Chart Data
const ctx = document.getElementById("supportChart").getContext("2d");
const supportChart = new Chart(ctx, {
  type: "bar",
  data: {
    labels: [
      "Total Beneficiaries",
      "Supported by SESLA",
      "Girls (SESLA)",
      "Boys (SESLA)",
    ],
    datasets: [
      {
        label: "",
        data: [65, 40, 35, 30], // Example data based on your description
        backgroundColor: [
          "rgba(5, 42, 249, 0.6)", // Teal for total beneficiaries
          "rgba(9, 161, 6, 0.6)", // Blue for supported by BG-3
          "rgba(247, 5, 138, 0.6)", // Red for girls
          "rgba(252, 69, 3, 0.6)", // Yellow for boys
        ],
        borderColor: [
          "rgba(5, 42, 249, 0.6)",
          "rgba(9, 161, 6, 0.6)",
          "rgba(247, 5, 138, 0.6)",
          "rgba(252, 69, 3, 0.6)",
        ],
        borderWidth: 1,
      },
    ],
  },
  options: {
    scales: {
      y: {
        beginAtZero: true,
        title: {
          display: true,
          text: "N of Members",
        },
      },
      x: {
        title: {
          display: true,
          text: "Categories",
        },
      },
    },
    plugins: {
      title: {
        display: true,
        text: "1000 Hills Rugby Education Support Breakdown",
      },
    },
  },
});
