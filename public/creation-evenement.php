<?php ?>
<link rel="stylesheet" href="assets/css/form.css">
<main class="page-container">
    <div class="container-form">
        <!-- Indicateur de progression -->
        <div class="progress-indicator">
            <p id="step-counter" class="step-counter">Étape 1/2</p>
            <div class="progress-bar-bg">
                <div id="progress-bar" class="progress-bar-fill" style="width: 50%;"></div>
            </div>
        </div>

        <!-- Étape 1: Création d'événement -->
        <div id="form-step-1" class="form-step active">
            <h1 class="form-title">Créer un événement</h1>
            <div class="form-group">
                <input id="event-title" type="text" placeholder=" " class="form-input" />
                <label class="form-label" for="event-title">Titre de l'événement</label>
            </div>
            <div class="form-group">
                <textarea id="event-description" placeholder=" " class="form-input" rows="4"></textarea>
                <label class="form-label" for="event-description">Description</label>
            </div>
            <div class="form-group">
                <input id="event-location" type="text" placeholder=" " class="form-input" />
                <label class="form-label" for="event-location">Lieu</label>
            </div>
            <div class="form-group-row">
                <div class="form-group">
                    <input id="event-date" type="date" placeholder=" " class="form-input" />
                    <label class="form-label" for="event-date">Date</label>
                </div>
                <div class="form-group">
                    <input id="event-time" type="time" placeholder=" " class="form-input" />
                    <label class="form-label" for="event-time">Heure</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Image de l'événement</label>
                <div class="image-uploader" id="imageUploader">
                    <div class="image-uploader-icon" id="imageUploaderIcon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 256 256"><path d="M208,128a80,80,0,1,1-80-80,80.09,80.09,0,0,1,80,80Z" opacity="0.2"></path><path d="M240,128a112,112,0,1,1-112-112,112,112,0,0,1,112,112Zm-48-48a24,24,0,1,0-24-24,24,24,0,0,0,24,24Zm-41.18,90.48-33.34-27.78a8,8,0,0,0-11,0l-56,46.66A8,8,0,0,0,56,200H200a8,8,0,0,0,5.18-14.48Z"></path></svg>
                    </div>
                    <div class="image-preview-container">
                        <div id="imagePreview" class="image-preview"></div>
                    </div>
                    <p class="image-uploader-text">Utilisez une image de haute qualité</p>
                    <input type="file" id="eventImage" name="eventImage[]" accept="image/*" multiple style="display: none;">
                    <button type="button" class="btn btn-secondary" id="uploadButton">Télécharger</button>
                </div>
            </div>
            <div class="form-actions">
                <button id="next-btn" type="button" class="btn btn-primary">Suivant</button>
            </div>
        </div>

        <!-- Étape 2: Création de ticket -->
        <div id="form-step-2" class="form-step">
            <h1 class="form-title">Configurez vos tickets</h1>
            <div class="form-group">
                <input id="ticket-name" type="text" placeholder=" " class="form-input" />
                <label class="form-label" for="ticket-name">Nom du ticket</label>
            </div>
            <div class="form-group-row">
                <div class="form-group">
                    <input id="ticket-quantity" type="number" placeholder=" " class="form-input" />
                    <label class="form-label" for="ticket-quantity">Quantité</label>
                </div>
                <div class="form-group">
                    <input id="ticket-price" type="text" placeholder=" " class="form-input" />
                    <label class="form-label" for="ticket-price">Prix</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-container">
                    <input type="checkbox" id="free-ticket" class="form-checkbox" />
                    <label for="free-ticket" class="checkbox-label">Ticket gratuit</label>
                </div>
            </div>
            <div class="form-group">
                <input id="ticket-description" type="text" placeholder=" " class="form-input" />
                <label class="form-label" for="ticket-description">Description du ticket</label>
            </div>
            <!-- Container for multiple ticket previews -->
            <div id="all-tickets-container" class="all-tickets-container">
                <h3 class="preview-title">Tickets ajoutés</h3>
                <div id="all-tickets-preview" class="all-tickets-preview">
                    <div class="tickets-preview-empty">
                        Aucun ticket ajouté
                    </div>
                </div>
            </div>
            <div class="form-actions-multiple">
                <button id="add-ticket-btn" type="button" class="btn btn-secondary">Ajouter un autre ticket</button>
            </div>
            <div class="form-actions">
                <button id="prev-btn" type="button" class="btn btn-secondary">Précédent</button>
                <button type="submit" class="btn btn-primary">Publier l'événement</button>
            </div>
        </div>
    </div>
</main>
<script src="assets/js/creation-evenement.js"></script>
