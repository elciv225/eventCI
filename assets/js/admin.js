/**
 * Admin Panel JavaScript - Centralized JS for all admin components
 */

// Initialize Lucide icons after the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }

    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');

    if (themeToggle && themeIcon) {
        // Check for saved theme preference or use default
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);

        // Update icon based on current theme
        updateThemeIcon(savedTheme);

        // Toggle theme when button is clicked
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            updateThemeIcon(newTheme);
        });
    }

    // Function to update theme icon
    function updateThemeIcon(theme) {
        if (!themeIcon) return;
        
        if (theme === 'dark') {
            themeIcon.innerHTML = `<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>`;
        } else {
            themeIcon.innerHTML = `<path d="M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path><path d="M12 8a2.83 2.83 0 0 1 1 5.45"></path><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path>`;
        }
    }

    // Add confirmation for delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    if (deleteButtons) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                if (!confirm('Confirmer la suppression ? Cette action est irréversible !')) {
                    e.preventDefault();
                }
            });
        });
    }
});