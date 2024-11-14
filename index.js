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
document.addEventListener("DOMContentLoaded", function () {
  const lazyImages = document.querySelectorAll(".lazy");
  lazyImages.forEach((img) => {
    const imgSrc = img.getAttribute("data-src");
    img.setAttribute("src", imgSrc);
  });
});

// ...................events .........................
// Set the date for the next event
var eventDate = new Date("Nov 11, 2024 19:45:00").getTime();

// Update the countdown every second
var countdownFunction = setInterval(function () {
  var now = new Date().getTime();
  var distance = eventDate - now;

  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  var seconds = Math.floor((distance % (1000 * 60)) / 1000);

  document.getElementById("days").innerHTML = days;
  document.getElementById("hours").innerHTML = hours;
  document.getElementById("minutes").innerHTML = minutes;
  document.getElementById("seconds").innerHTML = seconds;

  // If the countdown is over, display some text
  if (distance < 0) {
    clearInterval(countdownFunction);
    document.getElementById("countdown-timer").innerHTML = "Event has started!";
  }
}, 1000);

// JavaScript for Slideshow Functionality
const images = [
  "./images/bg000.jpg",
  "./images/bg001.jpg",
  "./images/bg002.jpg",
  "./images/bg003.jpg",
];
let currentIndex = 0;

function changeBackgroundImage() {
  const container = document.querySelector(".slideshow-container");
  container.style.backgroundImage = `url(${images[currentIndex]})`;
  updateCycleButtons();
  currentIndex = (currentIndex + 1) % images.length;
}

function createCycleButtons() {
  const cycleButtonsContainer = document.querySelector(".cycle-buttons");
  cycleButtonsContainer.innerHTML = ""; // Clear any existing buttons
  images.forEach(() => {
    const button = document.createElement("button");
    button.className = "cycle-button w-3 h-3 rounded-full bg-white opacity-50";
    cycleButtonsContainer.appendChild(button);
  });
}

function updateCycleButtons() {
  const buttons = document.querySelectorAll(".cycle-button");
  buttons.forEach((button, index) => {
    button.style.opacity = index === currentIndex ? "1" : "0.5";
  });
}

// Start autoplay when page loads
window.onload = function () {
  createCycleButtons(); // Create cycle buttons based on images array
  changeBackgroundImage(); // Display the first image
  setInterval(changeBackgroundImage, 3000); // Change every 3 seconds
};

function toggleDropdown(id) {
  const dropdowns = document.querySelectorAll(".dropdown");

  // Close any open dropdown
  dropdowns.forEach((dropdown) => {
    if (dropdown.id !== id) {
      dropdown.classList.add("hidden");
    }
  });

  // Toggle the clicked dropdown
  const targetDropdown = document.getElementById(id);
  targetDropdown.classList.toggle("hidden");
}
