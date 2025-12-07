// JavaScript for Rugby News Management (moved from inline script to satisfy CSP)

// Initialize section count based on existing sections in DOM
let sectionCount = 1;

function initSectionCount() {
    const sections = document.querySelectorAll('.section-container');
    sectionCount = sections.length > 0 ? sections.length : 1;
    console.log('Initial section count (computed from DOM):', sectionCount);
}

// Ensure the "Add More Sections" button always gets its click handler
function initAddMoreSectionsButton() {
    const addMoreBtn = document.getElementById('add-more-sections');
    if (addMoreBtn && !addMoreBtn.__hasAddFieldsListener) {
        addMoreBtn.addEventListener('click', addFields);
        addMoreBtn.__hasAddFieldsListener = true; // prevent duplicate bindings
        console.log('"Add More Sections" button listener attached');
    } else if (!addMoreBtn) {
        console.warn('"Add More Sections" button not found in DOM');
    }
}

// Delegate clicks on "Remove Section" buttons at the document level
function initSectionRemoveHandler() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-section-btn');
        if (!btn) return;
        // Ensure the button is inside our form/sections area
        const container = document.getElementById('repeatable-fields');
        if (container && container.contains(btn)) {
            e.preventDefault();
            console.log('Remove Section button clicked');
            removeSection(btn);
        }
    });
}

function initUploadPage() {
    initSectionCount();
    initAddMoreSectionsButton();
    initSectionRemoveHandler();
    initMainImagePreview();
    console.log('Script loaded successfully');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUploadPage);
} else {
    initUploadPage();
}

