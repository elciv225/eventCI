<?php
// Inclure le fichier de gestion des notifications
require_once __DIR__ . '/../../app/utils/notifications.php';

// Traitement des actions de validation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['ticket_id'])) {
        $ticketId = (int)$_POST['ticket_id'];
        $action = $_POST['action'];
        $remarque = isset($_POST['remarque']) ? trim($_POST['remarque']) : '';
        
        // Récupérer les informations du ticket et de l'utilisateur
        $stmt = $conn->prepare("
            SELECT a.Id_Utilisateur, a.Id_Achat, u.Email, u.Prenom, u.Nom, 
                   e.Titre AS Titre_Evenement, t.Titre AS Titre_Ticket
            FROM achat a
            JOIN utilisateur u ON a.Id_Utilisateur = u.Id_Utilisateur
            JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
            JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
            WHERE a.Id_Achat = ?
        ");
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
            $userId = $ticket['Id_Utilisateur'];
            $userEmail = $ticket['Email'];
            $userName = $ticket['Prenom'] . ' ' . $ticket['Nom'];
            $eventTitle = $ticket['Titre_Evenement'];
            $ticketTitle = $ticket['Titre_Ticket'];
            
            if ($action === 'valider') {
                // Mettre à jour le statut du ticket à "validé"
                $updateStmt = $conn->prepare("UPDATE achat SET Statut = 'validé' WHERE Id_Achat = ?");
                $updateStmt->bind_param("i", $ticketId);
                $success = $updateStmt->execute();
                $updateStmt->close();
                
                if ($success) {
                    // Créer une notification pour l'utilisateur
                    $message = "Votre ticket pour l'événement \"$eventTitle\" a été validé.";
                    createNotification($userId, $message, 'ticket_validated', $ticketId);
                    
                    // Afficher un message de succès
                    echo '<div class="success-message">Le ticket a été validé avec succès.</div>';
                }
            } elseif ($action === 'rejeter') {
                // Mettre à jour le statut du ticket à "rejeté" et enregistrer la remarque
                $updateStmt = $conn->prepare("UPDATE achat SET Statut = 'rejeté', RemarqueRejet = ? WHERE Id_Achat = ?");
                $updateStmt->bind_param("si", $remarque, $ticketId);
                $success = $updateStmt->execute();
                $updateStmt->close();
                
                if ($success) {
                    // Créer une notification pour l'utilisateur
                    $message = "Votre ticket pour l'événement \"$eventTitle\" a été rejeté.";
                    $additionalData = json_encode(['remarque' => $remarque]);
                    createNotification($userId, $message, 'ticket_rejected', $ticketId, $additionalData);
                    
                    // Afficher un message de succès
                    echo '<div class="success-message">Le ticket a été rejeté avec succès.</div>';
                }
            }
        }
        $stmt->close();
    }
}

// Récupération des tickets en attente de validation
$recherche = $_GET['recherche'] ?? '';
$pageNum = isset($_GET['pageNum']) ? max(1, intval($_GET['pageNum'])) : 1;
$limit = 10;
$start = ($pageNum - 1) * $limit;

