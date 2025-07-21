<?php
// Fichier pour récupérer les tickets d'un événement via AJAX
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si l'ID de l'événement est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de l\'événement non fourni']);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../config/base.php';

$eventId = (int)$_GET['id'];
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
    echo json_encode(['success' => false, 'message' => 'Événement non trouvé ou non autorisé']);
    exit;
}

// Récupérer les tickets de l'événement
$stmt_tickets = $conn->prepare("
    SELECT Id_TicketEvenement, Titre, Description, Prix, NombreDisponible
    FROM ticketevenement
    WHERE Id_Evenement = ?
");
$stmt_tickets->bind_param("i", $eventId);
$stmt_tickets->execute();
$result_tickets = $stmt_tickets->get_result();
$tickets = [];
while ($row = $result_tickets->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt_tickets->close();

// Retourner les données au format JSON
echo json_encode(['success' => true, 'tickets' => $tickets]);