// Add new section
function addFields() {
    console.log('addFields called, current sectionCount:', sectionCount);

    sectionCount++;
    const newIndex = sectionCount - 1;
    let container = document.getElementById('repeatable-fields');

    let div = document.createElement('div');
    div.className = 'section-container mb-4';
    div.setAttribute('data-section-index', newIndex);
    div.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <h3 class="text-xl font-semibold">Section ${sectionCount}</h3>
            <button type="button" onclick="removeSection(this)" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i> Remove Section
            </button>
        </div>
        <input type="hidden" name="existing_images[${newIndex}]" value="">
        <input type="text" name="subtitle[]" placeholder="Subtitle" class="w-full p-2 border rounded mb-2">
        <textarea name="content[]" placeholder="Content" class="w-full p-2 border rounded mb-2"></textarea>
        <div class="image-upload-section mb-4">
            <label class="block text-lg font-semibold mb-2">Media (Images or short videos)</label>
            <div class="flex flex-wrap gap-2 mb-2" id="section-images-${newIndex}"></div>
            <input type="file" name="image[${newIndex}][]" 
                   accept="image/*,video/*" multiple 
                   class="w-full p-2 border rounded"
                   onchange="previewNewImages(this, ${newIndex})">
            <!-- Add media by URL -->
            <div class="mt-2 flex gap-2">
                <input type="url" placeholder="Paste image or video URL" class="w-full p-2 border rounded" id="media-url-input-${newIndex}">
                <button type="button" class="bg-blue-500 text-white px-3 py-2 rounded" onclick="addMediaUrl(${newIndex})">Add URL</button>
            </div>
        </div>
    `;
    container.appendChild(div);

    console.log('New section added with index:', newIndex);
}

// Remove section
function removeSection(button) {
    console.log('removeSection() called');
    const sections = document.querySelectorAll('.section-container');
    if (sections.length > 1) {
        if (confirm('Are you sure you want to remove this section?')) {
            const sectionToRemove = button.closest('.section-container');
            sectionToRemove.remove();

            // Update all sections
            updateSectionIndexes();
        }
    } else {
        alert('You must have at least one section.');
    }
}

// Update all section indexes after removal
function updateSectionIndexes() {
    const sections = document.querySelectorAll('.section-container');
    sectionCount = sections.length;

    sections.forEach((section, index) => {
        // Update data attribute
        section.setAttribute('data-section-index', index);

        // Update section title
        const title = section.querySelector('h3');
        if (title) {
            title.textContent = `Section ${index + 1}`;
        }

        // Update existing images input
        const existingImagesInput = section.querySelector('input[name^="existing_images"]');
        if (existingImagesInput) {
            if (existingImagesInput.name.includes('[]')) {
                const parentName = existingImagesInput.name.match(/existing_images\[(\d+)\]/);
                if (parentName) {
                    existingImagesInput.name = `existing_images[${index}]`;
                }
            } else {
                existingImagesInput.name = `existing_images[${index}]`;
            }
        }

        // Update all existing image thumbnail inputs
        const thumbnailInputs = section.querySelectorAll('input[name^="existing_images["][name$="][]"]');
        thumbnailInputs.forEach(input => {
            input.name = input.name.replace(/existing_images\[\d+\]/, `existing_images[${index}]`);
        });

        // Update file input
        const fileInput = section.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.name = `image[${index}][]`;
            fileInput.setAttribute('onchange', `previewNewImages(this, ${index})`);
        }

        // Update images container ID
        const imagesContainer = section.querySelector('div[id^="section-images-"]');
        if (imagesContainer) {
            imagesContainer.id = `section-images-${index}`;
        }

        // Update media URL input
        const mediaUrlInput = section.querySelector('input[id^="media-url-input-"]');
        if (mediaUrlInput) {
            mediaUrlInput.id = `media-url-input-${index}`;
            const urlButton = mediaUrlInput.nextElementSibling;
            if (urlButton && urlButton.tagName === 'BUTTON') {
                urlButton.setAttribute('onclick', `addMediaUrl(${index})`);
            }
        }

        // Update media URLs hidden inputs
        const mediaUrlHiddenInputs = section.querySelectorAll('input[name^="media_urls["][name$="][]"]');
        mediaUrlHiddenInputs.forEach(input => {
            input.name = input.name.replace(/media_urls\[\d+\]/, `media_urls[${index}]`);
        });

        // Update remove image buttons
        const removeButtons = section.querySelectorAll('button[onclick^="removeImage"]');
        removeButtons.forEach(btn => {
            const onclickAttr = btn.getAttribute('onclick');
            if (onclickAttr) {
                const match = onclickAttr.match(/removeImage\(this,\s*'([^']+)',\s*(\d+)\)/);
                if (match) {
                    const imgPath = match[1];
                    btn.setAttribute('onclick', `removeImage(this, '${imgPath}', ${index})`);
                }
            }
        });

        // Update removed images hidden inputs
        const removedInputs = section.querySelectorAll('input[name^="removed_images["][name$="][]"]');
        removedInputs.forEach(input => {
            input.name = input.name.replace(/removed_images\[\d+\]/, `removed_images[${index}]`);
        });
    });

    console.log('Sections reindexed, new count:', sectionCount);
}

// Remove individual image
function removeImage(button, imagePath, sectionIndex) {
    if (confirm('Remove this image?')) {
        const form = document.querySelector('form');
        const removedInput = document.createElement('input');
        removedInput.type = 'hidden';
        removedInput.name = `removed_images[${sectionIndex}][]`;
        removedInput.value = imagePath;
        removedInput.className = 'hidden-removed-image';
        form.appendChild(removedInput);

        button.closest('.image-thumbnail').remove();
    }
}

// Remove main image
function removeMainImage() {
    if (confirm('Remove the main image?')) {
        const rm = document.getElementById('remove_main_image');
        if (rm) {
            rm.value = '1';
        }
        const preview = document.querySelector('.image-preview');
        if (preview) {
            preview.innerHTML = '<div class="image-preview-placeholder"><i class="fas fa-image"></i></div>';
        }
        const mainInput = document.getElementById('main_image');
        if (mainInput) {
            mainInput.value = '';
        }
    }
}

// Preview newly added images before upload
function previewNewImages(input, sectionIndex) {
    let container = document.getElementById(`section-images-${sectionIndex}`);
    if (!container) {
        container = document.createElement('div');
        container.id = `section-images-${sectionIndex}`;
        container.className = 'flex flex-wrap gap-2 mb-2';
        input.parentNode.insertBefore(container, input);
    }

    Array.from(input.files).forEach(file => {
        const url = URL.createObjectURL(file);
        const wrap = document.createElement('div');
        wrap.className = 'image-thumbnail relative';
        if (file.type.startsWith('video/')) {
            wrap.innerHTML = `
                <video src="${url}" class="h-24 w-40 object-cover" controls muted playsinline></video>
                <button type="button" onclick="this.parentElement.remove()" 
                        class="remove-image-btn opacity-0">
                    <i class="fas fa-times"></i>
                </button>
            `;
        } else {
            wrap.innerHTML = `
                <img src="${url}" class="h-24 w-40 object-cover">
                <button type="button" onclick="this.parentElement.remove()" 
                        class="remove-image-btn opacity-0">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }
        container.appendChild(wrap);
    });

    input.value = '';
}

