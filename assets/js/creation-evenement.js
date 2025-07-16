document.addEventListener('DOMContentLoaded', () => {
    const formStep1 = document.getElementById('form-step-1');
    const formStep2 = document.getElementById('form-step-2');
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const progressBar = document.getElementById('progress-bar');
    const stepCounter = document.getElementById('step-counter');

    // Image upload and preview
    const imageUploader = document.getElementById('imageUploader');
    const imageUploaderIcon = document.getElementById('imageUploaderIcon');
    const imagePreview = document.getElementById('imagePreview');
    const eventImage = document.getElementById('eventImage');
    const uploadButton = document.getElementById('uploadButton');

    // Ticket preview elements
    const ticketName = document.getElementById('ticket-name');
    const ticketQuantity = document.getElementById('ticket-quantity');
    const ticketPrice = document.getElementById('ticket-price');
    const ticketDescription = document.getElementById('ticket-description');
    const ticketPreview = document.getElementById('ticket-preview');
    const addTicketBtn = document.getElementById('add-ticket-btn');
    const freeTicketCheckbox = document.getElementById('free-ticket');

    // Handle image upload button click
    if (uploadButton && eventImage) {
        uploadButton.addEventListener('click', () => {
            eventImage.click();
        });
    }

    // Handle image selection and preview
    if (eventImage && imagePreview) {
        eventImage.addEventListener('change', function() {
            // Clear previous previews
            imagePreview.innerHTML = '';

            if (this.files.length > 0) {
                // Show the preview container
                imagePreview.style.display = 'flex';

                // Hide the icon
                if (imageUploaderIcon) {
                    imageUploaderIcon.style.display = 'none';
                }

                // Process each selected file
                Array.from(this.files).forEach(file => {
                    const reader = new FileReader();

                    reader.addEventListener('load', function() {
                        // Create a new preview element
                        const previewElement = document.createElement('div');
                        previewElement.className = 'preview-image';
                        previewElement.style.backgroundImage = `url(${this.result})`;

                        // Add it to the preview container
                        imagePreview.appendChild(previewElement);
                    });

                    reader.readAsDataURL(file);
                });
            } else {
                // No files selected, hide preview
                imagePreview.style.display = 'none';

                // Show the icon
                if (imageUploaderIcon) {
                    imageUploaderIcon.style.display = 'block';
                }
            }
        });
    }

    // Update ticket preview as user types
    function updateTicketPreview() {
        const name = ticketName.value || 'Nom du ticket';
        const quantity = ticketQuantity.value || '0';
        let price = ticketPrice.value || '0€';
        const description = ticketDescription.value || 'Aucune description';

        // Check if free ticket is selected
        if (freeTicketCheckbox && freeTicketCheckbox.checked) {
            price = 'Gratuit';
        }

        // Create ticket preview HTML
        const ticketHTML = `
            <div class="ticket-card">
                <div class="ticket-header">
                    <div class="ticket-name">${name}</div>
                    <div class="ticket-price">${price}</div>
                </div>
                <div class="ticket-details">
                    <div class="ticket-quantity">Quantité: ${quantity}</div>
                </div>
                ${description !== 'Aucune description' ? `<div class="ticket-description">${description}</div>` : ''}
            </div>
        `;

        // Update the preview
        ticketPreview.innerHTML = ticketHTML;
    }

    // Add event listeners to ticket form fields
    if (ticketName && ticketPreview) {
        ticketName.addEventListener('input', updateTicketPreview);
        ticketQuantity.addEventListener('input', updateTicketPreview);
        ticketPrice.addEventListener('input', updateTicketPreview);
        ticketDescription.addEventListener('input', updateTicketPreview);

        // Add event listener for free ticket checkbox
        if (freeTicketCheckbox) {
            freeTicketCheckbox.addEventListener('change', function() {
                // Toggle price field disabled state
                if (this.checked) {
                    ticketPrice.disabled = true;
                    ticketPrice.placeholder = 'Gratuit';
                } else {
                    ticketPrice.disabled = false;
                    ticketPrice.placeholder = ' ';
                }

                // Update preview
                updateTicketPreview();
            });
        }

        // Initialize with empty preview
        updateTicketPreview();
    }

    // Add ticket button functionality
    if (addTicketBtn) {
        addTicketBtn.addEventListener('click', () => {
            // Here you would normally save the current ticket and reset the form
            // For now, we'll just reset the form fields
            ticketName.value = '';
            ticketQuantity.value = '';
            ticketPrice.value = '';
            ticketDescription.value = '';

            // Reset the free ticket checkbox
            if (freeTicketCheckbox) {
                freeTicketCheckbox.checked = false;
                ticketPrice.disabled = false;
                ticketPrice.placeholder = ' ';
            }

            // Reset the preview
            ticketPreview.innerHTML = `
                <div class="ticket-preview-empty">
                    Les détails du ticket apparaîtront ici
                </div>
            `;

            // Focus on the first field
            ticketName.focus();
        });
    }

    nextBtn.addEventListener('click', () => {
        formStep1.classList.remove('active');
        formStep2.classList.add('active');
        progressBar.style.width = '100%';
        stepCounter.textContent = 'Étape 2/2';
    });

    prevBtn.addEventListener('click', () => {
        formStep2.classList.remove('active');
        formStep1.classList.add('active');
        progressBar.style.width = '50%';
        stepCounter.textContent = 'Étape 1/2';
    });
});
