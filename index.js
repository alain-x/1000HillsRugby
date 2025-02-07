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
startCountdown(new Date("Nov 11, 2025 19:45:00").getTime());
//********************* *
//***************** *

// Slideshow Background Image
const slideshowImages = [
  "./images/bg000.jpg",
  "./images/bg001.jpg",
  "./images/bg002.jpg",
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

document.addEventListener("DOMContentLoaded", function () {
  // Toggle for "Rugby & Life Skills Program"
  const readMoreBtn3 = document.getElementById("readMoreBtn3");
  const moreText3 = document.getElementById("moreText3");

  if (readMoreBtn3) {
    readMoreBtn3.addEventListener("click", function () {
      if (moreText3.classList.contains("hidden")) {
        moreText3.classList.remove("hidden"); // Show more content
        readMoreBtn3.textContent = "Read Less"; // Change button text
      } else {
        moreText3.classList.add("hidden"); // Hide content again
        readMoreBtn3.textContent = "Read More"; // Reset button text
      }
    });
  }

  // Toggle for "Rising with 1000 Hills"
  const readMoreBtn = document.getElementById("readMoreBtn");
  const moreText = document.getElementById("moreText");

  if (readMoreBtn) {
    readMoreBtn.addEventListener("click", function () {
      if (moreText.classList.contains("hidden")) {
        moreText.classList.remove("hidden"); // Show more content
        readMoreBtn.textContent = "Read Less"; // Change button text
      } else {
        moreText.classList.add("hidden"); // Hide content again
        readMoreBtn.textContent = "Read More"; // Reset button text
      }
    });
  }

  // Toggle for "Program Structure and Pathways"
  const readMoreBtn0 = document.getElementById("readMoreBtn0");
  const moreText0 = document.getElementById("moreText0");
  const shortText0 = document.getElementById("shortText0");

  if (readMoreBtn0) {
    readMoreBtn0.addEventListener("click", function () {
      if (moreText0.classList.contains("hidden")) {
        moreText0.classList.remove("hidden"); // Show full content
        shortText0.classList.add("hidden"); // Hide short text
        readMoreBtn0.textContent = "Read Less"; // Change button text
      } else {
        moreText0.classList.add("hidden"); // Hide full content
        shortText0.classList.remove("hidden"); // Show short text
        readMoreBtn0.textContent = "Read More"; // Reset button text
      }
    });
  }

  const readMoreBtn1 = document.getElementById("readMoreBtn1");
  const moreText1 = document.getElementById("moreText1");

  if (readMoreBtn1) {
    readMoreBtn1.addEventListener("click", function () {
      if (moreText1.classList.contains("hidden")) {
        moreText1.classList.remove("hidden"); // Show more content
        readMoreBtn1.textContent = "Read Less"; // Change button text
      } else {
        moreText1.classList.add("hidden"); // Hide content again
        readMoreBtn1.textContent = "Read More"; // Reset button text
      }
    });
  }
});
