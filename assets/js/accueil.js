document.addEventListener('DOMContentLoaded', function() {
    // --- Theme Toggle Logic ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    if (themeToggleBtn && themeIcon) {
        themeToggleBtn.addEventListener('click', function() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');

            // Toggle theme
            if (currentTheme === 'dark') {
                body.setAttribute('data-theme', 'light');
                // Change to sun icon for light theme
                themeIcon.innerHTML = '<path d="M128,56a72,72,0,1,0,72,72A72.08,72.08,0,0,0,128,56Zm0,128a56,56,0,1,1,56-56A56.06,56.06,0,0,1,128,184ZM128,24A8,8,0,0,0,136,16V8a8,8,0,0,0-16,0v8A8,8,0,0,0,128,24Zm0,208a8,8,0,0,0-8,8v8a8,8,0,0,0,16,0v-8A8,8,0,0,0,128,232ZM248,128a8,8,0,0,0,8-8v-8a8,8,0,0,0-16,0v8A8,8,0,0,0,248,128ZM16,120a8,8,0,0,0-8,8v8a8,8,0,0,0,16,0v-8A8,8,0,0,0,16,120ZM208,72a8,8,0,0,0,5.66-2.34l5.66-5.66a8,8,0,0,0-11.32-11.31l-5.65,5.65A8,8,0,0,0,208,72Zm-160,0a8,8,0,0,0,5.66-13.65L48,52.69A8,8,0,0,0,36.69,64l5.65,5.66A8,8,0,0,0,48,72Zm160,112a8,8,0,0,0-5.66,13.66l5.66,5.65a8,8,0,0,0,11.31-11.31l-5.65-5.66A8,8,0,0,0,208,184ZM42.34,189.66l5.66-5.66a8,8,0,0,0-11.32-11.31L31,178.34a8,8,0,0,0,11.31,11.31Z"></path>';
            } else {
                body.setAttribute('data-theme', 'dark');
                // Change to moon icon for dark theme
                themeIcon.innerHTML = '<path d="M233.54,142.23a8,8,0,0,0-8-2,88.08,88.08,0,0,1-109.8-109.8,8,8,0,0,0-10-10,104.84,104.84,0,0,0-52.91,37A104,104,0,0,0,136,224a103.09,103.09,0,0,0,62.52-20.88,104.84,104.84,0,0,0,37-52.91A8,8,0,0,0,233.54,142.23ZM188.9,190.34A88,88,0,0,1,65.66,67.11a89,89,0,0,1,31.4-26A106,106,0,0,0,96,56A104.11,104.11,0,0,0,200,160a106,106,0,0,0,14.92-1.06A89,89,0,0,1,188.9,190.34Z"></path>';
            }
        });

        // Initialize icon based on current theme
        const currentTheme = document.body.getAttribute('data-theme');
        if (currentTheme === 'light') {
            themeIcon.innerHTML = '<path d="M128,56a72,72,0,1,0,72,72A72.08,72.08,0,0,0,128,56Zm0,128a56,56,0,1,1,56-56A56.06,56.06,0,0,1,128,184ZM128,24A8,8,0,0,0,136,16V8a8,8,0,0,0-16,0v8A8,8,0,0,0,128,24Zm0,208a8,8,0,0,0-8,8v8a8,8,0,0,0,16,0v-8A8,8,0,0,0,128,232ZM248,128a8,8,0,0,0,8-8v-8a8,8,0,0,0-16,0v8A8,8,0,0,0,248,128ZM16,120a8,8,0,0,0-8,8v8a8,8,0,0,0,16,0v-8A8,8,0,0,0,16,120ZM208,72a8,8,0,0,0,5.66-2.34l5.66-5.66a8,8,0,0,0-11.32-11.31l-5.65,5.65A8,8,0,0,0,208,72Zm-160,0a8,8,0,0,0,5.66-13.65L48,52.69A8,8,0,0,0,36.69,64l5.65,5.66A8,8,0,0,0,48,72Zm160,112a8,8,0,0,0-5.66,13.66l5.66,5.65a8,8,0,0,0,11.31-11.31l-5.65-5.66A8,8,0,0,0,208,184ZM42.34,189.66l5.66-5.66a8,8,0,0,0-11.32-11.31L31,178.34a8,8,0,0,0,11.31,11.31Z"></path>';
        }
    }

    // --- Card Carousel Logic ---
    const carousels = document.querySelectorAll('[data-carousel]');
    carousels.forEach(wrapper => {
        const carousel = wrapper;
        const images = carousel.querySelectorAll('.event-card-image');
        // Les boutons sont des frères du carrousel dans le même conteneur (event-card-image-wrapper)
        const imageWrapper = wrapper.parentElement;
        const prevBtn = imageWrapper.querySelector('.carousel-arrow.prev');
        const nextBtn = imageWrapper.querySelector('.carousel-arrow.next');

        // Get the card element and its text content
        const card = imageWrapper.closest('.event-card');
        const cardTextContainer = card.querySelector('a > div');

        // Hide the text content initially
        if (cardTextContainer) {
            cardTextContainer.style.opacity = '0';
            cardTextContainer.style.transition = 'opacity 0.3s ease';
        }

        let currentIndex = 0;
        let intervalId = null;
        let isHovered = false;
        let imagesLoaded = 0;
        let totalImages = images.length;

        // Create and add skeleton loader
        const loader = document.createElement('div');
        loader.className = 'carousel-loader';

        // Create skeleton structure
        const skeletonCard = document.createElement('div');
        skeletonCard.className = 'skeleton-card';

        // Create skeleton image
        const skeletonImage = document.createElement('div');
        skeletonImage.className = 'skeleton skeleton-image';

        // Create skeleton content
        const skeletonContent = document.createElement('div');
        skeletonContent.className = 'skeleton-card-content';

        // Create skeleton title
        const skeletonTitle = document.createElement('div');
        skeletonTitle.className = 'skeleton skeleton-text skeleton-title';

        // Create skeleton description
        const skeletonDesc = document.createElement('div');
        skeletonDesc.className = 'skeleton skeleton-text skeleton-desc';

        // Assemble the skeleton
        skeletonContent.appendChild(skeletonTitle);
        skeletonContent.appendChild(skeletonDesc);
        skeletonCard.appendChild(skeletonImage);
        skeletonCard.appendChild(skeletonContent);
        loader.appendChild(skeletonCard);

        imageWrapper.appendChild(loader);

        // Function to check if all images are loaded
        function checkImagesLoaded() {
            imagesLoaded++;
            if (imagesLoaded === totalImages) {
                // All images loaded, clear the timeout
                clearTimeout(loadingTimeout);

                // Fade out loader with transition
                loader.style.opacity = '0';

                // Remove loader from DOM after transition completes
                setTimeout(() => {
                    loader.style.display = 'none';

                    // Show the card text with a smooth transition
                    if (cardTextContainer) {
                        cardTextContainer.style.opacity = '1';
                    }
                }, 300); // Match this with the transition duration in CSS
            }
        }

        // Add load event listeners to all background images with optimization
        // Set a global timeout to prevent infinite loading
        const loadingTimeout = setTimeout(() => {
            if (imagesLoaded < totalImages) {
                // Force complete loading after timeout
                while (imagesLoaded < totalImages) {
                    checkImagesLoaded();
                }
            }
        }, 3000); // 3 second timeout

        // Process images with priority for the first one
        images.forEach((imgContainer, index) => {
            const imgElement = imgContainer.querySelector('img');
            if (imgElement && imgElement.src) {
                const tempImg = new Image();
                tempImg.onload = checkImagesLoaded;
                tempImg.onerror = checkImagesLoaded; // Count errors as loaded to avoid stuck loader

                // Set high priority for the first image
                if (index === 0) {
                    tempImg.fetchPriority = "high";
                    tempImg.loading = "eager";
                } else {
                    tempImg.loading = "lazy";
                }

                tempImg.src = imgElement.src;
            } else {
                console.error('No image found in container:', imgContainer);
                checkImagesLoaded(); // No image found, count as loaded
            }
        });

        function showImage(index) {
            // S'assurer que l'index est dans les limites correctes
            let newIndex;
            if (index < 0) {
                newIndex = images.length - 1;
            } else if (index >= images.length) {
                newIndex = 0;
            } else {
                newIndex = index;
            }

            // Update currentIndex and apply the transformation
            currentIndex = newIndex;
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
        }

        function startCarousel() {
            if (intervalId) clearInterval(intervalId);
            intervalId = setInterval(() => {
                showImage(currentIndex + 1);
            }, 3000);
        }

        function stopCarousel() {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
            }
        }

        function resetToFirst() {
            currentIndex = 0;
            carousel.style.transform = `translateX(0%)`;
        }

        // Hide or show arrows based on number of images
        if (images.length <= 1) {
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
        } else {
            // Hover behavior - démarre le carrousel automatique
            imageWrapper.addEventListener('mouseenter', () => {
                isHovered = true;
                startCarousel();
            });

            // Quand on arrête de hover, on arrête le carrousel mais on garde la position actuelle
            imageWrapper.addEventListener('mouseleave', () => {
                isHovered = false;
                stopCarousel();
                // Ne pas réinitialiser à la première image
            });

            // Arrow click behavior - navigation manuelle
            if (prevBtn && nextBtn) {
                prevBtn.addEventListener('click', (e) => {
                    // Empêcher la propagation de l'événement et le comportement par défaut
                    e.preventDefault();
                    e.stopPropagation();

                    // Arrêter le carrousel automatique temporairement
                    stopCarousel();

                    // Naviguer vers l'image précédente
                    showImage(currentIndex - 1);

                    // Redémarrer le carrousel automatique si on est toujours en hover
                    if (isHovered) {
                        setTimeout(() => {
                            if (isHovered) {
                                startCarousel();
                            }
                        }, 1000); // Délai d'1 seconde avant de redémarrer
                    }
                });

                nextBtn.addEventListener('click', (e) => {
                    // Empêcher la propagation de l'événement et le comportement par défaut
                    e.preventDefault();
                    e.stopPropagation();

                    // Arrêter le carrousel automatique temporairement
                    stopCarousel();

                    // Naviguer vers l'image suivante
                    showImage(currentIndex + 1);

                    // Redémarrer le carrousel automatique si on est toujours en hover
                    if (isHovered) {
                        setTimeout(() => {
                            if (isHovered) {
                                startCarousel();
                            }
                        }, 1000); // Délai d'1 seconde avant de redémarrer
                    }
                });
            }
        }
    });

    // --- Horizontal Scroll Container Logic ---
    const horizontalScrollContainers = document.querySelectorAll('.horizontal-scroll-container');
    horizontalScrollContainers.forEach(container => {
        const prevBtn = container.querySelector('.horizontal-scroll-nav.prev');
        const nextBtn = container.querySelector('.horizontal-scroll-nav.next');
        const cards = container.querySelectorAll('.event-card');

        if (prevBtn && nextBtn && cards.length > 0) {
            // Calculate how many cards to scroll (3 or less if not enough cards)
            const scrollAmount = Math.min(3, cards.length);

            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                // Scroll to show the next 3 cards
                const cardWidth = cards[0].offsetWidth;
                const gap = parseInt(window.getComputedStyle(container).getPropertyValue('gap'));
                const scrollDistance = (cardWidth + gap) * scrollAmount;
                container.scrollBy({ left: scrollDistance, behavior: 'smooth' });
            });

            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                // Scroll to show the previous 3 cards
                const cardWidth = cards[0].offsetWidth;
                const gap = parseInt(window.getComputedStyle(container).getPropertyValue('gap'));
                const scrollDistance = (cardWidth + gap) * scrollAmount;
                container.scrollBy({ left: -scrollDistance, behavior: 'smooth' });
            });
        }
    });
});
