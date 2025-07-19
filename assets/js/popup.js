/**
 * Popup Message System
 * This script provides functions to display popup messages instead of using alerts
 */

// Create popup HTML structure when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get existing popup overlay if it exists
    let popupOverlay = document.querySelector('.popup-overlay');
    let popupClose, popupButton;

    // If popup doesn't exist, create it
    if (!popupOverlay) {
        popupOverlay = document.createElement('div');
        popupOverlay.className = 'popup-overlay';

        const popupContainer = document.createElement('div');
        popupContainer.className = 'popup-container';

        const popupHeader = document.createElement('div');
        popupHeader.className = 'popup-header';

        const popupIcon = document.createElement('div');
        popupIcon.className = 'popup-icon';

        const popupTitle = document.createElement('div');
        popupTitle.className = 'popup-title';

        popupClose = document.createElement('button');
        popupClose.className = 'popup-close';
        popupClose.innerHTML = '×';
        popupClose.setAttribute('aria-label', 'Fermer');

        const popupContent = document.createElement('div');
        popupContent.className = 'popup-content';

        const popupActions = document.createElement('div');
        popupActions.className = 'popup-actions';

        popupButton = document.createElement('button');
        popupButton.className = 'popup-button popup-button-primary';
        popupButton.textContent = 'OK';

        // Assemble the popup structure
        popupHeader.appendChild(popupIcon);
        popupHeader.appendChild(popupTitle);
        popupHeader.appendChild(popupClose);

        popupActions.appendChild(popupButton);

        popupContainer.appendChild(popupHeader);
        popupContainer.appendChild(popupContent);
        popupContainer.appendChild(popupActions);

        popupOverlay.appendChild(popupContainer);

        document.body.appendChild(popupOverlay);
    } else {
        // Get references to existing elements
        popupClose = popupOverlay.querySelector('.popup-close');
        popupButton = popupOverlay.querySelector('.popup-button');
    }

    // Add event listeners for closing the popup
    popupClose.addEventListener('click', closePopup);
    popupButton.addEventListener('click', closePopup);
    popupOverlay.addEventListener('click', function(e) {
        if (e.target === popupOverlay) {
            closePopup();
        }
    });

    // Close popup when Escape key is pressed
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.querySelector('.popup-overlay.active')) {
            closePopup();
        }
    });
});

/**
 * Show a popup message
 * @param {string} message - The message to display
 * @param {string} type - The type of message: 'success', 'error', 'warning', 'info'
 * @param {string} title - The title of the popup
 * @param {Function} callback - Optional callback function to execute when the popup is closed
 */
function showPopup(message, type = 'info', title = '', callback = null) {
    const popupOverlay = document.querySelector('.popup-overlay');
    if (!popupOverlay) return;

    const popupContainer = popupOverlay.querySelector('.popup-container');
    const popupIcon = popupOverlay.querySelector('.popup-icon');
    const popupTitle = popupOverlay.querySelector('.popup-title');
    const popupContent = popupOverlay.querySelector('.popup-content');
    const popupButton = popupOverlay.querySelector('.popup-button');

    // Remove previous type classes
    popupContainer.classList.remove('popup-success', 'popup-error', 'popup-warning', 'popup-info');

    // Set the type class
    popupContainer.classList.add(`popup-${type}`);

    // Set the icon based on type
    let iconSvg = '';
    switch (type) {
        case 'success':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            break;
        case 'error':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            break;
        case 'warning':
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
            break;
        case 'info':
        default:
            iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
            break;
    }
    popupIcon.innerHTML = iconSvg;

    // Set the title and content
    popupTitle.textContent = title || getDefaultTitle(type);
    popupContent.textContent = message;

    // Store the callback
    if (callback) {
        popupButton._callback = callback;
    } else {
        popupButton._callback = null;
    }

    // Show the popup
    popupOverlay.classList.add('active');

    // Focus the button for accessibility
    setTimeout(() => {
        popupButton.focus();
    }, 100);
}

/**
 * Close the popup
 */
function closePopup() {
    const popupOverlay = document.querySelector('.popup-overlay');
    if (!popupOverlay) return;

    popupOverlay.classList.remove('active');

    // Execute callback if exists
    const popupButton = popupOverlay.querySelector('.popup-button');
    if (popupButton && popupButton._callback) {
        setTimeout(() => {
            popupButton._callback();
            popupButton._callback = null;
        }, 300);
    }
}

/**
 * Get default title based on message type
 * @param {string} type - The type of message
 * @returns {string} The default title
 */
function getDefaultTitle(type) {
    switch (type) {
        case 'success':
            return 'Succès';
        case 'error':
            return 'Erreur';
        case 'warning':
            return 'Attention';
        case 'info':
        default:
            return 'Information';
    }
}

/**
 * Show a success popup
 * @param {string} message - The message to display
 * @param {string} title - The title of the popup
 * @param {Function} callback - Optional callback function
 */
function showSuccessPopup(message, title = 'Succès', callback = null) {
    showPopup(message, 'success', title, callback);
}

/**
 * Show an error popup
 * @param {string} message - The message to display
 * @param {string} title - The title of the popup
 * @param {Function} callback - Optional callback function
 */
function showErrorPopup(message, title = 'Erreur', callback = null) {
    showPopup(message, 'error', title, callback);
}

/**
 * Show a warning popup
 * @param {string} message - The message to display
 * @param {string} title - The title of the popup
 * @param {Function} callback - Optional callback function
 */
function showWarningPopup(message, title = 'Attention', callback = null) {
    showPopup(message, 'warning', title, callback);
}

/**
 * Show an info popup
 * @param {string} message - The message to display
 * @param {string} title - The title of the popup
 * @param {Function} callback - Optional callback function
 */
function showInfoPopup(message, title = 'Information', callback = null) {
    showPopup(message, 'info', title, callback);
}

/**
 * Show a form validation error popup with a list of errors
 * @param {Array|string} errors - Array of error messages or a single error message string
 * @param {string} title - The title of the popup
 * @param {Function} callback - Optional callback function
 */
function showFormValidationErrors(errors, title = 'Erreur de validation', callback = null) {
    let errorMessage;

    if (Array.isArray(errors)) {
        // Create a list of errors
        errorMessage = '<ul class="validation-error-list">';
        errors.forEach(error => {
            errorMessage += `<li>${error}</li>`;
        });
        errorMessage += '</ul>';
    } else {
        // Single error message or HTML string
        errorMessage = errors;
    }

    // Use the error popup with custom HTML content
    const popupOverlay = document.querySelector('.popup-overlay');
    if (!popupOverlay) return;

    const popupContainer = popupOverlay.querySelector('.popup-container');
    const popupIcon = popupOverlay.querySelector('.popup-icon');
    const popupTitle = popupOverlay.querySelector('.popup-title');
    const popupContent = popupOverlay.querySelector('.popup-content');
    const popupButton = popupOverlay.querySelector('.popup-button');

    // Remove previous type classes
    popupContainer.classList.remove('popup-success', 'popup-error', 'popup-warning', 'popup-info');

    // Set the type class
    popupContainer.classList.add('popup-error');

    // Set the icon for error
    popupIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';

    // Set the title and content
    popupTitle.textContent = title;
    popupContent.innerHTML = errorMessage; // Use innerHTML to render HTML content

    // Store the callback
    if (callback) {
        popupButton._callback = callback;
    } else {
        popupButton._callback = null;
    }

    // Show the popup
    popupOverlay.classList.add('active');

    // Focus the button for accessibility
    setTimeout(() => {
        popupButton.focus();
    }, 100);
}
