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

$user_id = $_SESSION['utilisateur']['id'];
$user_name = $_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom'];

// Récupérer les derniers tickets achetés
$tickets_query = "SELECT 
                    a.Id_Achat, a.DatePaiement,
                    t.Titre AS Titre_Ticket, 
                    t.Prix,
                    e.Titre AS Titre_Evenement,
                    e.DateDebut,
                    e.Salle AS Lieu_Evenement
                  FROM achat a
                  JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
                  JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
                  WHERE a.Id_Utilisateur = ? AND a.Statut = 'payé'
                  ORDER BY a.DatePaiement DESC
                  LIMIT 5";

$stmt = $conn->prepare($tickets_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$recent_tickets = [];
if ($result && $result->num_rows > 0) {
    while ($ticket = $result->fetch_assoc()) {
        $recent_tickets[] = $ticket;
    }
}
?>

<main class="page-container">
    <section class="confirmation-section">
        <div class="confirmation-container">
            <div class="confirmation-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>

            <h1 class="section-title">Commande confirmée !</h1>

            <p class="confirmation-message">
                Merci pour votre achat, <?php echo htmlspecialchars($user_name); ?>. Votre commande a été traitée avec succès.
            </p>

            <div class="confirmation-details">
                <h2>Récapitulatif de votre commande</h2>

                <?php if (empty($recent_tickets)): ?>
                    <p>Aucun ticket récent trouvé.</p>
                <?php else: ?>
                    <div class="tickets-list">
                        <?php foreach ($recent_tickets as $ticket): ?>
                            <div class="ticket-item">
                                <h3><?php echo htmlspecialchars($ticket['Titre_Evenement']); ?></h3>
                                <p>
                                    <strong>Ticket:</strong> <?php echo htmlspecialchars($ticket['Titre_Ticket']); ?><br>
                                    <strong>Date de l'événement:</strong> <?php echo date('d/m/Y à H:i', strtotime($ticket['DateDebut'])); ?><br>
                                    <strong>Lieu:</strong> <?php echo htmlspecialchars($ticket['Lieu_Evenement']); ?><br>
                                    <strong>Prix:</strong> <?php echo number_format($ticket['Prix'], 2, ',', ' '); ?> €<br>
                                    <strong>Date d'achat:</strong> <?php echo date('d/m/Y à H:i', strtotime($ticket['DatePaiement'])); ?>
                                </p>
                                <div class="ticket-actions">
                                    <a href="?page=ticket&id=<?php echo $ticket['Id_Achat']; ?>" class="btn-secondary">Voir le ticket</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <p class="confirmation-message">
                Un email de confirmation avec vos tickets a été envoyé à votre adresse email.
            </p>

            <div class="confirmation-actions">
                <a href="?page=accueil" class="back-button"><span class="arrow">←</span> Retour à l'accueil</a>
                <a href="?page=panier" class="btn-secondary">Voir mon panier</a>
            </div>
        </div>
    </section>
</main>
