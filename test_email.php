<?php
// Script de test pour vérifier la fonctionnalité d'envoi d'emails
require_once 'config/mail.php';

// Définir l'adresse email de test
$testEmail = 'eventci2025@gmail.com'; // Remplacez par votre adresse email pour les tests

echo "=== Test de fonctionnalité d'envoi d'emails ===\n\n";

// Test 1: Email de bienvenue
echo "Test 1: Envoi d'un email de bienvenue...\n";
$success1 = sendWelcomeEmail(
    $testEmail,
    'Utilisateur Test',
    'https://eventci.com/activation?token=test123'
);

if ($success1) {
    echo "✓ Email de bienvenue envoyé avec succès!\n\n";
} else {
    echo "✗ Échec de l'envoi de l'email de bienvenue.\n\n";
}

// Test 2: Email de reçu de ticket
echo "Test 2: Envoi d'un email de reçu de ticket...\n";
$ticketData = [
    'Id_Achat' => 12345,
    'Titre_Evenement' => 'Concert de Jazz',
    'Titre_Ticket' => 'Place VIP',
    'Date_Evenement' => '15/12/2023 à 20:00',
    'Lieu' => 'Salle de concert, Paris',
    'Prix' => 45.00,
    'DateAchat' => '2023-11-25 14:30:00',
    'QRCode' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
];

$success2 = sendTicketReceiptEmail(
    $testEmail,
    'Utilisateur Test',
    $ticketData,
    'https://eventci.com/ticket?id=12345'
);

if ($success2) {
    echo "✓ Email de reçu de ticket envoyé avec succès!\n\n";
} else {
    echo "✗ Échec de l'envoi de l'email de reçu de ticket.\n\n";
}

// Résumé
if ($success1 && $success2) {
    echo "=== Tous les tests ont réussi! La fonctionnalité d'email est opérationnelle. ===\n";
} else {
    echo "=== Certains tests ont échoué. Veuillez vérifier la configuration de votre email. ===\n";
}
?>