if ($recherche) {
    $stmt = $conn->prepare("
        SELECT a.Id_Achat, a.DateAchat, a.Statut, a.QRCode, a.RemarqueRejet,
               u.Id_Utilisateur, u.Prenom, u.Nom, u.Email,
               t.Titre AS Titre_Ticket, t.Prix,
               e.Titre AS Titre_Evenement, e.DateDebut, e.Salle
        FROM achat a
        JOIN utilisateur u ON a.Id_Utilisateur = u.Id_Utilisateur
        JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
        JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
        WHERE (a.Statut = 'payé' OR a.Statut = 'validé' OR a.Statut = 'rejeté')
        AND (u.Nom LIKE ? OR u.Prenom LIKE ? OR e.Titre LIKE ? OR t.Titre LIKE ?)
        ORDER BY a.DateAchat DESC
        LIMIT ?, ?
    ");
    $searchTerm = '%' . $recherche . '%';
    $stmt->bind_param("ssssii", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt = $conn->prepare("
        SELECT a.Id_Achat, a.DateAchat, a.Statut, a.QRCode, a.RemarqueRejet,
               u.Id_Utilisateur, u.Prenom, u.Nom, u.Email,
               t.Titre AS Titre_Ticket, t.Prix,
               e.Titre AS Titre_Evenement, e.DateDebut, e.Salle
        FROM achat a
        JOIN utilisateur u ON a.Id_Utilisateur = u.Id_Utilisateur
        JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
        JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
        WHERE a.Statut = 'payé' OR a.Statut = 'validé' OR a.Statut = 'rejeté'
        ORDER BY a.DateAchat DESC
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $start, $limit);
}

$stmt->execute();
$tickets = $stmt->get_result();

// Compter le nombre total de tickets pour la pagination
if ($recherche) {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM achat a
        JOIN utilisateur u ON a.Id_Utilisateur = u.Id_Utilisateur
        JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
        JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
        WHERE (a.Statut = 'payé' OR a.Statut = 'validé' OR a.Statut = 'rejeté')
        AND (u.Nom LIKE ? OR u.Prenom LIKE ? OR e.Titre LIKE ? OR t.Titre LIKE ?)
    ");
    $countStmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
} else {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM achat a
        WHERE a.Statut = 'payé' OR a.Statut = 'validé' OR a.Statut = 'rejeté'
    ");
}

$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$countStmt->close();
?>

<h1 class="section-title"><i data-lucide="check-circle"></i> Validation des Tickets</h1>

<div class="admin-container">
    <div class="search-box">
        <form method="GET" action="">
            <input type="hidden" name="page" value="valider_tickets">
            <input type="text" name="recherche" placeholder="Rechercher par nom, événement ou ticket" value="<?= htmlspecialchars($recherche); ?>">
            <button type="submit"><i data-lucide="search"></i> Rechercher</button>
        </form>
    </div>

    <div class="tickets-validation-grid">
        <?php if (isset($tickets) && $tickets->num_rows > 0): ?>
            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                <div class="ticket-validation-card <?= $ticket['Statut'] ?>">
                    <div class="ticket-header">
                        <h3><?= htmlspecialchars($ticket['Titre_Evenement']) ?></h3>
                        <span class="ticket-status <?= $ticket['Statut'] ?>">
                            <?php 
                            switch($ticket['Statut']) {
                                case 'payé': echo 'En attente'; break;
                                case 'validé': echo 'Validé'; break;
                                case 'rejeté': echo 'Rejeté'; break;
                                default: echo $ticket['Statut']; break;
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="ticket-details">
                        <p><strong>Ticket:</strong> <?= htmlspecialchars($ticket['Titre_Ticket']) ?></p>
                        <p><strong>Prix:</strong> <?= number_format($ticket['Prix'], 0, ',', ' ') ?> FCFA</p>
                        <p><strong>Date de l'événement:</strong> <?= date('d/m/Y à H:i', strtotime($ticket['DateDebut'])) ?></p>
                        <p><strong>Lieu:</strong> <?= htmlspecialchars($ticket['Salle']) ?></p>
                        <p><strong>Acheteur:</strong> <?= htmlspecialchars($ticket['Prenom'] . ' ' . $ticket['Nom']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($ticket['Email']) ?></p>
                        <p><strong>Date d'achat:</strong> <?= date('d/m/Y à H:i', strtotime($ticket['DateAchat'])) ?></p>
                        
                        <?php if ($ticket['Statut'] === 'rejeté' && !empty($ticket['RemarqueRejet'])): ?>
                            <p class="rejection-reason"><strong>Motif du rejet:</strong> <?= htmlspecialchars($ticket['RemarqueRejet']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($ticket['Statut'] === 'payé'): ?>
                        <div class="ticket-actions">
                            <form method="POST" action="" class="validation-form">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['Id_Achat'] ?>">
                                <input type="hidden" name="action" value="valider">
                                <button type="submit" class="btn-success"><i data-lucide="check"></i> Valider</button>
                            </form>
                            
                            <button type="button" class="btn-danger reject-btn" data-ticket-id="<?= $ticket['Id_Achat'] ?>">
                                <i data-lucide="x"></i> Rejeter
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-tickets-message">
                <i data-lucide="info"></i> Aucun ticket trouvé pour votre recherche.
            </p>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=valider_tickets&pageNum=<?= $i ?>&recherche=<?= urlencode($recherche ?? '') ?>" 
                   class="pagination-link <?= ($i === $pageNum) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de rejet de ticket -->
<div class="modal" id="reject-ticket-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rejeter le ticket</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="reject-form">
                <input type="hidden" name="ticket_id" id="reject-ticket-id" value="">
                <input type="hidden" name="action" value="rejeter">
                
                <div class="form-group">
                    <label for="remarque">Motif du rejet:</label>
                    <textarea name="remarque" id="remarque" rows="4" required></textarea>
                    <p class="form-help">Veuillez indiquer la raison pour laquelle vous rejetez ce ticket. Cette information sera visible par l'utilisateur.</p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary modal-close">Annuler</button>
                    <button type="submit" class="btn-danger">Confirmer le rejet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.tickets-validation-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ticket-validation-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
}

.ticket-validation-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
}

.ticket-validation-card.validé {
    border-left: 5px solid #28a745;
}

.ticket-validation-card.rejeté {
    border-left: 5px solid #dc3545;
}

.ticket-validation-card.payé {
    border-left: 5px solid #ffc107;
}

.ticket-header {
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ticket-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
}

.ticket-status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
}

.ticket-status.validé {
    background-color: #d4edda;
    color: #155724;
}

.ticket-status.rejeté {
    background-color: #f8d7da;
    color: #721c24;
}

.ticket-status.payé {
    background-color: #fff3cd;
    color: #856404;
}

.ticket-details {
    padding: 15px;
}

.ticket-details p {
    margin: 8px 0;
    font-size: 0.9rem;
}

.rejection-reason {
    background-color: #f8d7da;
    padding: 10px;
    border-radius: 5px;
    margin-top: 15px;
    color: #721c24;
}

.ticket-actions {
    display: flex;
    padding: 15px;
    gap: 10px;
    border-top: 1px solid #e9ecef;
}

.ticket-actions form {
    flex: 1;
}

.btn-success, .btn-danger {
    width: 100%;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.no-tickets-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 30px;
    background-color: #f8f9fa;
    border-radius: 8px;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}

/* Modal styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    resize: vertical;
}

.form-help {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les icônes Lucide
    lucide.createIcons();
    
    // Gestion du modal de rejet de ticket
    const rejectModal = document.getElementById('reject-ticket-modal');
    const rejectTicketId = document.getElementById('reject-ticket-id');
    const rejectForm = document.getElementById('reject-form');
    
    // Ouvrir le modal lors du clic sur le bouton de rejet
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const ticketId = this.getAttribute('data-ticket-id');
            rejectTicketId.value = ticketId;
            rejectModal.style.display = 'block';
        });
    });
    
    // Fermer le modal
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            rejectModal.style.display = 'none';
        });
    });
    
    // Fermer le modal en cliquant à l'extérieur
    window.addEventListener('click', function(event) {
        if (event.target === rejectModal) {
            rejectModal.style.display = 'none';
        }
    });
});
</script>