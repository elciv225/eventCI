// --- Gestion du Carousel ---
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.carousel-slide');
    const indicatorsContainer = document.getElementById('carouselIndicators');
    let currentSlide = 0;

    // --- Gestion des messages d'erreur ---
    const errorMessages = document.querySelectorAll('.error-message');

    // Function to handle error message fadeout
    function setupErrorMessageFadeout(errorMessage) {
        // Add a class for the fadeout animation after 5 seconds
        setTimeout(() => {
            errorMessage.style.animation = 'fadeOut 0.5s ease-out forwards';

            // Remove the element after the animation completes
            setTimeout(() => {
                if (errorMessage.parentNode) {
                    errorMessage.parentNode.removeChild(errorMessage);
                }
            }, 500); // Match this to the animation duration
        }, 5000); // 5 seconds delay before fadeout starts
    }

    // Setup fadeout for all error messages
    errorMessages.forEach(setupErrorMessageFadeout);

    // Create indicators
    if (slides.length > 1 && indicatorsContainer) {
        slides.forEach((_, i) => {
            const indicator = document.createElement('span');
            indicatorsContainer.appendChild(indicator);
        });
        updateIndicators();
    }

    function updateIndicators() {
        const indicators = indicatorsContainer.querySelectorAll('span');
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === currentSlide);
        });
    }

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        if (slides.length > 1) {
            updateIndicators();
        }
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    if (slides.length > 1) {
        setInterval(nextSlide, 5000);
    }

    // --- Gestion du basculement des formulaires via ancre URL ---
    const connexionForm = document.getElementById('connexion');
    const inscriptionForm = document.getElementById('inscription');
    const formPanel = document.getElementById('formPanel');

    function toggleForms() {
        const hash = window.location.hash;
        if (formPanel) {
            formPanel.scrollTop = 0;
        }

        if (hash === '#inscription') {
            connexionForm.classList.remove('active');
            inscriptionForm.classList.add('active');
        } else {
            inscriptionForm.classList.remove('active');
            connexionForm.classList.add('active');
        }
    }

    window.addEventListener('hashchange', toggleForms);

    // Initialize forms and slides
    showSlide(0); // Show first slide initially
    toggleForms();

    // --- Gestion de l'aperçu photo ---
    const photoInput = document.getElementById('photo');
    const photoUploader = document.getElementById('photoUploader');
    if (photoUploader && photoInput) {
        const photoUploaderIcon = photoUploader.querySelector('.photo-uploader-icon');
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.addEventListener('load', function () {
                    photoUploader.style.backgroundImage = `url(${this.result})`;
                    if (photoUploaderIcon) {
                        photoUploaderIcon.style.display = 'none';
                    }
                });
                reader.readAsDataURL(file);
            } else {
                photoUploader.style.backgroundImage = 'none';
                if (photoUploaderIcon) {
                    photoUploaderIcon.style.display = 'block';
                }
            }
        });
    }

    // --- Gestion de la visibilité du mot de passe ---
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Fix: Get the input element that is a sibling of this button
            const passwordInput = this.parentNode.querySelector('input[type="password"], input[type="text"]');
            const eyeOpen = this.querySelector('.eye-open');
            const eyeClosed = this.querySelector('.eye-closed');

            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'block';
                } else {
                    passwordInput.type = 'password';
                    eyeOpen.style.display = 'block';
                    eyeClosed.style.display = 'none';
                }
            }
        });
    });
});
