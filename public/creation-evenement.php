<?php
/**
 * Script de création d'événement avec tickets
 * Corrigé pour la gestion des en-têtes et la logique de redirection
 */

// La connexion $conn est supposée être disponible via l'inclusion depuis index.php
if (!isset($conn)) {
    require_once __DIR__ . '/config/base.php';
}

$evenementId = null;

// Récupération des catégories d'événements
$categories = [];
try {
    $stmt_categories = $conn->prepare("SELECT Id_CategorieEvenement, Libelle FROM categorieevenement ORDER BY Libelle ASC");
    $stmt_categories->execute();
    $result_categories = $stmt_categories->get_result();
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt_categories->close();
} catch (Exception $e) {
    error_log("Erreur récupération catégories: " . $e->getMessage());
    $categories = [];
}

// Récupération des villes
$villes = [];
try {
    $stmt_villes = $conn->prepare("SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle ASC");
    $stmt_villes->execute();
    $result_villes = $stmt_villes->get_result();
    while ($row = $result_villes->fetch_assoc()) {
        $villes[] = $row;
    }
    $stmt_villes->close();
} catch (Exception $e) {
    error_log("Erreur récupération villes: " . $e->getMessage());
    $villes = [];
}

// Vérifie si l'utilisateur est connecté
$loggedInUserId = $_SESSION['utilisateur']['id'] ?? null;
if (!$loggedInUserId) {
    $_SESSION['error_message'] = 'Vous devez être connecté pour créer un événement.';
    header('Location: index.php?page=accueil');
    exit;
}