// Validate media URL
function isValidMediaUrl(url) {
    try {
        new URL(url);
    } catch (e) {
        return false;
    }
    const u = new URL(url);
    const path = u.pathname.toLowerCase();
    const host = u.hostname.toLowerCase();
    const exts = ['jpg','jpeg','png','gif','webp','avif','svg','mp4','webm','ogg','mov'];
    const isExt = exts.some(ext => path.endsWith('.' + ext));
    const isYouTube = host.includes('youtube.com') || host.includes('youtu.be') || host.includes('youtube-nocookie.com');
    return isExt || isYouTube;
}

// Add media by URL to a section
function addMediaUrl(sectionIndex) {
    const input = document.getElementById(`media-url-input-${sectionIndex}`);
    if (!input) return;
    const url = (input.value || '').trim();
    if (!url) {
        alert('Please enter a URL');
        return;
    }
    if (!isValidMediaUrl(url)) {
        alert('Please paste a direct URL to an image or a short video (mp4/webm/ogg/mov) or a YouTube link.');
        return;
    }

    let container = document.getElementById(`section-images-${sectionIndex}`);
    if (!container) {
        container = document.createElement('div');
        container.id = `section-images-${sectionIndex}`;
        container.className = 'flex flex-wrap gap-2 mb-2';
        const fileInput = document.querySelector(`input[name="image[${sectionIndex}][]"]`);
        if (fileInput && fileInput.parentNode) {
            fileInput.parentNode.insertBefore(container, fileInput);
        }
    }

    const wrap = document.createElement('div');
    wrap.className = 'image-thumbnail relative';

    const isYouTube = url.includes('youtube.com') || url.includes('youtu.be') || url.includes('youtube-nocookie.com');
    const isVideo = /(\.mp4|\.webm|\.ogg|\.mov)(\?|#|$)/i.test(url);

    if (isYouTube) {
        const videoId = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i);
        const vidId = videoId ? videoId[1] : '';
        if (vidId) {
            wrap.innerHTML = `
                <iframe src="https://www.youtube-nocookie.com/embed/${vidId}" 
                        class="h-24 w-40 object-cover" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen loading="lazy"></iframe>
                <button type="button" onclick="this.parentElement.remove()" class="remove-image-btn opacity-0">
                    <i class="fas fa-times"></i>
                </button>
                <input type="hidden" name="media_urls[${sectionIndex}][]" value="${url}">
            `;
        } else {
            alert('Invalid YouTube URL');
            return;
        }
    } else if (isVideo) {
        wrap.innerHTML = `
            <video src="${url}" class="h-24 w-40 object-cover" controls muted playsinline></video>
            <button type="button" onclick="this.parentElement.remove()" class="remove-image-btn opacity-0">
                <i class="fas fa-times"></i>
            </button>
            <input type="hidden" name="media_urls[${sectionIndex}][]" value="${url}">
        `;
    } else {
        wrap.innerHTML = `
            <img src="${url}" class="h-24 w-40 object-cover">
            <button type="button" onclick="this.parentElement.remove()" class="remove-image-btn opacity-0">
                <i class="fas fa-times"></i>
            </button>
            <input type="hidden" name="media_urls[${sectionIndex}][]" value="${url}">
        `;
    }
    container.appendChild(wrap);
    input.value = '';
}

// Initialize main image preview listener
function initMainImagePreview() {
    const mainImageInput = document.getElementById('main_image');
    if (!mainImageInput) return;

    mainImageInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                const preview = document.querySelector('.image-preview');
                if (preview) {
                    preview.innerHTML = `
                        <img src="${event.target.result}" alt="Preview">
                        <button type="button" onclick="removeMainImage()" class="remove-image-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                }
                const rm = document.getElementById('remove_main_image');
                if (rm) { rm.value = '0'; }
            };
            reader.readAsDataURL(file);
        }
    });
}
