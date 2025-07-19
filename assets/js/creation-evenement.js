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

    // Multiple tickets preview
    const allTicketsPreview = document.getElementById('all-tickets-preview');

    // Array to store all tickets
    let savedTickets = [];

    // Handle image upload button click
    if (uploadButton && eventImage) {
        uploadButton.addEventListener('click', () => {
            eventImage.click();
        });
    }

    // Store uploaded files in an array for easier management
    let uploadedFiles = [];

    // Handle image selection and preview
    if (eventImage && imagePreview) {
        eventImage.addEventListener('change', function() {
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
                        // Add file to our array with its data URL
                        const fileIndex = uploadedFiles.length;
                        uploadedFiles.push({
                            file: file,
                            dataUrl: this.result
                        });

                        // Create a new preview element
                        const previewElement = document.createElement('div');
                        previewElement.className = 'preview-image';
                        previewElement.style.backgroundImage = `url(${this.result})`;
                        previewElement.dataset.index = fileIndex;

                        // Create delete button
                        const deleteButton = document.createElement('div');
                        deleteButton.className = 'preview-image-delete';
                        deleteButton.innerHTML = '×';
                        deleteButton.dataset.index = fileIndex;

                        // Add delete functionality
                        deleteButton.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const index = parseInt(this.dataset.index);

                            // Remove from DOM
                            const previewToRemove = document.querySelector(`.preview-image[data-index="${index}"]`);
                            if (previewToRemove) {
                                previewToRemove.remove();
                            }

                            // Remove from array (set to null to maintain indices)
                            uploadedFiles[index] = null;

                            // If no more valid files, show the icon again
                            const hasValidFiles = uploadedFiles.some(item => item !== null);
                            if (!hasValidFiles) {
                                imagePreview.style.display = 'none';
                                if (imageUploaderIcon) {
                                    imageUploaderIcon.style.display = 'block';
                                }
                            }
                        });

                        // Add delete button to preview
                        previewElement.appendChild(deleteButton);

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
        let price = ticketPrice.value || '0';
        const description = ticketDescription.value || 'Aucune description';

        // Check if free ticket is selected
        if (freeTicketCheckbox && freeTicketCheckbox.checked) {
            price = 'Gratuit';
            // Make sure the price value is 0
            ticketPrice.value = '0';
        } else {
            // Remove any non-numeric characters except decimal point for validation
            const numericPrice = price.replace(/[^\d.]/g, '');
            // Ensure it's a valid number
            if (isNaN(parseFloat(numericPrice))) {
                price = '0€';
            } else {
                // Format with currency symbol for display
                price = numericPrice + '€';
            }
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
        if (ticketPreview) {
            ticketPreview.innerHTML = ticketHTML;
        }
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
                    ticketPrice.value = '0'; // Set value to 0 when checked
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

    // Function to render all saved tickets
    function renderSavedTickets() {
        if (!allTicketsPreview) return;

        if (savedTickets.length === 0) {
            allTicketsPreview.innerHTML = `
                <div class="tickets-preview-empty">
                    Aucun ticket ajouté
                </div>
            `;
            return;
        }

        allTicketsPreview.innerHTML = '';

        savedTickets.forEach((ticket, index) => {
            const ticketElement = document.createElement('div');
            ticketElement.className = 'ticket-card';
            ticketElement.innerHTML = `
                <div class="ticket-header">
                    <div class="ticket-name">${ticket.name}</div>
                    <div class="ticket-price">${ticket.price}</div>
                </div>
                <div class="ticket-details">
                    <div class="ticket-quantity">Quantité: ${ticket.quantity}</div>
                </div>
                ${ticket.description !== 'Aucune description' ? `<div class="ticket-description">${ticket.description}</div>` : ''}
            `;
            allTicketsPreview.appendChild(ticketElement);
        });
    }

    // Add ticket button functionality
    if (addTicketBtn) {
        addTicketBtn.addEventListener('click', () => {
            // Save the current ticket data
            const name = ticketName.value || 'Nom du ticket';
            const quantity = ticketQuantity.value || '0';
            let price = ticketPrice.value || '0';
            const description = ticketDescription.value || 'Aucune description';

            // Check if free ticket is selected
            if (freeTicketCheckbox && freeTicketCheckbox.checked) {
                price = 'Gratuit';
                // Make sure the price value is 0 for database storage
                ticketPrice.value = '0';
            } else {
                // Remove any non-numeric characters except decimal point
                price = price.replace(/[^\d.]/g, '');
                // Ensure it's a valid number
                if (isNaN(parseFloat(price))) {
                    price = '0';
                }
            }

            // Only save if there's at least a name
            if (name !== 'Nom du ticket') {
                // Add to saved tickets
                savedTickets.push({
                    name,
                    quantity,
                    price,
                    description
                });

                // Update hidden field with JSON data
                const ticketsDataField = document.getElementById('tickets-data');
                if (ticketsDataField) {
                    ticketsDataField.value = JSON.stringify(savedTickets);
                }

                // Render all saved tickets
                renderSavedTickets();
            }

            // Reset the form fields
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
            if (ticketPreview) {
                ticketPreview.innerHTML = `
                    <div class="ticket-preview-empty">
                        Les détails du ticket apparaîtront ici
                    </div>
                `;
            }

            // Focus on the first field
            ticketName.focus();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            // Validation de l'étape 1
            const eventTitle = document.getElementById('event-title');
            const eventDescription = document.getElementById('event-description');
            const eventLocation = document.getElementById('event-location');
            const eventDateDebut = document.getElementById('event-date-debut');
            const eventDateFin = document.getElementById('event-date-fin');
            const idVille = document.getElementById('idVille');
            const idCategorieEvenement = document.getElementById('idCategorieEvenement');

            let isValid = true;

            if (!eventTitle.value) {
                eventTitle.classList.add('error');
                isValid = false;
            } else {
                eventTitle.classList.remove('error');
            }

            if (!eventDescription.value) {
                eventDescription.classList.add('error');
                isValid = false;
            } else {
                eventDescription.classList.remove('error');
            }

            if (!eventLocation.value) {
                eventLocation.classList.add('error');
                isValid = false;
            } else {
                eventLocation.classList.remove('error');
            }

            if (!eventDateDebut.value) {
                eventDateDebut.classList.add('error');
                isValid = false;
            } else {
                eventDateDebut.classList.remove('error');
            }

            if (!eventDateFin.value) {
                eventDateFin.classList.add('error');
                isValid = false;
            } else {
                eventDateFin.classList.remove('error');
            }

            if (!idVille.value) {
                idVille.classList.add('error');
                isValid = false;
            } else {
                idVille.classList.remove('error');
            }

            if (!idCategorieEvenement.value) {
                idCategorieEvenement.classList.add('error');
                isValid = false;
            } else {
                idCategorieEvenement.classList.remove('error');
            }

            if (!isValid) {
                showErrorPopup('Veuillez remplir tous les champs obligatoires.');
                return;
            }

            formStep1.classList.remove('active');
            formStep2.classList.add('active');
            progressBar.style.width = '100%';
            stepCounter.textContent = 'Étape 2/2';
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            formStep2.classList.remove('active');
            formStep1.classList.add('active');
            progressBar.style.width = '50%';
            stepCounter.textContent = 'Étape 1/2';
        });
    }

    // Initialize the saved tickets display
    // Load tickets from hidden field if they exist
    const ticketsDataField = document.getElementById('tickets-data');
    if (ticketsDataField && ticketsDataField.value && ticketsDataField.value !== '[]') {
        try {
            savedTickets = JSON.parse(ticketsDataField.value);
            renderSavedTickets();

            // If there are tickets, show step 2 automatically
            if (savedTickets.length > 0 && formStep1 && formStep2 && progressBar && stepCounter) {
                formStep1.classList.remove('active');
                formStep2.classList.add('active');
                progressBar.style.width = '100%';
                stepCounter.textContent = 'Étape 2/2';
            }
        } catch (e) {
            console.error('Error parsing tickets data:', e);
        }
    } else {
        renderSavedTickets();
    }
});