// Traitement du formulaire (uniquement pour les requêtes POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['creer-evenement-ticket'])) {

    // Récupération et nettoyage des données
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $dateDebut = $_POST['dateDebut'] ?? '';
    $dateFin = $_POST['dateFin'] ?? '';
    $idVille = (int)($_POST['idVille'] ?? 0);
    $idCategorieEvenement = (int)($_POST['idCategorieEvenement'] ?? 0);

    // Validation des dates
    $errors = [];

    // Vérifier si les dates sont vides
    if (empty($dateDebut)) {
        $errors[] = "La date de début est requise";
    }

    if (empty($dateFin)) {
        $errors[] = "La date de fin est requise";
    }

    // Vérifier le format des dates
    if (!empty($dateDebut) && !strtotime($dateDebut)) {
        $errors[] = "Le format de la date de début est invalide";
    }

    if (!empty($dateFin) && !strtotime($dateFin)) {
        $errors[] = "Le format de la date de fin est invalide";
    }

    // Vérifier que la date de fin est après la date de début
    if (!empty($dateDebut) && !empty($dateFin) && strtotime($dateDebut) && strtotime($dateFin)) {
        $dtDebut = new DateTime($dateDebut);
        $dtFin = new DateTime($dateFin);

        if ($dtFin <= $dtDebut) {
            $errors[] = "La date de fin doit être postérieure à la date de début";
        }

        // Vérifier que les dates ne sont pas dans le passé
        $now = new DateTime();
        if ($dtDebut < $now) {
            $errors[] = "La date de début ne peut pas être dans le passé";
        }
    }

    // Si des erreurs sont détectées, afficher un message et arrêter le traitement
    if (!empty($errors)) {
        // Stocker les erreurs dans la session pour l'affichage
        $_SESSION['form_errors'] = $errors;
        header('Location: index.php?page=creation-evenement');
        exit;
    }

    // Récupération des tickets (JSON depuis le champ caché)
    $ticketsJson = $_POST['tickets'] ?? '[]';
    $tickets = json_decode($ticketsJson, true);
    if (!is_array($tickets)) {
        $tickets = [];
    }

    // Traitement des images
    $uploadedImageFiles = [];
    $targetDir = "uploads/photos_event/";

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            error_log("Impossible de créer le dossier de destination des images.");
            $_SESSION['error_message'] = "Un problème technique est survenu lors de la création du dossier.";
            header('Location: index.php?page=creation-evenement');
            exit;
        }
    }

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        foreach ($_FILES['images']['name'] as $i => $fileName) {
            if (empty($fileName)) continue;

            $fileTmpName = $_FILES['images']['tmp_name'][$i];
            $fileSize = $_FILES['images']['size'][$i];
            $fileError = $_FILES['images']['error'][$i];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileError !== 0 || !in_array($fileType, $allowedTypes) || $fileSize > $maxFileSize) {
                $_SESSION['error_message'] = "Erreur avec le fichier '$fileName'. Vérifiez le type (jpg, png, gif) et la taille (max 5MB).";
                header('Location: index.php?page=creation-evenement');
                exit;
            }

            $newFileName = uniqid('event_', true) . '.' . $fileType;
            $targetFilePath = $targetDir . $newFileName;

            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                $uploadedImageFiles[] = $targetFilePath;
            } else {
                error_log("Échec du téléchargement de $fileName.");
                $_SESSION['error_message'] = "Échec du téléchargement de l'image '$fileName'.";
                header('Location: index.php?page=creation-evenement');
                exit;
            }
        }
    }

    $conn->begin_transaction();

    try {
        $dateDebutFormatted = date('Y-m-d H:i:s', strtotime($dateDebut));
        $dateFinFormatted = date('Y-m-d H:i:s', strtotime($dateFin));

        // 1. Insertion de l'événement
        $stmt_event = $conn->prepare("INSERT INTO evenement (Titre, Description, Adresse, DateDebut, DateFin, Id_Ville, Id_CategorieEvenement, statut_approbation) VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')");
        $stmt_event->bind_param("sssssii", $titre, $description, $adresse, $dateDebutFormatted, $dateFinFormatted, $idVille, $idCategorieEvenement);
        if (!$stmt_event->execute()) throw new Exception("Erreur lors de la création de l'événement: " . $stmt_event->error);
        $evenementId = $conn->insert_id;
        $stmt_event->close();

        // 2. Liaison utilisateur-événement
        $stmt_creer = $conn->prepare("INSERT INTO creer (Id_Utilisateur, Id_Evenement, DateCreation) VALUES (?, ?, NOW())");
        $stmt_creer->bind_param("ii", $loggedInUserId, $evenementId);
        if (!$stmt_creer->execute()) throw new Exception("Erreur lors de la liaison créateur-événement: " . $stmt_creer->error);
        $stmt_creer->close();

        // 3. Insertion des images
        if (!empty($uploadedImageFiles)) {
            $stmt_img = $conn->prepare("INSERT INTO imageevenement (Titre, Description, Lien, Id_Evenement) VALUES (?, ?, ?, ?)");
            foreach ($uploadedImageFiles as $imagePath) {
                $imgTitre = "Image pour " . $titre;
                $imgDesc = "Illustration de l'événement: " . $titre;
                $stmt_img->bind_param("sssi", $imgTitre, $imgDesc, $imagePath, $evenementId);
                if (!$stmt_img->execute()) throw new Exception("Erreur lors de l'insertion d'une image: " . $stmt_img->error);
            }
            $stmt_img->close();
        }

        // 4. Insertion des tickets
        if (!empty($tickets)) {
            $stmt_ticket = $conn->prepare("INSERT INTO ticketevenement (Titre, Description, Prix, NombreDisponible, Id_Evenement) VALUES (?, ?, ?, ?, ?)");
            foreach ($tickets as $ticket) {
                $ticketTitre = trim($ticket['name']);
                $ticketDesc = trim($ticket['description'] ?? '');
                $ticketPrix = 0.00;
                if (isset($ticket['price']) && $ticket['price'] !== 'Gratuit' && !empty($ticket['price'])) {
                    $prixStr = preg_replace('/[^\d.,]/', '', $ticket['price']);
                    $prixStr = str_replace(',', '.', $prixStr);
                    $ticketPrix = floatval($prixStr);
                }
                $ticketQuantite = (int)$ticket['quantity'];
                $stmt_ticket->bind_param("ssdii", $ticketTitre, $ticketDesc, $ticketPrix, $ticketQuantite, $evenementId);
                if (!$stmt_ticket->execute()) throw new Exception("Erreur lors de l'insertion du ticket '$ticketTitre': " . $stmt_ticket->error);
            }
            $stmt_ticket->close();
        }

        $conn->commit();
        $_SESSION['success_message'] = 'Événement créé avec succès ! Il est maintenant en attente d\'approbation.';
        header('Location: index.php?page=accueil&msg=event_created');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        foreach ($uploadedImageFiles as $file) {
            if (file_exists($file)) unlink($file);
        }
        error_log("Erreur création événement: " . $e->getMessage());
        $_SESSION['error_message'] = 'Une erreur est survenue lors de la création de l\'événement.';
        header('Location: index.php?page=creation-evenement');
        exit;
    }
}

