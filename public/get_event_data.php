<?php
// Fichier pour récupérer les données d'un événement via AJAX
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

// Récupérer les données de l'événement
$stmt_event = $conn->prepare("
    SELECT 
        e.Id_Evenement, e.Titre, e.Description, e.Adresse, 
        e.DateDebut, e.DateFin, e.Id_Ville, e.Id_CategorieEvenement,
        v.Libelle as ville_nom,
        c.Libelle as categorie_nom
    FROM evenement e
    LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
    LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
    WHERE e.Id_Evenement = ?
");
$stmt_event->bind_param("i", $eventId);
$stmt_event->execute();
$result_event = $stmt_event->get_result();
$event = $result_event->fetch_assoc();
$stmt_event->close();

if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Événement non trouvé']);
    exit;
}

// Récupérer les images de l'événement
$stmt_images = $conn->prepare("
    SELECT Id_ImageEvenement, Titre, Description, Lien
    FROM imageevenement
    WHERE Id_Evenement = ?
");
$stmt_images->bind_param("i", $eventId);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
$images = [];
while ($row = $result_images->fetch_assoc()) {
    $images[] = $row;
}
$stmt_images->close();

// Ajouter les images à l'événement
$event['images'] = $images;

// Retourner les données au format JSON
echo json_encode(['success' => true, 'event' => $event]);