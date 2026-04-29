(function () {
  function calculateAge(dateString) {
    const dob = new Date(dateString);
    const today = new Date();

    if (Number.isNaN(dob.getTime())) {
      return null;
    }
    if (dob > today) {
      return null;
    }

    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
      age--;
    }
    return age >= 0 ? age : null;
  }

  function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.querySelector('.nav-links');
    if (!mobileMenuBtn || !navLinks) return;

    mobileMenuBtn.addEventListener('click', function () {
      navLinks.classList.toggle('active');
    });

    document.querySelectorAll('.nav-links a').forEach(function (link) {
      link.addEventListener('click', function () {
        navLinks.classList.remove('active');
      });
    });
  }

  function initImagePreview() {
    const playerImageInput = document.getElementById('player_image');
    const imagePreview = document.getElementById('imagePreview');
    const uploadBtn = document.getElementById('uploadBtn');
    if (!playerImageInput || !imagePreview) return;

    playerImageInput.addEventListener('change', function () {
      const file = playerImageInput.files && playerImageInput.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.addEventListener('load', function () {
        imagePreview.innerHTML = '';
        const img = document.createElement('img');
        img.src = String(reader.result || '');
        imagePreview.appendChild(img);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-image-btn';
        removeBtn.title = 'Remove Image';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.addEventListener('click', function () {
          playerImageInput.value = '';
          imagePreview.innerHTML = '<div class="image-preview-placeholder"><i class="fas fa-user"></i></div>';
        });
        imagePreview.appendChild(removeBtn);
      });

      reader.readAsDataURL(file);
    });

    if (uploadBtn) {
      uploadBtn.addEventListener('click', function () {
        playerImageInput.click();
      });
    }
  }

  function initRemoveExistingImage() {
    const removeImageBtn = document.getElementById('removeImageBtn');
    const removeImageFormReal = document.getElementById('removeImageFormReal');

    if (removeImageBtn && removeImageFormReal) {
      removeImageBtn.addEventListener('click', function () {
        const confirmed = confirm('Are you sure you want to remove this image from the player profile?');
        if (confirmed) {
          removeImageFormReal.submit();
        }
      });
    }
  }

  function initCategoryValidation() {
    const categorySelect = document.getElementById('category');
    const playerCategorySelect = document.getElementById('player_category');

    if (!categorySelect || !playerCategorySelect) return;

    categorySelect.addEventListener('change', function () {
      const val = categorySelect.value;
      if (val === 'Captain' || val === 'Vice-Captain') {
        const pc = playerCategorySelect.value;
        if (pc !== 'Backs' && pc !== 'Forwards') {
          alert('Captain and Vice-Captain must be selected from Backs or Forwards');
          categorySelect.value = '';
        }
      }
    });
  }

  function initAgeAutoCalc() {
    const dobInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age');
    if (!dobInput || !ageInput) return;

    function updateAgeFromDob() {
      const val = dobInput.value;
      if (!val) {
        ageInput.value = '';
        return;
      }
      const age = calculateAge(val);
      ageInput.value = age === null ? '' : String(age);
    }

    dobInput.addEventListener('change', updateAgeFromDob);
    dobInput.addEventListener('blur', updateAgeFromDob);
    updateAgeFromDob();
  }

  function initDeleteConfirm() {
    document.addEventListener('click', function (e) {
      const link = e.target && e.target.closest ? e.target.closest('a.delete-btn') : null;
      if (!link) return;
      const ok = confirm("Are you sure you want to delete this player profile? This cannot be undone.");
      if (!ok) {
        e.preventDefault();
      }
    });
  }

  function init() {
    initMobileMenu();
    initImagePreview();
    initRemoveExistingImage();
    initCategoryValidation();
    initAgeAutoCalc();
    initDeleteConfirm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
