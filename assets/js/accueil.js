document.addEventListener('DOMContentLoaded', function() {
    // Common elements
    const header = document.querySelector('.header-principale');

    // --- Page Loader Logic ---
    const pageLoader = document.getElementById('page-loader');
    if (pageLoader && header) {
        // Position the loader under the header
        const positionLoader = function() {
            const headerHeight = header.offsetHeight;
            pageLoader.style.top = headerHeight + 'px';
            pageLoader.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
        };

        // Position immediately and on resize
        positionLoader();
        window.addEventListener('resize', positionLoader);

        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
            setTimeout(function() {
                pageLoader.classList.add('loaded');
                // Remove from DOM after animation completes
                setTimeout(function() {
                    if (pageLoader.parentNode) {
                        pageLoader.parentNode.removeChild(pageLoader);
                    }
                }, 300); // Match the duration of the loaderFadeOut animation
            }, 500); // Small delay to ensure the loader is visible even on fast loads
        });
    }

    // --- Sticky Header Logic ---
    const mobileSearch = document.querySelector('.mobile-search-section');
    let lastScrollTop = 0;
    const scrollThreshold = 100; // Pixels to scroll before hiding header
    let isHeaderVisible = true;

    function handleScroll() {
        const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Determine scroll direction
        if (currentScrollTop > lastScrollTop && currentScrollTop > scrollThreshold) {
            // Scrolling down past threshold
            if (isHeaderVisible) {
                // Hide header
                header.style.transform = 'translateY(-100%)';
                isHeaderVisible = false;
                document.body.classList.add('header-hidden');

                // If mobile search exists, move it to top position
                if (mobileSearch) {
                    mobileSearch.style.transform = 'translateY(0)';
                    mobileSearch.style.top = '0';
                }
            }
        } else if (currentScrollTop < lastScrollTop) {
            // Scrolling up
            if (!isHeaderVisible) {
                // Show header
                header.style.transform = 'translateY(0)';
                isHeaderVisible = true;
                document.body.classList.remove('header-hidden');

                // If mobile search exists, move it below header
                if (mobileSearch) {
                    mobileSearch.style.transform = 'translateY(0)';
                    // When header is visible, position search below it
                    mobileSearch.style.top = header.offsetHeight + 'px';
                }
            }
        }

        lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop; // For Mobile or negative scrolling
    }

    // Set initial position for mobile search if it exists
    if (mobileSearch && header) {
        mobileSearch.style.top = header.offsetHeight + 'px';
    }

    // Add scroll event listener
    window.addEventListener('scroll', handleScroll, { passive: true });

    // Update mobile search position on window resize
    window.addEventListener('resize', function() {
        if (mobileSearch && header && isHeaderVisible) {
            mobileSearch.style.top = header.offsetHeight + 'px';
        }
    });

    // --- User Menu Dropdown Logic ---
    const userMenu = document.querySelector('.user-menu');
    const authMenu = document.querySelector('.auth-menu');

    // Handle user menu dropdown (when logged in)
    if (userMenu) {
        const userDropdown = userMenu.querySelector('.user-dropdown');

        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
    }

    // Handle auth menu dropdown (when not logged in)
    if (authMenu) {
        const authDropdown = authMenu.querySelector('.auth-dropdown');
        const loginButton = document.querySelector('.header-nav .login-button');

        if (loginButton) {
            loginButton.addEventListener('click', function(e) {
                // Don't stop propagation here to allow the mobile menu to close when clicked
                // Just prevent default to avoid navigating away immediately
                e.preventDefault();
                window.location.href = this.getAttribute('href');
            });
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const userDropdown = document.querySelector('.user-dropdown');
        const authDropdown = document.querySelector('.auth-dropdown');

        if (userDropdown && !e.target.closest('.user-menu')) {
            userDropdown.classList.remove('active');
        }

        if (authDropdown && !e.target.closest('.auth-menu')) {
            authDropdown.classList.remove('active');
        }
    });

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

    // --- Mobile Menu Toggle Logic ---
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenuOverlay = document.querySelector('.mobile-menu-overlay');

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            document.body.classList.toggle('mobile-menu-open');
            // The CSS transitions will handle the animation automatically
        });

        // Close mobile menu when clicking on the overlay
        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', function() {
                document.body.classList.remove('mobile-menu-open');
                // The CSS transitions will handle the animation automatically
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (document.body.classList.contains('mobile-menu-open') && 
                !event.target.closest('.header-nav') && 
                !event.target.closest('.mobile-menu-toggle') &&
                !event.target.closest('.mobile-menu-overlay')) {
                document.body.classList.remove('mobile-menu-open');
                // The CSS transitions will handle the animation automatically
            }
        });

        // Close mobile menu when clicking on a nav link
        const navLinks = document.querySelectorAll('.header-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Don't close menu immediately for login button to allow dropdown
                if (!this.classList.contains('login-button')) {
                    document.body.classList.remove('mobile-menu-open');
                    // The CSS transitions will handle the animation automatically
                }
            });
        });
    }

    // --- Card Carousel Logic ---
    const carousels = document.querySelectorAll('[data-carousel]');
    carousels.forEach(wrapper => {
        const carousel = wrapper;
        const images = carousel.querySelectorAll('.event-card-image');
        const imageWrapper = wrapper.parentElement;
        const prevBtn = imageWrapper.querySelector('.carousel-arrow.prev');
        const nextBtn = imageWrapper.querySelector('.carousel-arrow.next');
        const card = imageWrapper.closest('.event-card');
        const cardTextContainer = card.querySelector('a > div');

        if (cardTextContainer) {
            cardTextContainer.style.opacity = '0';
            cardTextContainer.style.transition = 'opacity 0.3s ease';
        }

        const loader = document.createElement('div');
        loader.className = 'carousel-loader';
        loader.style.visibility = 'visible';
        const skeletonCard = document.createElement('div');
        skeletonCard.className = 'skeleton-card';
        const skeletonImage = document.createElement('div');
        skeletonImage.className = 'skeleton skeleton-image';
        const skeletonContent = document.createElement('div');
        skeletonContent.className = 'skeleton-card-content';
        const skeletonTitle = document.createElement('div');
        skeletonTitle.className = 'skeleton skeleton-text skeleton-title';
        const skeletonDesc = document.createElement('div');
        skeletonDesc.className = 'skeleton skeleton-text skeleton-desc';
        skeletonContent.appendChild(skeletonTitle);
        skeletonContent.appendChild(skeletonDesc);
        skeletonCard.appendChild(skeletonImage);
        skeletonCard.appendChild(skeletonContent);
        loader.appendChild(skeletonCard);
        imageWrapper.appendChild(loader);

        let currentIndex = 0;
        let intervalId = null;
        let isHovered = false;
        let imagesLoaded = 0;
        let totalImages = images.length;

        // =================================================================
        // DÉBUT DE LA CORRECTION
        // =================================================================
        // Si la carte n'a aucune image, on retire le loader immédiatement.
        if (totalImages === 0) {
            // On rend le loader invisible
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';

            // On le supprime du DOM après la transition et on affiche le texte
            setTimeout(() => {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
                if (cardTextContainer) {
                    cardTextContainer.style.opacity = '1';
                }
            }, 300); // Doit correspondre à la durée de la transition CSS

            // On arrête le script pour cette carte, car il n'y a rien à faire.
            return;
        }
        // =================================================================
        // FIN DE LA CORRECTION
        // =================================================================

        function createImageWarning(container, index) {
            const warningElement = document.createElement('div');
            warningElement.className = 'image-load-warning';
            warningElement.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>L'image n'a pas pu être chargée</p>
        `;
            container.appendChild(warningElement);
            if (index === 0 && cardTextContainer) {
                cardTextContainer.style.opacity = '1';
            }
            return warningElement;
        }

        const failedImages = new Set();
        function checkImagesLoaded() {
            imagesLoaded++;
            if (imagesLoaded >= totalImages) {
                clearTimeout(loadingTimeout);
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                    if (cardTextContainer) {
                        cardTextContainer.style.opacity = '1';
                    }
                }, 300);
            }
        }

        const loadingTimeout = setTimeout(() => {
            if (imagesLoaded < totalImages) {
                images.forEach((imgContainer, index) => {
                    if (!imgContainer.dataset.loaded && !failedImages.has(index)) {
                        failedImages.add(index);
                        createImageWarning(imgContainer, index);
                    }
                });
                // Force la suppression du loader
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                    if (cardTextContainer) {
                        cardTextContainer.style.opacity = '1';
                    }
                }, 300);
            }
        }, 5000); // 5 secondes de timeout

        images.forEach((imgContainer, index) => {
            const imgElement = imgContainer.querySelector('img');
            if (imgElement && imgElement.src) {
                const tempImg = new Image();
                tempImg.onload = () => {
                    imgContainer.dataset.loaded = 'true';
                    checkImagesLoaded();
                };
                tempImg.onerror = () => {
                    failedImages.add(index);
                    createImageWarning(imgContainer, index);
                    checkImagesLoaded();
                };
                tempImg.src = imgElement.src;
            } else {
                failedImages.add(index);
                createImageWarning(imgContainer, index);
                checkImagesLoaded();
            }
        });

        function showImage(index) {
            let newIndex = Math.max(0, Math.min(index, images.length - 1));
            currentIndex = newIndex;
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
        }

        function startCarousel() {
            if (intervalId) clearInterval(intervalId);
            intervalId = setInterval(() => {
                if (currentIndex < images.length - 1) {
                    showImage(currentIndex + 1);
                } else {
                    stopCarousel();
                }
            }, 3000);
        }

        function stopCarousel() {
            clearInterval(intervalId);
            intervalId = null;
        }

        imageWrapper.addEventListener('mouseenter', () => {
            isHovered = true;
            startCarousel();
        });

        imageWrapper.addEventListener('mouseleave', () => {
            isHovered = false;
            stopCarousel();
        });

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                stopCarousel();
                showImage(currentIndex - 1);
                if (isHovered) {
                    setTimeout(() => { if (isHovered) startCarousel(); }, 1000);
                }
            });

            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                stopCarousel();
                showImage(currentIndex + 1);
                if (isHovered) {
                    setTimeout(() => { if (isHovered) startCarousel(); }, 1000);
                }
            });
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
