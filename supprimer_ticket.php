<?php
// Activer l'affichage des erreurs pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'base.php'; // Connexion à la base de données

// Vérifier si des tickets ont été sélectionnés dans le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tickets']) && is_array($_POST['tickets'])) {
    $ids = array_map('intval', $_POST['tickets']); // Nettoyer les IDs reçus

    if (count($ids) > 0) {
        // Préparer la requête SQL avec des placeholders dynamiques
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids)); // i = entier pour mysqli

        $stmt = $conn->prepare("DELETE FROM ticketevenement WHERE Id_TicketEvenement IN ($placeholders)");

        if ($stmt) {
            // Lier dynamiquement les paramètres
            $stmt->bind_param($types, ...$ids);

            if ($stmt->execute()) {
                header('Location: liste_tickets.php?msg=success&text=Tickets+s%C3%A9lectionn%C3%A9s+supprim%C3%A9s+avec+succ%C3%A8s');
                exit;
            } else {
                header('Location: liste_tickets.php?msg=error&text=Erreur+de+suppression+des+tickets');
                exit;
            }
        } else {
            header('Location: liste_tickets.php?msg=error&text=Erreur+préparation+de+la+requête');
            exit;
        }
    } else {
        header('Location: liste_tickets.php?msg=info&text=Aucun+ticket+à+supprimer');
        exit;
    }
} else {
    header('Location: liste_tickets.php?msg=info&text=Veuillez+sélectionner+au+moins+un+ticket');
    exit;
}
?>