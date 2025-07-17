<?php
require_once 'config/base.php';

$message = '';
$errors = [];
$evenementId = null;

// Vérifie si l'utilisateur est connecté
$loggedInUserId = $_SESSION['utilisateur']['id'] ?? null;
if (!$loggedInUserId) {
    // Store error in session and redirect
    $_SESSION['error_message'] = 'Vous devez être connecté pour créer un événement.';
    header('Location: ?page=accueil');
    exit;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['creer-evenement-ticket'])) {
    // Récupération des données de l'événement
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');

    // Combiner date et heure
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $dateDebut = $date . ' ' . $time . ':00';

    // Pour simplifier, on fixe la date de fin à 3 heures après le début
    $dateFin = date('Y-m-d H:i:s', strtotime($dateDebut . ' +3 hours'));

    $idVille = $_POST['idVille'] ?? '';
    $idCategorieEvenement = $_POST['idCategorieEvenement'] ?? '';

    // Validation des champs
    if (!$titre) $errors['titre'] = "Le titre est requis.";
    if (!$description) $errors['description'] = "La description est requise.";
    if (!$adresse) $errors['adresse'] = "L'adresse est requise.";
    if (!$date) $errors['date'] = "La date est requise.";
    if (!$time) $errors['time'] = "L'heure est requise.";
    if (!$idVille) $errors['idVille'] = "La ville est requise.";
    if (!$idCategorieEvenement) $errors['idCategorieEvenement'] = "La catégorie est requise.";

    // Récupération des tickets
    $tickets = json_decode($_POST['tickets'] ?? '[]', true);
    if (empty($tickets)) {
        $errors['tickets'] = "Au moins un ticket est requis.";
    }

    // Upload des images
    $uploadedImageFiles = [];
    $targetDir = "image/";

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        $errors['images'][] = "Impossible de créer le dossier image/.";
    }

    if (empty($errors['images']) && isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
        $maxFileSize = 5 * 1024 * 1024;

        foreach ($_FILES['images']['name'] as $i => $fileName) {
            if (empty($fileName)) continue;

            $fileTmpName = $_FILES['images']['tmp_name'][$i];
            $fileSize = $_FILES['images']['size'][$i];
            $fileError = $_FILES['images']['error'][$i];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileError === 0 && in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
                $newFileName = uniqid() . '_' . basename($fileName);
                $targetFilePath = $targetDir . $newFileName;

                if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                    $uploadedImageFiles[] = $targetFilePath;
                } else {
                    $errors['images'][] = "Échec du téléchargement de $fileName.";
                }
            }
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Insère l'événement avec statut 'en_attente'
            $statut = 'en_attente';
            $stmt = $conn->prepare("INSERT INTO evenement (Titre, Description, Adresse, DateDebut, DateFin, Id_Ville, Id_CategorieEvenement, statut_approbation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssis", $titre, $description, $adresse, $dateDebut, $dateFin, $idVille, $idCategorieEvenement, $statut);
            if (!$stmt->execute()) throw new Exception("Erreur événement : " . $stmt->error);
            $evenementId = $conn->insert_id;
            $stmt->close();

            // Liaison utilisateur → événement
            $stmt_creer = $conn->prepare("INSERT INTO creer (Id_Utilisateur, Id_Evenement, DateCreation) VALUES (?, ?, NOW())");
            $stmt_creer->bind_param("ii", $loggedInUserId, $evenementId);
            if (!$stmt_creer->execute()) throw new Exception("Erreur lien événement : " . $stmt_creer->error);
            $stmt_creer->close();

            // Insertion des images
            if ($uploadedImageFiles) {
                $stmt_img = $conn->prepare("INSERT INTO imageevenement (Titre, Description, Lien, Id_Evenement) VALUES (?, ?, ?, ?)");
                foreach ($uploadedImageFiles as $img) {
                    $imgTitre = "Image pour " . $titre;
                    $imgDesc = "Illustration de l'événement : " . $titre;
                    $stmt_img->bind_param("sssi", $imgTitre, $imgDesc, $img, $evenementId);
                    if (!$stmt_img->execute()) throw new Exception("Erreur image : " . $stmt_img->error);
                }
                $stmt_img->close();
            }

            // Insertion des tickets
            if (!empty($tickets)) {
                $stmt_ticket = $conn->prepare("INSERT INTO ticketevenement (Titre, Description, Prix, NombreDisponible, Id_Evenement) VALUES (?, ?, ?, ?, ?)");
                foreach ($tickets as $ticket) {
                    $ticketTitre = $ticket['name'];
                    $ticketDesc = $ticket['description'];
                    $ticketPrix = ($ticket['price'] === 'Gratuit') ? 0 : floatval($ticket['price']);
                    $ticketQuantite = intval($ticket['quantity']);

                    $stmt_ticket->bind_param("ssdii", $ticketTitre, $ticketDesc, $ticketPrix, $ticketQuantite, $evenementId);
                    if (!$stmt_ticket->execute()) throw new Exception("Erreur ticket : " . $stmt_ticket->error);
                }
                $stmt_ticket->close();
            }

            $conn->commit();
            $_SESSION['success_message'] = 'Événement créé avec succès!';
            header('Location: ?page=accueil&msg=created_success');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            foreach ($uploadedImageFiles as $img) if (file_exists($img)) unlink($img);
            $_SESSION['error_message'] = 'Erreur : ' . $e->getMessage();
            header('Location: ?page=creation-evenement');
            exit;
        }
    } else {
        $_SESSION['error_message'] = 'Veuillez corriger les erreurs.';
        $_SESSION['form_errors'] = $errors;
        header('Location: ?page=creation-evenement');
        exit;
    }
}

