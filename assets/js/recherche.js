document.addEventListener('DOMContentLoaded', function() {
    // --- Filter Toggle Logic ---
    const toggleFiltersBtn = document.getElementById('toggle-filters');
    const filterContainer = document.getElementById('filter-container');
    
    if (toggleFiltersBtn && filterContainer) {
        toggleFiltersBtn.addEventListener('click', function() {
            filterContainer.classList.toggle('active');
            
            // Change button text based on state
            if (filterContainer.classList.contains('active')) {
                toggleFiltersBtn.querySelector('svg').innerHTML = '<path d="M200,128a8,8,0,0,1-8,8H64a8,8,0,0,1,0-16H192A8,8,0,0,1,200,128ZM64,72H192a8,8,0,0,0,0-16H64a8,8,0,0,0,0,16ZM192,184H64a8,8,0,0,0,0,16H192a8,8,0,0,0,0-16Z"></path>';
            } else {
                toggleFiltersBtn.querySelector('svg').innerHTML = '<path d="M200,128a8,8,0,0,1-8,8H64a8,8,0,0,1,0-16H192A8,8,0,0,1,200,128ZM64,72H192a8,8,0,0,0,0-16H64a8,8,0,0,0,0,16ZM192,184H64a8,8,0,0,0,0,16H192a8,8,0,0,0,0-16Z"></path>';
            }
        });
        
        // Close filter container when clicking outside
        document.addEventListener('click', function(event) {
            if (filterContainer.classList.contains('active') && 
                !event.target.closest('#filter-container') && 
                !event.target.closest('#toggle-filters')) {
                filterContainer.classList.remove('active');
            }
        });
    }
    
    // --- Sticky Header Logic (similar to accueil.js) ---
    const header = document.querySelector('.header-principale');
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
});