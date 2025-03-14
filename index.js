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

// Lazy Loading Images
document.addEventListener("DOMContentLoaded", function () {
  const lazyImages = document.querySelectorAll(".lazy");
  lazyImages.forEach((img) => {
    const imgSrc = img.getAttribute("data-src");
    img.setAttribute("src", imgSrc);
  });
});

// Countdown Timer
function startCountdown(eventDate) {
  const countdownFunction = setInterval(function () {
    const now = new Date().getTime();
    const distance = eventDate - now;

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor(
      (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
    );
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    document.getElementById("days").innerHTML = days;
    document.getElementById("hours").innerHTML = hours;
    document.getElementById("minutes").innerHTML = minutes;
    document.getElementById("seconds").innerHTML = seconds;

    if (distance < 0) {
      clearInterval(countdownFunction);
      document.getElementById("countdown-timer").innerHTML =
        "Event has started!";
    }
  }, 1000);
}
startCountdown(new Date("Mar 23, 2025 02:00:00").getTime());
//********************* *
//***************** *

// Slideshow Background Image
const slideshowImages = [
  "./uploads/1740072895_fans.JPG",
  "./images/bg001.jpg",
  "./images/2OP.png",
  "./images/bg003.jpg",
];
let currentSlideshowIndex = 0; // Keep track of the current image index
let slideshowInterval; // Store the interval reference

// Function to change the background image
function changeBackgroundImage() {
  const container = document.querySelector(".slideshow-container");
  container.style.backgroundImage = `url(${slideshowImages[currentSlideshowIndex]})`;
  updateCycleButtons();
}

// Function to create cycle buttons dynamically
function createCycleButtons() {
  const cycleButtonsContainer = document.querySelector(".cycle-buttons");
  cycleButtonsContainer.innerHTML = ""; // Clear any existing buttons
  slideshowImages.forEach((_, index) => {
    const button = document.createElement("button");
    button.className = "cycle-button w-3 h-3 rounded-full bg-white opacity-50";
    button.addEventListener("click", () => {
      currentSlideshowIndex = index; // Update to the clicked image's index
      changeBackgroundImage(); // Update the background
      resetSlideshowInterval(); // Reset interval for automatic slideshow
    });
    cycleButtonsContainer.appendChild(button);
  });
}

// Function to update the active cycle button
function updateCycleButtons() {
  const buttons = document.querySelectorAll(".cycle-button");
  buttons.forEach((button, index) => {
    button.style.opacity = index === currentSlideshowIndex ? "1" : "0.5";
  });
}

// Function to start the slideshow
function startSlideshow() {
  changeBackgroundImage(); // Show the initial image
  slideshowInterval = setInterval(() => {
    currentSlideshowIndex =
      (currentSlideshowIndex + 1) % slideshowImages.length; // Cycle through images
    changeBackgroundImage();
  }, 2000); // Change every 2 seconds
}

// Function to reset the slideshow interval when user interacts
function resetSlideshowInterval() {
  clearInterval(slideshowInterval); // Clear the existing interval
  startSlideshow(); // Restart the interval
}

// Initialize the slideshow when the page loads
document.addEventListener("DOMContentLoaded", function () {
  createCycleButtons(); // Create navigation buttons
  startSlideshow(); // Start automatic slideshow
});

// Toggle Dropdown
function toggleDropdown(menuId) {
  const dropdown = document.getElementById(menuId);
  dropdown.classList.toggle("hidden");
}

// Match Data******************************************************************************************
const matches = [
  {
    date: { day: "15", month: "Feb" },
    team1: {
      name: "1000 Hills Rugby RFC",
      logo: "./logos_/logoT.jpg",
      score: "14",
    },
    team2: {
      name: "Lion De Fer RFC",
      logo: "./images/lions.jpg",
      score: "21",
    },
    tournamentInfo: "Final - Jade Water7s 2025",
  },
  {
    date: { day: "15", month: "Feb" },
    team1: {
      name: "1000 Hills Rugby RFC",
      logo: "./logos_/logoT.jpg",
      score: "21",
    },
    team2: {
      name: "GITISI A RFC",
      logo: "", // Empty logo to trigger fallback
      score: "19",
    },
    tournamentInfo: "Semi Final - Jade Water7s 2025",
  },
];

// Function to create a match card
function createMatchCard(match) {
  return `
          <div class="flex lg:flex-row flex-col items-center bg-white lg:w-[48%] w-full shadow-lg scale-105 hover:scale-110 transition-transform duration-300">
            <!-- Date Section -->
            <div class="flex flex-col justify-center items-center text-white lg:w-[15%] w-full h-full" style="background-color: black;">
              <p class="text-3xl font-bold">${match.date.day}</p>
              <p class="text-lg font-light">${match.date.month}</p>
            </div>
            
            <!-- Match Details -->
            <div class="flex-1 flex flex-col justify-between w-full items-center px-6 py-4 text-[#02120b]">
              <div class="flex w-full justify-between items-center">
                <!-- Team 1 -->
                <div class="flex items-center">
                  <div class="lg:w-14 w-10 mr-4 flex items-center justify-center bg-gray-300 rounded-full">
                    ${
                      match.team1.logo
                        ? `<img class="lg:w-14 w-10" src="${match.team1.logo}" alt="${match.team1.name} Logo" />`
                        : `<span class="text-2xl font-bold text-gray-600">${match.team1.name.charAt(
                            0
                          )}</span>`
                    }
                  </div>
                  <div>
                    <p class="lg:text-4xl md:text-xl text-lg font-bold">${
                      match.team1.score
                    }</p>
                    <p class="lg:text-sm md:text-lg text-sm">${
                      match.team1.name
                    }</p>
                  </div>
                </div>
                
                <!-- VS Text -->
                <p class="lg:text-3xl text-xl font-semibold text-[#dcbb26]">VS</p>
                
                <!-- Team 2 -->
                <div class="flex items-center">
                  <div class="lg:w-14 w-10 ml-4 flex items-center justify-center bg-gray-300 rounded-full">
                    ${
                      match.team2.logo
                        ? `<img class="lg:w-14 w-10" src="${match.team2.logo}" alt="${match.team2.name} Logo" />`
                        : `<span class="text-2xl font-bold text-gray-600">${match.team2.name.charAt(
                            0
                          )}</span>`
                    }
                  </div>
                  <div>
                    <p class="lg:text-4xl md:text-xl text-lg font-bold">${
                      match.team2.score
                    }</p>
                    <p class="lg:text-sm md:text-lg text-sm">${
                      match.team2.name
                    }</p>
                  </div>
                </div>
              </div>
              
              <!-- Tournament Info -->
              <p class="text-center text-gray-600 py-2 text-sm lg:text-base">${
                match.tournamentInfo
              }</p>
            </div>
          </div>
        `;
}

// Function to create the <section> and insert match cards
function createMatchSection() {
  // Create the <section> element
  const section = document.createElement("section");
  section.className = "w-[95%] mx-auto text-center z-2 mt-[auto]";

  // Create the container for match cards
  const matchesContainer = document.createElement("div");
  matchesContainer.className =
    "flex flex-col lg:flex-row justify-between -mt-20 mb-24 gap-8 p-4";

  // Add match cards to the container
  matches.forEach((match) => {
    const matchCard = createMatchCard(match);
    matchesContainer.innerHTML += matchCard;
  });

  // Append the container to the <section>
  section.appendChild(matchesContainer);

  // Append the <section> to the container with class "match-container"
  const container = document.querySelector(".match-container");
  if (container) {
    container.appendChild(section);
  } else {
    console.error("Container with class 'match-container' not found!");
  }
}

// Create the <section> on page load
document.addEventListener("DOMContentLoaded", createMatchSection);
