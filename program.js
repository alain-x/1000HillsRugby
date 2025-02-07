document.addEventListener("DOMContentLoaded", function () {
  // Read More Button Toggle
  function toggleText(contentId, btnId) {
    var content = document.getElementById(contentId);
    var btn = document.getElementById(btnId);

    content.classList.toggle("hidden");
    btn.textContent = content.classList.contains("hidden")
      ? "Read More"
      : "Read Less";
  }

  document.getElementById("readMoreBtn").addEventListener("click", function () {
    toggleText("moreText", "readMoreBtn");
  });

  document
    .getElementById("readMoreBtn4")
    .addEventListener("click", function () {
      toggleText("moreText4", "readMoreBtn4");
    });

  document
    .getElementById("readMoreBtn1")
    .addEventListener("click", function () {
      toggleText("moreText1", "readMoreBtn1");
    });

  document
    .getElementById("readMoreBtn2")
    .addEventListener("click", function () {
      toggleText("moreText2", "readMoreBtn2");
    });

  document
    .getElementById("readMoreBtn3")
    .addEventListener("click", function () {
      toggleText("moreText3", "readMoreBtn3");
    });

  // Toggle Menu Visibility
  const menuToggle = document.getElementById("menu-toggle");
  if (menuToggle) {
    menuToggle.addEventListener("change", function () {
      const menu = document.getElementById("menu");
      const openIcon = document.getElementById("menu-open-icon");
      const closeIcon = document.getElementById("menu-close-icon");

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
  }

  // Lazy Loading Images
  const lazyImages = document.querySelectorAll(".lazy");
  lazyImages.forEach((img) => {
    const imgSrc = img.getAttribute("data-src");
    img.setAttribute("src", imgSrc);
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

  // Slideshow Background Image
  const slideshowImages = [
    "./images/bg000.jpg",
    "./images/bg001.jpg",
    "./images/bg002.jpg",
    "./images/bg003.jpg",
  ];
  let currentSlideshowIndex = 0;
  let slideshowInterval;

  function changeBackgroundImage() {
    const container = document.querySelector(".slideshow-container");
    container.style.backgroundImage = `url(${slideshowImages[currentSlideshowIndex]})`;
    updateCycleButtons();
  }

  function createCycleButtons() {
    const cycleButtonsContainer = document.querySelector(".cycle-buttons");
    cycleButtonsContainer.innerHTML = "";
    slideshowImages.forEach((_, index) => {
      const button = document.createElement("button");
      button.className =
        "cycle-button w-3 h-3 rounded-full bg-white opacity-50";
      button.addEventListener("click", () => {
        currentSlideshowIndex = index;
        changeBackgroundImage();
        resetSlideshowInterval();
      });
      cycleButtonsContainer.appendChild(button);
    });
  }

  function updateCycleButtons() {
    const buttons = document.querySelectorAll(".cycle-button");
    buttons.forEach((button, index) => {
      button.style.opacity = index === currentSlideshowIndex ? "1" : "0.5";
    });
  }

  function startSlideshow() {
    changeBackgroundImage();
    slideshowInterval = setInterval(() => {
      currentSlideshowIndex =
        (currentSlideshowIndex + 1) % slideshowImages.length;
      changeBackgroundImage();
    }, 2000);
  }

  function resetSlideshowInterval() {
    clearInterval(slideshowInterval);
    startSlideshow();
  }

  createCycleButtons();
  startSlideshow();

  // Dropdown Toggle
  function toggleDropdown(menuId) {
    const dropdown = document.getElementById(menuId);
    dropdown.classList.toggle("hidden");
  }
});
