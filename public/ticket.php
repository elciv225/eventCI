<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}

// Vérifier si l'ID du ticket est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Rediriger vers la page des tickets si aucun ID n'est fourni
    header('Location: ?page=mes-ticket');
    exit;
}

$ticket_id = intval($_GET['id']);
$user_id = $_SESSION['utilisateur']['id'];

// --- Récupérer les données du ticket depuis la base de données ---
$query = "SELECT
            a.Id_Achat,
            a.DateAchat,
            a.DatePaiement,
            a.QRCode,
            a.Id_Utilisateur,
            t.Titre AS Titre_Ticket,
            t.Prix,
            e.Titre AS Titre_Evenement,
            e.Description AS Description_Evenement,
            e.DateDebut AS DateDebut_Evenement,
            e.DateFin AS DateFin_Evenement,
            e.Adresse AS Adresse_Evenement,
            e.Salle AS Lieu_Evenement,
            u.Nom, u.Prenom, u.Email, u.Telephone
          FROM achat a
          JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
          JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
          JOIN utilisateur u ON a.Id_Utilisateur = u.Id_Utilisateur
          WHERE a.Id_Achat = ? AND a.Id_Utilisateur = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ticket_data = $result->fetch_assoc();

    // Vérifier si c'est un scan de ticket (paramètre scan=1)
    $is_scan = isset($_GET['scan']) && $_GET['scan'] == 1;

    // Si c'est un scan, mettre à jour le statut du ticket
    if ($is_scan && isset($_GET['operator']) && $_GET['operator'] == 'admin') {
        // Ici, on pourrait ajouter une vérification plus stricte de l'opérateur
        $scan_time = date('Y-m-d H:i:s');
        $update_query = "UPDATE achat SET DernierScan = ? WHERE Id_Achat = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $scan_time, $ticket_id);
        $stmt->execute();

        // Ajouter cette information aux données du ticket
        $ticket_data['DernierScan'] = $scan_time;
    }
} else {
    // Ticket non trouvé ou n'appartient pas à l'utilisateur
    $_SESSION['error_message'] = "Ticket non trouvé ou vous n'avez pas la permission de le voir.";
    header('Location: ?page=mes-ticket');
    exit;
}
// --- Fin de la récupération des données ---

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
            <a href="?page=mes-ticket" class="back-button"><span class="arrow">←</span> Retour à mes tickets</a>
        </div>

        <div class="ticket-container">
            <div class="ticket">
                <div class="ticket-event-info">
                    <h2 class="ticket-event-title"><?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?></h2>
                    <p class="ticket-event-date">
                        Le <?php echo $date_evenement_formatted; ?> de <?php echo $heure_debut_formatted; ?> à <?php echo $heure_fin_formatted; ?>
                    </p>
                    <p class="ticket-event-location"><?php echo htmlspecialchars($ticket_data['Lieu_Evenement']); ?>, <?php echo htmlspecialchars($ticket_data['Adresse_Evenement']); ?></p>
                    <?php if (isset($ticket_data['DernierScan'])): ?>
                    <div class="ticket-scan-info">
                        <p><strong>Ticket scanné avec succès</strong><br>Date: <?php echo (new DateTime($ticket_data['DernierScan']))->format('d/m/Y à H:i:s'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ticket-details">
                    <div class="ticket-info">
                        <div class="ticket-info-item">
                            <span class="ticket-label">Type de ticket:</span>
                            <span class="ticket-value"><?php echo htmlspecialchars($ticket_data['Titre_Ticket']); ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Prix:</span>
                            <span class="ticket-value"><?php echo number_format($ticket_data['Prix'], 0, '', ' '); ?> FCFA</span>
                        </div>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Date d'achat:</span>
                            <span class="ticket-value"><?php echo $date_achat_formatted; ?></span>
                        </div>
                        <?php if (!empty($ticket_data['DatePaiement'])): ?>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Date de paiement:</span>
                            <span class="ticket-value"><?php echo (new DateTime($ticket_data['DatePaiement']))->format($format_date); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Numéro de ticket:</span>
                            <span class="ticket-value">TICKET-<?php echo htmlspecialchars($ticket_data['Id_Achat']); ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Commandé par:</span>
                            <span class="ticket-value"><?php echo htmlspecialchars($ticket_data['Prenom'] . ' ' . $ticket_data['Nom']); ?></span>
                        </div>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Email:</span>
                            <span class="ticket-value"><?php echo htmlspecialchars($ticket_data['Email']); ?></span>
                        </div>
                        <?php if (!empty($ticket_data['Telephone'])): ?>
                        <div class="ticket-info-item">
                            <span class="ticket-label">Téléphone:</span>
                            <span class="ticket-value"><?php echo htmlspecialchars($ticket_data['Telephone']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="ticket-qr">
                        <?php if (!empty($ticket_data['QRCode'])): ?>
                            <img src="<?php echo htmlspecialchars($ticket_data['QRCode'], ENT_QUOTES, 'UTF-8'); ?>" alt="Code QR du ticket" class="qr-code">
                        <?php else: ?>
                            <div class="qr-placeholder">QR Code non disponible</div>
                        <?php endif; ?>
                        <p class="qr-instructions">Présentez ce code QR à l'entrée de l'événement</p>
                    </div>
                </div>
            </div>

            <div class="ticket-actions">
                <button class="btn-primary" onclick="window.print()">Imprimer le ticket</button>
                <a href="mailto:?subject=Mon ticket pour <?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?>&body=Voici mon ticket pour l'événement: <?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?> le <?php echo $date_evenement_formatted; ?> à <?php echo $heure_debut_formatted; ?>. Code: TICKET-<?php echo htmlspecialchars($ticket_data['Id_Achat']); ?>" class="btn-secondary">Envoyer par email</a>
            </div>
        </div>
    </section>
</main>
