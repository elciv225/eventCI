<?php
// Fichier pour traiter les modifications d'événements
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    $_SESSION['error_message'] = 'Vous devez être connecté pour modifier un événement.';
    header('Location: index.php?page=accueil');
    exit;
}

// Vérifier si le formulaire a été soumis
if (!isset($_POST['modifier_evenement'])) {
    $_SESSION['error_message'] = 'Requête invalide.';
    header('Location: index.php?page=profil');
    exit;
}

// Vérifier si l'ID de l'événement est fourni
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    $_SESSION['error_message'] = 'ID de l\'événement non fourni.';
    header('Location: index.php?page=profil');
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../config/base.php';

$eventId = (int)$_POST['event_id'];
$userId = $_SESSION['utilisateur']['id'];

// Vérifier que l'événement appartient à l'utilisateur
$stmt_check = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM evenement e 
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement 
    WHERE e.Id_Evenement = ? AND c.Id_Utilisateur = ?
");
$stmt_check->bind_param("ii", $eventId, $userId);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$count = $result_check->fetch_assoc()['count'] ?? 0;
$stmt_check->close();

if ($count === 0) {
    $_SESSION['error_message'] = 'Événement non trouvé ou non autorisé.';
    header('Location: index.php?page=profil');
    exit;
}

// Récupération et nettoyage des données
$titre = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$dateDebut = $_POST['dateDebut'] ?? '';
$dateFin = $_POST['dateFin'] ?? '';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$salle = trim($_POST['salle'] ?? '');
$idCategorieEvenement = (int)($_POST['idCategorieEvenement'] ?? 0);

// Validation des données
$errors = [];

if (empty($titre)) {
    $errors[] = "Le titre est requis";
}

if (empty($description)) {
    $errors[] = "La description est requise";
}

if (empty($adresse)) {
    $errors[] = "L'adresse est requise";
}

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
}

// Vérifier que la salle et la catégorie existent
if (empty($salle)) {
    $errors[] = "Veuillez entrer une salle valide";
}

if ($idCategorieEvenement <= 0) {
    $errors[] = "Veuillez sélectionner une catégorie valide";
}

// Si des erreurs sont détectées, afficher un message et arrêter le traitement
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header('Location: index.php?page=profil');
    exit;
}

// Traitement des images à supprimer
$imagesToRemove = $_POST['remove_images'] ?? [];

// Début de la transaction
$conn->begin_transaction();

