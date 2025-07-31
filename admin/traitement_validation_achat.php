<?php
// Script pour traiter la validation ou le rejet d'un achat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/base.php';

// 1. Sécurité : Vérifier si l'utilisateur est un admin connecté
if (!isset($_SESSION['utilisateur']) || ($_SESSION['utilisateur']['role'] !== 'admin' && $_SESSION['utilisateur']['role'] !== 'super_admin')) {
    $_SESSION['validation_message'] = "Accès non autorisé.";
    $_SESSION['validation_message_type'] = "danger";
    header('Location: index.php');
    exit;
}

// 2. Vérifier si les données du formulaire sont présentes
if (!isset($_POST['action']) || !isset($_POST['id_achat'])) {
    $_SESSION['validation_message'] = "Requête invalide.";
    $_SESSION['validation_message_type'] = "danger";
    header('Location: index.php?page=valider_achats');
    exit;
}

$action = $_POST['action'];
$id_achat = (int)$_POST['id_achat'];

// 3. Traiter l'action
if ($action === 'valider') {
    $stmt = $conn->prepare("UPDATE achat SET Statut = 'validé' WHERE Id_Achat = ?");
    $stmt->bind_param("i", $id_achat);

    if ($stmt->execute()) {
        $_SESSION['validation_message'] = "L'achat #$id_achat a été validé avec succès.";
        $_SESSION['validation_message_type'] = "success";
    } else {
        $_SESSION['validation_message'] = "Erreur lors de la validation de l'achat #$id_achat.";
        $_SESSION['validation_message_type'] = "danger";
    }
    $stmt->close();

} elseif ($action === 'rejeter') {
    $remarque = trim($_POST['remarque_de_rejet'] ?? '');

    if (empty($remarque)) {
        $_SESSION['validation_message'] = "Le motif du rejet est obligatoire.";
        $_SESSION['validation_message_type'] = "warning";
        header('Location: index.php?page=valider_achats');
        exit;
    }

    $stmt = $conn->prepare("UPDATE achat SET Statut = 'rejeté', remarque_de_rejet = ? WHERE Id_Achat = ?");
    $stmt->bind_param("si", $remarque, $id_achat);

    if ($stmt->execute()) {
        $_SESSION['validation_message'] = "L'achat #$id_achat a été rejeté.";
        $_SESSION['validation_message_type'] = "success";
    } else {
        $_SESSION['validation_message'] = "Erreur lors du rejet de l'achat #$id_achat.";
        $_SESSION['validation_message_type'] = "danger";
    }
    $stmt->close();

} else {
    $_SESSION['validation_message'] = "Action non reconnue.";
    $_SESSION['validation_message_type'] = "danger";
}

// Rediriger vers la page de validation
header('Location: index.php?page=valider_achats');
exit;
?>
