<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    require_once __DIR__ . '/../config/base.php';
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    header('Location: authentification.php');
    exit;
}

// Vérifier si l'ID de l'achat est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ?page=panier');
    exit;
}

$achat_id = intval($_GET['id']);
$user_id = $_SESSION['utilisateur']['id'];

// Récupérer les données du ticket depuis la base de données
$query = "SELECT
            a.Id_Achat,
            a.DateAchat,
            a.QRCode,
            t.Titre AS Titre_Ticket,
            t.Prix,
            e.Titre AS Titre_Evenement,
            e.DateDebut AS DateDebut_Evenement,
            e.DateFin AS DateFin_Evenement,
            e.Adresse AS Adresse_Evenement
          FROM achat a
          JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
          JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
          WHERE a.Id_Achat = ? AND a.Id_Utilisateur = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $achat_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket_data = $result->fetch_assoc();

// Si aucun ticket n'est trouvé ou n'appartient pas à l'utilisateur, afficher une erreur
if (!$ticket_data) {
    // Vous pouvez afficher un message d'erreur plus élaboré ici
    echo "<main class='page-container'><p>Ticket non trouvé ou accès non autorisé.</p></main>";
    exit;
}

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

// Le QR code est déjà en base64
$qr_code_src = $ticket_data['QRCode'];
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
                            <span class="ticket-value">TICKET-<?php echo htmlspecialchars($ticket_data['Id_Achat']); ?></span>
                        </div>
                    </div>

                    <div class="ticket-qr">
                        <?php if (!empty($qr_code_src)): ?>
                            <img src="<?php echo $qr_code_src; ?>" alt="Code QR du ticket" class="qr-code">
                            <p class="qr-instructions">Présentez ce code QR à l'entrée de l'événement</p>
                        <?php else: ?>
                            <p class="qr-instructions">QR Code non disponible.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="ticket-actions">
                <button class="btn-primary" onclick="window.print()">Imprimer le ticket</button>
                <a href="mailto:?subject=Mon ticket pour <?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?>&body=Voici mon ticket pour l'événement: <?php echo htmlspecialchars($ticket_data['Titre_Evenement']); ?> le <?php echo $date_evenement_formatted; ?> à <?php echo $heure_debut_formatted; ?>. Numéro de ticket: TICKET-<?php echo htmlspecialchars($ticket_data['Id_Achat']); ?>" class="btn-secondary">Envoyer par email</a>
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
