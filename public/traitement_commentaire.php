<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/base.php';

// 1. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour laisser un avis.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// 2. Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['event_id'], $_POST['rating'])) {
    $_SESSION['error_message'] = "Requête invalide.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

$user_id = $_SESSION['utilisateur']['id'];
$event_id = intval($_POST['event_id']);
$rating = intval($_POST['rating']);
$comment = trim($_POST['comment'] ?? '');

// 3. Valider les données
$errors = [];
if ($event_id <= 0) {
    $errors[] = "ID d'événement non valide.";
}
if ($rating < 1 || $rating > 5) {
    $errors[] = "La note doit être entre 1 et 5.";
}
if (empty($comment)) {
    $errors[] = "Le commentaire ne peut pas être vide.";
}
if (strlen($comment) > 1000) { // Limite de caractères
    $errors[] = "Le commentaire est trop long (1000 caractères maximum).";
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    header('Location: index.php?page=details&id=' . $event_id);
    exit;
}

$conn->begin_transaction();
try {
    // 4. Vérifier si l'événement est terminé
    $stmt_event = $conn->prepare("SELECT DateFin FROM evenement WHERE Id_Evenement = ?");
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();
    $event = $result_event->fetch_assoc();

    if (!$event || new DateTime($event['DateFin']) >= new DateTime()) {
        throw new Exception("Vous ne pouvez laisser un avis que sur les événements terminés.");
    }

    // 5. Vérifier si l'utilisateur a un ticket validé pour cet événement
    $stmt_ticket = $conn->prepare("
        SELECT COUNT(*) as count
        FROM achat a
        JOIN ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement
        WHERE a.Id_Utilisateur = ? AND te.Id_Evenement = ? AND a.Statut = 'validé'
    ");
    $stmt_ticket->bind_param("ii", $user_id, $event_id);
    $stmt_ticket->execute();
    $ticket_count = $stmt_ticket->get_result()->fetch_assoc()['count'];

    if ($ticket_count == 0) {
        throw new Exception("Vous devez avoir un ticket validé pour cet événement pour laisser un avis.");
    }

    // 6. Insérer le commentaire
    $stmt_comment = $conn->prepare("INSERT INTO commentaireevenement (Id_Utilisateur, Id_Evenement, Contenu, DateCommentaire) VALUES (?, ?, ?, NOW())");
    $stmt_comment->bind_param("iis", $user_id, $event_id, $comment);
    if (!$stmt_comment->execute()) {
        // Ignorer l'erreur si l'utilisateur a déjà commenté (contrainte unique)
        if ($conn->errno !== 1062) { // 1062 = Duplicate entry
             throw new Exception("Erreur lors de l'ajout du commentaire.");
        }
    }

    // 7. Insérer ou mettre à jour la note
    $stmt_rating = $conn->prepare("
        INSERT INTO noteevenement (Id_Utilisateur, Id_Evenement, Note, DateNote)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE Note = ?, DateNote = NOW()
    ");
    $stmt_rating->bind_param("iiis", $user_id, $event_id, $rating, $rating);
    if (!$stmt_rating->execute()) {
        throw new Exception("Erreur lors de l'ajout de la note.");
    }

    $conn->commit();
    $_SESSION['success_message'] = "Merci ! Votre avis a été publié.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

// Rediriger vers la page de l'événement
header('Location: index.php?page=details&id=' . $event_id);
exit;
?>