// Si la requête n'est pas POST, le script continue et affiche le formulaire HTML ci-dessous.
// Le bloc 'else' qui causait une redirection erronée a été supprimé.
?>
<!-- Le code HTML du formulaire reste identique -->
<link rel="stylesheet" href="assets/css/form.css">
<main class="page-container" style="display: flex; justify-content: center; align-items: center">
    <form class="container-form" method="post" enctype="multipart/form-data"
          id="event-form">
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
                <input id="event-title" name="titre" type="text" placeholder=" "
                       class="form-input"
                       required/>
                <label class="form-label" for="event-title">Titre de l'événement</label>
            </div>
            <div class="form-group">
                <textarea id="event-description" name="description" placeholder=" "
                          class="form-input" rows="4"
                          required></textarea>
                <label class="form-label" for="event-description">Description</label>
            </div>
            <div class="form-group">
                <input id="event-location" name="adresse" type="text" placeholder=" "
                       class="form-input"
                       required/>
                <label class="form-label" for="event-location">Adresse</label>
            </div>
            <div class="form-group-row">
                <div class="form-group">
                    <input id="event-date-debut" name="dateDebut" type="datetime-local" placeholder=" "
                           class="form-input"
                           required/>
                    <label class="form-label" for="event-date-debut">Date et heure de début</label>
                </div>
                <div class="form-group">
                    <input id="event-date-fin" name="dateFin" type="datetime-local" placeholder=" "
                           class="form-input"
                           required/>
                    <label class="form-label" for="event-date-fin">Date et heure de fin</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Image de l'événement</label>
                <div class="image-uploader" id="imageUploader">
                    <div class="image-uploader-icon" id="imageUploaderIcon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor"
                             viewBox="0 0 256 256">
                            <path d="M208,128a80,80,0,1,1-80-80,80.09,80.09,0,0,1,80,80Z" opacity="0.2"></path>
                            <path d="M240,128a112,112,0,1,1-112-112,112,112,0,0,1,112,112Zm-48-48a24,24,0,1,0-24-24,24,24,0,0,0,24,24Zm-41.18,90.48-33.34-27.78a8,8,0,0,0-11,0l-56,46.66A8,8,0,0,0,56,200H200a8,8,0,0,0,5.18-14.48Z"></path>
                        </svg>
                    </div>
                    <div class="image-preview-container">
                        <div id="imagePreview" class="image-preview"></div>
                    </div>
                    <p class="image-uploader-text">Utilisez une image de haute qualité (JPG, PNG, GIF)</p>
                    <input type="file" id="eventImage" name="images[]" accept="image/jpeg,image/png,image/gif" multiple
                           style="display: none;">
                    <button type="button" class="btn btn-secondary" id="uploadButton">Télécharger</button>
                </div>
            </div>
            <!-- Sélection de la ville et de la catégorie -->
            <div class="form-group">
                <select id="idVille" name="idVille"
                        class="form-input" required>
                    <option value="">Sélectionnez une ville</option>
                    <?php foreach ($villes as $ville): ?>
                        <option value="<?php echo $ville['Id_Ville']; ?>">
                            <?php echo htmlspecialchars($ville['Libelle']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label" for="idVille">Ville</label>
            </div>

            <div class="form-group">
                <select id="idCategorieEvenement" name="idCategorieEvenement"
                        class="form-input"
                        required>
                    <option value="">Sélectionnez une catégorie</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?php echo $categorie['Id_CategorieEvenement']; ?>">
                            <?php echo htmlspecialchars($categorie['Libelle']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label" for="idCategorieEvenement">Catégorie</label>
            </div>

            <div class="form-actions">
                <button id="next-btn" type="button" class="btn btn-primary">Suivant</button>
            </div>
        </div>

        <!-- Étape 2: Création de ticket -->
        <div id="form-step-2" class="form-step">
            <h1 class="form-title">Configurez vos tickets</h1>
            <div class="form-group">
                <input id="ticket-name" type="text" placeholder=" " class="form-input"/>
                <label class="form-label" for="ticket-name">Nom du ticket</label>
                <!-- Champ caché pour stocker les tickets -->
                <input type="hidden" id="tickets-data" name="tickets"/>
            </div>
            <div class="form-group-row">
                <div class="form-group">
                    <input id="ticket-quantity" type="number" placeholder=" " class="form-input"/>
                    <label class="form-label" for="ticket-quantity">Quantité</label>
                </div>
                <div class="form-group">
                    <div class="price-input-container">
                        <input id="ticket-price" type="text" placeholder=" " class="form-input"/>
                        <label class="form-label" for="ticket-price">Prix</label>
                        <div class="checkbox-container inside-input">
                            <input type="checkbox" id="free-ticket" class="form-checkbox"/>
                            <label for="free-ticket" class="checkbox-label">Gratuit</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <input id="ticket-description" type="text" placeholder=" " class="form-input"/>
                <label class="form-label" for="ticket-description">Description du ticket</label>
            </div>
            <div class="form-actions-multiple">
                <button id="add-ticket-btn" type="button" class="btn btn-secondary">Ajouter un autre ticket</button>
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
            <div class="form-actions">
                <button id="prev-btn" type="button" class="btn btn-secondary">Précédent</button>
                <button type="submit" name="creer-evenement-ticket" class="btn btn-primary">Publier l'événement</button>
            </div>
        </div>
    </form>
</main>
