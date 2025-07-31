<?php
// Démarrer la session et inclure la base de données
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/base.php';

// 1. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    // Rediriger vers la page d'authentification si non connecté
    header('Location: ../authentification.php?error=not_logged_in');
    exit;
}

// 2. Valider l'ID de l'événement
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    // Rediriger avec une erreur si l'ID est manquant ou invalide
    header('Location: ../index.php?page=mon-profil&delete_status=invalid_id');
    exit;
}

$event_id = (int)$_GET['id'];
$user_id = $_SESSION['utilisateur']['id'];

// 3. Vérifier que l'événement appartient bien à l'utilisateur connecté (Sécurité)
$stmt_check = $conn->prepare("SELECT COUNT(*) FROM creer WHERE Id_Evenement = ? AND Id_Utilisateur = ?");
$stmt_check->bind_param("ii", $event_id, $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$owner_count = $result_check->fetch_row()[0];
$stmt_check->close();

if ($owner_count == 0) {
    // Si l'utilisateur n'est pas le propriétaire, rediriger avec une erreur
    header('Location: ../index.php?page=mon-profil&delete_status=unauthorized');
    exit;
}

// 4. Procéder à la suppression (avec une transaction pour assurer l'intégrité)
$conn->begin_transaction();

try {
    // Récupérer les IDs des tickets liés à l'événement
    $stmt_tickets = $conn->prepare("SELECT Id_TicketEvenement FROM ticketevenement WHERE Id_Evenement = ?");
    $stmt_tickets->bind_param("i", $event_id);
    $stmt_tickets->execute();
    $result_tickets = $stmt_tickets->get_result();
    $ticket_ids = [];
    while ($row = $result_tickets->fetch_assoc()) {
        $ticket_ids[] = $row['Id_TicketEvenement'];
    }
    $stmt_tickets->close();

    if (!empty($ticket_ids)) {
        // Supprimer les achats liés à ces tickets
        $ids_placeholder = implode(',', array_fill(0, count($ticket_ids), '?'));
        $stmt_delete_achats = $conn->prepare("DELETE FROM achat WHERE Id_TicketEvenement IN ($ids_placeholder)");
        // La fonction bind_param n'accepte pas un tableau, il faut le faire dynamiquement
        $types = str_repeat('i', count($ticket_ids));
        $stmt_delete_achats->bind_param($types, ...$ticket_ids);
        $stmt_delete_achats->execute();
        $stmt_delete_achats->close();

        // Supprimer les tickets eux-mêmes
        $stmt_delete_tickets = $conn->prepare("DELETE FROM ticketevenement WHERE Id_Evenement = ?");
        $stmt_delete_tickets->bind_param("i", $event_id);
        $stmt_delete_tickets->execute();
        $stmt_delete_tickets->close();
    }

    // Supprimer les images liées
    $stmt_delete_images = $conn->prepare("DELETE FROM imageevenement WHERE Id_Evenement = ?");
    $stmt_delete_images->bind_param("i", $event_id);
    $stmt_delete_images->execute();
    $stmt_delete_images->close();

    // Supprimer le lien de création
    $stmt_delete_creer = $conn->prepare("DELETE FROM creer WHERE Id_Evenement = ?");
    $stmt_delete_creer->bind_param("i", $event_id);
    $stmt_delete_creer->execute();
    $stmt_delete_creer->close();

    // Supprimer l'événement lui-même
    $stmt_delete_event = $conn->prepare("DELETE FROM evenement WHERE Id_Evenement = ?");
    $stmt_delete_event->bind_param("i", $event_id);
    $stmt_delete_event->execute();
    $stmt_delete_event->close();

    // Si tout s'est bien passé, on valide la transaction
    $conn->commit();

    // 5. Rediriger avec un message de succès
    header('Location: ../index.php?page=mon-profil&delete_status=success');
    exit;

} catch (Exception $e) {
    // En cas d'erreur, on annule la transaction
    $conn->rollback();

    // On peut logguer l'erreur pour le débogage
    error_log("Erreur lors de la suppression de l'événement $event_id: " . $e->getMessage());

    // 5. Rediriger avec un message d'erreur
    header('Location: ../index.php?page=mon-profil&delete_status=error');
    exit;
}
?>
