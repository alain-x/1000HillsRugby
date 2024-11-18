// Toggle Dropdown
function toggleDropdown(menuId) {
  const dropdown = document.getElementById(menuId);
  dropdown.classList.toggle("hidden");
}

// Array of background images for the first section
const backgroundImages = [
  "./images/bg001.jpg",
  "./images/allmember1.jpg",
  "./images/alainjean.jpeg",
  "./images/study2.jpg",
];

let bgIndex = 0;

function changeBackground() {
  const slideshowContainer = document.querySelector(".slideshow-container");
  slideshowContainer.style.backgroundImage = `url(${backgroundImages[bgIndex]})`;
  bgIndex = (bgIndex + 1) % backgroundImages.length;
}

setInterval(changeBackground, 2000);
changeBackground();

const images = [
  "./images/1.png",
  "./images/2.png",
  "./images/3.png",
  "./images/4.png",
  "./images/5.png",
  "./images/6.png",
  "./images/7.png",
  "./images/8.png",
  "./images/9.png",
];

let currentIndex = 0;

function updateImage() {
  const sliderImage = document.getElementById("slider-image");
  sliderImage.src = images[currentIndex];
  document.getElementById("backBtn").disabled = currentIndex === 0;
  document.getElementById("nextBtn").disabled =
    currentIndex === images.length - 1;
}

function nextImage() {
  if (currentIndex < images.length - 1) {
    currentIndex++;
    updateImage();
  }
}

function prevImage() {
  if (currentIndex > 0) {
    currentIndex--;
    updateImage();
  }
}

updateImage();

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
