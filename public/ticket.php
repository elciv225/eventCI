<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    // Rediriger vers la page d'authentification si non connecté
    header('Location: authentification.php');
    exit;
}

// Vérifier si l'ID du ticket est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Rediriger vers la page panier si aucun ID n'est fourni
    header('Location: ?page=panier');
    exit;
}

$ticket_id = intval($_GET['id']);

// Pour les besoins de la démo, utilisons des données statiques
// Dans une implémentation réelle, on récupérerait les données depuis la base de données
$ticket_data = [
    'Id_Achat' => $ticket_id,
    'Id_TicketEvenement' => 1,
    'Titre_Ticket' => 'Place Standard',
    'Prix' => 25.00,
    'DateAchat' => '2023-11-20 14:30:00',
    'Titre_Evenement' => 'Concert de musique classique',
    'Description_Evenement' => 'Un magnifique concert de musique classique avec les plus grands compositeurs.',
    'DateDebut_Evenement' => '2023-12-15 19:30:00',
    'DateFin_Evenement' => '2023-12-15 22:00:00',
    'Adresse_Evenement' => '123 Avenue de la Musique, 75001 Paris',
    'Lieu_Evenement' => 'Paris',
    'Code_QR' => 'TICKET-' . $ticket_id . '-' . rand(10000, 99999)
];

// Générer un code QR (simulé pour la démo)
$qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($ticket_data['Code_QR']);

// Formater les dates
$date_achat = new DateTime($ticket_data['DateAchat']);
$date_debut = new DateTime($ticket_data['DateDebut_Evenement']);
$date_fin = new DateTime($ticket_data['DateFin_Evenement']);

$format_date = 'd/m/Y';
$format_time = 'H:i';
$date_achat_formatted = $date_achat->format($format_date);
$date_evenement_formatted = $date_debut->format($format_date);
$heure_debut_formatted = $date_debut->format($format_time);
$heure_fin_formatted = $date_fin->format($format_time);
?>

<main class="page-container">
    <section class="ticket-section">
        <div class="ticket-header">
            <h1 class="section-title">Votre Ticket</h1>
            <a href="?page=panier" class="btn-secondary">Retour au panier</a>
        </div>

        <div class="ticket-container">
            <div class="ticket">
                <div class="ticket-event-info">
                    <h2 class="ticket-event-title"><?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?></h2>
                    <p class="ticket-event-date">
                        Le <?php echo $date_evenement_formatted; ?> de <?php echo $heure_debut_formatted; ?> à <?php echo $heure_fin_formatted; ?>
                    </p>
                    <p class="ticket-event-location"><?php echo htmlspecialchars($ticket_data['Adresse_Evenement']); ?></p>
                </div>

                <div class="ticket-details">
                    <div class="ticket-info">
                        <div class="ticket-info-item">
                            <span class="ticket-label">Type de ticket:</span>
                            <span class="ticket-value"><?php echo htmlspecialchars($ticket_data['Titre_Ticket']); ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Prix:</span>
                            <span class="ticket-value"><?php echo number_format($ticket_data['Prix'], 2, ',', ' '); ?> €</span>
                        </div>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Date d'achat:</span>
                            <span class="ticket-value"><?php echo $date_achat_formatted; ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Numéro de ticket:</span>
                            <span class="ticket-value"><?php echo $ticket_data['Code_QR']; ?></span>
                        </div>
                    </div>

                    <div class="ticket-qr">
                        <img src="<?php echo $qr_code_url; ?>" alt="Code QR du ticket" class="qr-code">
                        <p class="qr-instructions">Présentez ce code QR à l'entrée de l'événement</p>
                    </div>
                </div>
            </div>

            <div class="ticket-actions">
                <button class="btn-primary" onclick="window.print()">Imprimer le ticket</button>
                <a href="mailto:?subject=Mon ticket pour <?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?>&body=Voici mon ticket pour l'événement: <?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?> le <?php echo $date_evenement_formatted; ?> à <?php echo $heure_debut_formatted; ?>. Code: <?php echo $ticket_data['Code_QR']; ?>" class="btn-secondary">Envoyer par email</a>
            </div>
        </div>
    </section>
</main>

<style>
    @media print {
        header, footer, .ticket-header, .ticket-actions {
            display: none !important;
        }

        body, .page-container, .ticket-section, .ticket-container {
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: white;
        }

        .ticket {
            box-shadow: none;
            border: 1px solid #ccc;
            page-break-inside: avoid;
        }
    }
</style>