try {
    $dateDebutFormatted = date('Y-m-d H:i:s', strtotime($dateDebut));
    $dateFinFormatted = date('Y-m-d H:i:s', strtotime($dateFin));

    // Mise à jour de l'événement
    $stmt_update = $conn->prepare("
        UPDATE evenement 
        SET Titre = ?, Description = ?, Adresse = ?, DateDebut = ?, DateFin = ?, Salle = ?, Id_CategorieEvenement = ?, Latitude = ?, Longitude = ? 
        WHERE Id_Evenement = ?
    ");
    $stmt_update->bind_param("ssssssidd", $titre, $description, $adresse, $dateDebutFormatted, $dateFinFormatted, $salle, $idCategorieEvenement, $latitude, $longitude, $eventId);
    if (!$stmt_update->execute()) {
        throw new Exception("Erreur lors de la mise à jour de l'événement: " . $stmt_update->error);
    }
    $stmt_update->close();

    // Traitement des tickets
    if (isset($_POST['tickets']) && !empty($_POST['tickets'])) {
        $tickets = json_decode($_POST['tickets'], true);

        if (is_array($tickets) && !empty($tickets)) {
            // --- Nouvelle logique de synchronisation des tickets ---

            // 1. Récupérer les tickets existants depuis la BDD
            $stmt_existing = $conn->prepare("SELECT Id_TicketEvenement, Titre, Description, Prix, NombreDisponible FROM ticketevenement WHERE Id_Evenement = ?");
            $stmt_existing->bind_param("i", $eventId);
            $stmt_existing->execute();
            $result_existing = $stmt_existing->get_result();
            $existing_tickets_map = [];
            while ($row = $result_existing->fetch_assoc()) {
                $existing_tickets_map[$row['Id_TicketEvenement']] = $row;
            }
            $stmt_existing->close();

            $submitted_ticket_ids = [];

            // 2. Parcourir les tickets soumis pour les insérer ou les mettre à jour
            $stmt_update_ticket = $conn->prepare("UPDATE ticketevenement SET Titre = ?, Description = ?, Prix = ?, NombreDisponible = ? WHERE Id_TicketEvenement = ?");
            $stmt_insert_ticket = $conn->prepare("INSERT INTO ticketevenement (Titre, Description, Prix, NombreDisponible, Id_Evenement) VALUES (?, ?, ?, ?, ?)");

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

                if (isset($ticket['id'])) {
                    // C'est un ticket existant -> UPDATE
                    $ticketId = (int)$ticket['id'];
                    $submitted_ticket_ids[] = $ticketId;

                    // Vérifier si une mise à jour est nécessaire
                    if (isset($existing_tickets_map[$ticketId])) {
                        $existing_ticket = $existing_tickets_map[$ticketId];
                        if ($existing_ticket['Titre'] !== $ticketTitre ||
                            $existing_ticket['Description'] !== $ticketDesc ||
                            (float)$existing_ticket['Prix'] !== $ticketPrix ||
                            (int)$existing_ticket['NombreDisponible'] !== $ticketQuantite) {

                            $stmt_update_ticket->bind_param("ssdii", $ticketTitre, $ticketDesc, $ticketPrix, $ticketQuantite, $ticketId);
                            if (!$stmt_update_ticket->execute()) {
                                throw new Exception("Erreur lors de la mise à jour du ticket '$ticketTitre': " . $stmt_update_ticket->error);
                            }
                        }
                    }
                } else {
                    // C'est un nouveau ticket -> INSERT
                    $stmt_insert_ticket->bind_param("ssdii", $ticketTitre, $ticketDesc, $ticketPrix, $ticketQuantite, $eventId);
                    if (!$stmt_insert_ticket->execute()) {
                        throw new Exception("Erreur lors de l'insertion du ticket '$ticketTitre': " . $stmt_insert_ticket->error);
                    }
                }
            }
            $stmt_update_ticket->close();
            $stmt_insert_ticket->close();

            // 3. Déterminer quels tickets supprimer
            $existing_ticket_ids = array_keys($existing_tickets_map);
            $tickets_to_delete_ids = array_diff($existing_ticket_ids, $submitted_ticket_ids);

            if (!empty($tickets_to_delete_ids)) {
                // Vérifier si un des tickets à supprimer a été vendu
                $placeholders_delete = str_repeat('?,', count($tickets_to_delete_ids) - 1) . '?';
                $stmt_check_sold = $conn->prepare("SELECT COUNT(*) as sold_count, t.Titre FROM achat a JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement WHERE a.Id_TicketEvenement IN ($placeholders_delete) GROUP BY t.Titre");
                $types_delete = str_repeat('i', count($tickets_to_delete_ids));
                $stmt_check_sold->bind_param($types_delete, ...$tickets_to_delete_ids);
                $stmt_check_sold->execute();
                $result_sold = $stmt_check_sold->get_result();
                while($sold_ticket = $result_sold->fetch_assoc()){
                    if($sold_ticket['sold_count'] > 0){
                         throw new Exception("Impossible de supprimer le ticket '" . htmlspecialchars($sold_ticket['Titre']) . "' car il a déjà été vendu.");
                    }
                }
                $stmt_check_sold->close();

                // Supprimer les tickets qui n'ont pas été vendus
                $stmt_delete = $conn->prepare("DELETE FROM ticketevenement WHERE Id_TicketEvenement IN ($placeholders_delete)");
                $stmt_delete->bind_param($types_delete, ...$tickets_to_delete_ids);
                if (!$stmt_delete->execute()) {
                    throw new Exception("Erreur lors de la suppression des anciens tickets: " . $stmt_delete->error);
                }
                $stmt_delete->close();
            }
        }
    }
        }
    }

    // Suppression des images sélectionnées
    if (!empty($imagesToRemove)) {
        // D'abord, récupérer les chemins des images à supprimer
        $placeholders = str_repeat('?,', count($imagesToRemove) - 1) . '?';
        $stmt_get_images = $conn->prepare("SELECT Lien FROM imageevenement WHERE Id_ImageEvenement IN ($placeholders)");
        $types = str_repeat('i', count($imagesToRemove));
        $stmt_get_images->bind_param($types, ...$imagesToRemove);
        $stmt_get_images->execute();
        $result_images = $stmt_get_images->get_result();
        $imagePaths = [];
        while ($row = $result_images->fetch_assoc()) {
            $imagePaths[] = $row['Lien'];
        }
        $stmt_get_images->close();

        // Ensuite, supprimer les entrées de la base de données
        $stmt_delete_images = $conn->prepare("DELETE FROM imageevenement WHERE Id_ImageEvenement IN ($placeholders)");
        $stmt_delete_images->bind_param($types, ...$imagesToRemove);
        if (!$stmt_delete_images->execute()) {
            throw new Exception("Erreur lors de la suppression des images: " . $stmt_delete_images->error);
        }
        $stmt_delete_images->close();

        // Enfin, supprimer les fichiers physiques
        foreach ($imagePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    // Traitement des nouvelles images
    $uploadedImageFiles = [];
    $targetDir = "../uploads/photos_event/";

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            throw new Exception("Impossible de créer le dossier de destination des images.");
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
                throw new Exception("Erreur avec le fichier '$fileName'. Vérifiez le type (jpg, png, gif) et la taille (max 5MB).");
            }

            $newFileName = uniqid('event_', true) . '.' . $fileType;
            $targetFilePath = $targetDir . $newFileName;

            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                $uploadedImageFiles[] = $targetFilePath;
            } else {
                throw new Exception("Échec du téléchargement de l'image '$fileName'.");
            }
        }

        // Insertion des nouvelles images
        if (!empty($uploadedImageFiles)) {
            $stmt_img = $conn->prepare("INSERT INTO imageevenement (Titre, Description, Lien, Id_Evenement) VALUES (?, ?, ?, ?)");
            foreach ($uploadedImageFiles as $imagePath) {
                $imgTitre = "Image pour " . $titre;
                $imgDesc = "Illustration de l'événement: " . $titre;
                $stmt_img->bind_param("sssi", $imgTitre, $imgDesc, $imagePath, $eventId);
                if (!$stmt_img->execute()) {
                    throw new Exception("Erreur lors de l'insertion d'une image: " . $stmt_img->error);
                }
            }
            $stmt_img->close();
        }
    }

    $conn->commit();
    $_SESSION['success_message'] = 'Événement mis à jour avec succès !';
    header('Location: index.php?page=profil&tab=' . (isset($_POST['active_tab']) ? $_POST['active_tab'] : 'active'));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    // Supprimer les fichiers téléchargés en cas d'erreur
    foreach ($uploadedImageFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    $_SESSION['error_message'] = 'Une erreur est survenue lors de la mise à jour de l\'événement: ' . $e->getMessage();
    header('Location: index.php?page=profil');
    exit;
}