$conn->close();
?>
<link rel="stylesheet" href="assets/css/form.css">
<style>
    .error {
        border: 2px solid #ff3860 !important;
        background-color: #ffeff3 !important;
    }
    .form-input.error:focus {
        box-shadow: 0 0 0 2px rgba(255, 56, 96, 0.25) !important;
    }
</style>
<main class="page-container">
    <form class="container-form" method="post" action="?page=creation-evenement" enctype="multipart/form-data" id="event-form">
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
                <input id="event-title" name="titre" type="text" placeholder=" " class="form-input" required />
                <label class="form-label" for="event-title">Titre de l'événement</label>
            </div>
            <div class="form-group">
                <textarea id="event-description" name="description" placeholder=" " class="form-input" rows="4" required></textarea>
                <label class="form-label" for="event-description">Description</label>
            </div>
            <div class="form-group">
                <input id="event-location" name="adresse" type="text" placeholder=" " class="form-input" required />
                <label class="form-label" for="event-location">Lieu</label>
            </div>
            <div class="form-group-row">
                <div class="form-group">
                    <input id="event-date" name="date" type="date" placeholder=" " class="form-input" required />
                    <label class="form-label" for="event-date">Date</label>
                </div>
                <div class="form-group">
                    <input id="event-time" name="time" type="time" placeholder=" " class="form-input" required />
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
                    <input type="file" id="eventImage" name="images[]" accept="image/*" multiple style="display: none;">
                    <button type="button" class="btn btn-secondary" id="uploadButton">Télécharger</button>
                </div>
            </div>
            <!-- Sélection de la ville et de la catégorie -->
            <div class="form-group">
                <label class="form-label" for="idVille">Ville :</label>
                <select id="idVille" name="idVille" class="form-input" required>
                    <option value="">Sélectionnez une ville</option>
                    <option value="1">Abidjan</option>
                    <option value="5">Bouaké</option>
                    <option value="8">Korhogo</option>
                    <option value="9">Daloa</option>
                    <option value="11">San-Pédro</option>
                    <option value="14">Yamoussoukro</option>
                    <option value="16">Divo</option>
                    <option value="17">Gagnoa</option>
                    <option value="18">Soubré</option>
                    <option value="19">Man</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="idCategorieEvenement">Catégorie :</label>
                <select id="idCategorieEvenement" name="idCategorieEvenement" class="form-input" required>
                    <option value="">Sélectionnez une catégorie</option>
                    <option value="1">Concert-spectacle</option>
                    <option value="2">Sport</option>
                    <option value="3">Dîner gala</option>
                    <option value="4">Soirée party</option>
                    <option value="5">Tourisme</option>
                    <option value="6">Formation</option>
                    <option value="7">Festival</option>
                    <option value="8">Rencontre-privée</option>
                    <option value="9">Rencontre groupée</option>
                    <option value="10">Autre</option>
                </select>
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
                <!-- Champ caché pour stocker les tickets -->
                <input type="hidden" id="tickets-data" name="tickets" value="[]" />
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
                <button type="submit" name="creer-evenement-ticket" class="btn btn-primary">Publier l'événement</button>
            </div>
        </div>
    </form>
</main>
<script src="assets/js/creation-evenement.js"></script>
