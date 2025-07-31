<?php
// Fichier pour traiter les modifications et suppressions d'événements
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la base de données
require_once __DIR__ . '/../config/base.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    $_SESSION['error_message'] = 'Vous devez être connecté pour effectuer cette action.';
    header('Location: /../../index.php?page=accueil');
    exit;
}

$userId = $_SESSION['utilisateur']['id'];
$action = $_POST['action'] ?? '';

// --- Fonction de vérification de propriété de l'événement ---
function checkEventOwnership($conn, $eventId, $userId) {
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
    return $count > 0;
}

if ($action === 'modifier_evenement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- LOGIQUE DE MODIFICATION ---

    if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
        $_SESSION['error_message'] = 'ID de l\'événement non fourni.';
        header('Location: /../../index.php?page=profil');
        exit;
    }
    $eventId = (int)$_POST['event_id'];

    if (!checkEventOwnership($conn, $eventId, $userId)) {
        $_SESSION['error_message'] = 'Événement non trouvé ou non autorisé.';
        header('Location: /../../index.php?page=profil');
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
    $imagesToRemove = $_POST['remove_images'] ?? [];
    $uploadedImageFiles = [];

    // Validation des données (vous pouvez ajouter plus de validations ici)
    if (empty($titre) || empty($description) || empty($adresse) || empty($dateDebut) || empty($dateFin) || empty($salle) || $idCategorieEvenement <= 0) {
        $_SESSION['error_message'] = 'Veuillez remplir tous les champs obligatoires.';
        header('Location: /../../index.php?page=profil');
        exit;
    }

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
        $stmt_update->bind_param("ssssssiddi", $titre, $description, $adresse, $dateDebutFormatted, $dateFinFormatted, $salle, $idCategorieEvenement, $latitude, $longitude, $eventId);
        if (!$stmt_update->execute()) throw new Exception("Erreur lors de la mise à jour de l'événement.");
        $stmt_update->close();

        // Traitement des tickets
        if (isset($_POST['tickets'])) {
            $tickets = json_decode($_POST['tickets'], true);
            if (is_array($tickets)) {
                $stmt_delete_tickets = $conn->prepare("DELETE FROM ticketevenement WHERE Id_Evenement = ?");
                $stmt_delete_tickets->bind_param("i", $eventId);
                if (!$stmt_delete_tickets->execute()) throw new Exception("Erreur lors de la suppression des tickets existants.");
                $stmt_delete_tickets->close();

                if (!empty($tickets)) {
                    $stmt_ticket = $conn->prepare("INSERT INTO ticketevenement (Titre, Description, Prix, NombreDisponible, Id_Evenement) VALUES (?, ?, ?, ?, ?)");
                    foreach ($tickets as $ticket) {
                        $ticketPrix = ($ticket['price'] === 'Gratuit' || empty($ticket['price'])) ? 0.00 : floatval($ticket['price']);
                        $stmt_ticket->bind_param("ssdii", $ticket['name'], $ticket['description'], $ticketPrix, $ticket['quantity'], $eventId);
                        if (!$stmt_ticket->execute()) throw new Exception("Erreur lors de l'insertion d'un ticket.");
                    }
                    $stmt_ticket->close();
                }
            }
        }

        // Suppression des images sélectionnées
        if (!empty($imagesToRemove)) {
            $placeholders = str_repeat('?,', count($imagesToRemove) - 1) . '?';
            $stmt_get_images = $conn->prepare("SELECT Lien FROM imageevenement WHERE Id_ImageEvenement IN ($placeholders)");
            $stmt_get_images->bind_param(str_repeat('i', count($imagesToRemove)), ...$imagesToRemove);
            $stmt_get_images->execute();
            $result = $stmt_get_images->get_result();
            while ($row = $result->fetch_assoc()) {
                if (file_exists(__DIR__ . '/../' . $row['Lien'])) unlink(__DIR__ . '/../' . $row['Lien']);
            }
            $stmt_get_images->close();
            $stmt_delete_images = $conn->prepare("DELETE FROM imageevenement WHERE Id_ImageEvenement IN ($placeholders)");
            $stmt_delete_images->bind_param(str_repeat('i', count($imagesToRemove)), ...$imagesToRemove);
            if (!$stmt_delete_images->execute()) throw new Exception("Erreur lors de la suppression des images de la base de données.");
            $stmt_delete_images->close();
        }

        // Traitement des nouvelles images
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $targetDir = __DIR__ . "/../uploads/photos_event/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

            foreach ($_FILES['images']['name'] as $i => $fileName) {
                $fileTmpName = $_FILES['images']['tmp_name'][$i];
                $newFileName = uniqid('event_', true) . '.' . strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $targetFilePath = $targetDir . $newFileName;
                if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                    $dbPath = "uploads/photos_event/" . $newFileName;
                    $stmt_img = $conn->prepare("INSERT INTO imageevenement (Lien, Id_Evenement) VALUES (?, ?)");
                    $stmt_img->bind_param("si", $dbPath, $eventId);
                    if (!$stmt_img->execute()) throw new Exception("Erreur lors de l'insertion de l'image en base de données.");
                    $stmt_img->close();
                }
            }
        }

        $conn->commit();
        $_SESSION['success_message'] = 'Événement mis à jour avec succès !';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Une erreur est survenue : ' . $e->getMessage();
    }
    header('Location: /../../index.php?page=profil&tab=' . ($_POST['active_tab'] ?? 'active'));
    exit;

} elseif ($action === 'delete_evenement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- LOGIQUE DE SUPPRESSION ---

    if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
        $_SESSION['error_message'] = 'ID de l\'événement non fourni.';
        header('Location: /../../index.php?page=profil');
        exit;
    }
    $eventId = (int)$_POST['event_id'];

    if (!checkEventOwnership($conn, $eventId, $userId)) {
        $_SESSION['error_message'] = 'Événement non trouvé ou non autorisé.';
        header('Location: /../../index.php?page=profil');
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Supprimer les achats liés
        $stmt_delete_achats = $conn->prepare("DELETE a FROM achat a JOIN ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement WHERE te.Id_Evenement = ?");
        $stmt_delete_achats->bind_param("i", $eventId);
        $stmt_delete_achats->execute();
        $stmt_delete_achats->close();

        // 2. Supprimer les tickets
        $stmt_delete_tickets = $conn->prepare("DELETE FROM ticketevenement WHERE Id_Evenement = ?");
        $stmt_delete_tickets->bind_param("i", $eventId);
        $stmt_delete_tickets->execute();
        $stmt_delete_tickets->close();

        // 3. Supprimer les images (fichiers et DB)
        $stmt_get_images = $conn->prepare("SELECT Lien FROM imageevenement WHERE Id_Evenement = ?");
        $stmt_get_images->bind_param("i", $eventId);
        $stmt_get_images->execute();
        $result_images = $stmt_get_images->get_result();
        while ($row = $result_images->fetch_assoc()) {
            $full_path = __DIR__ . '/../' . $row['Lien'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
        $stmt_get_images->close();
        $stmt_delete_images = $conn->prepare("DELETE FROM imageevenement WHERE Id_Evenement = ?");
        $stmt_delete_images->bind_param("i", $eventId);
        $stmt_delete_images->execute();
        $stmt_delete_images->close();

        // 4. Supprimer le lien de création
        $stmt_delete_creer = $conn->prepare("DELETE FROM creer WHERE Id_Evenement = ?");
        $stmt_delete_creer->bind_param("i", $eventId);
        $stmt_delete_creer->execute();
        $stmt_delete_creer->close();

        // 5. Supprimer l'événement
        $stmt_delete_event = $conn->prepare("DELETE FROM evenement WHERE Id_Evenement = ?");
        $stmt_delete_event->bind_param("i", $eventId);
        $stmt_delete_event->execute();
        $stmt_delete_event->close();

        $conn->commit();
        $_SESSION['success_message'] = 'Événement supprimé avec succès.';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Erreur lors de la suppression de l\'événement: ' . $e->getMessage();
    }

    header('Location: /../../index.php?page=profil');
    exit;

} else {
    $_SESSION['error_message'] = 'Action non valide.';
    header('Location: /../../index.php?page=profil');
    exit;
}
