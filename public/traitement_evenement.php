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
            // D'abord, supprimer tous les tickets existants pour cet événement
            $stmt_delete_tickets = $conn->prepare("DELETE FROM ticketevenement WHERE Id_Evenement = ?");
            $stmt_delete_tickets->bind_param("i", $eventId);
            if (!$stmt_delete_tickets->execute()) {
                throw new Exception("Erreur lors de la suppression des tickets existants: " . $stmt_delete_tickets->error);
            }
            $stmt_delete_tickets->close();

            // Ensuite, insérer les nouveaux tickets
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
                $stmt_ticket->bind_param("ssdii", $ticketTitre, $ticketDesc, $ticketPrix, $ticketQuantite, $eventId);
                if (!$stmt_ticket->execute()) {
                    throw new Exception("Erreur lors de l'insertion du ticket '$ticketTitre': " . $stmt_ticket->error);
                }
            }
            $stmt_ticket->close();
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
