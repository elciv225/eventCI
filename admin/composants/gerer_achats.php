<?php
// Gérer les Achats - admin/composants/gerer_achats.php

// Traitement des actions de validation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['achat_id'])) {
    $action = $_POST['action'];
    $achat_id = intval($_POST['achat_id']);
    $remarque = $_POST['remarque_rejet'] ?? '';

    // Récupérer l'ID de l'utilisateur et le titre de l'événement pour la notification
    $info_query = "SELECT a.Id_Utilisateur, e.Titre
                   FROM achat a
                   JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
                   JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
                   WHERE a.Id_Achat = ?";
    $stmt_info = $conn->prepare($info_query);
    $stmt_info->bind_param("i", $achat_id);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    $info = $result_info->fetch_assoc();

    if ($info) {
        $user_id = $info['Id_Utilisateur'];
        $event_title = $info['Titre'];

        $conn->begin_transaction();
        try {
            if ($action === 'valider') {
                $stmt = $conn->prepare("UPDATE achat SET Statut = 'validé' WHERE Id_Achat = ?");
                $stmt->bind_param("i", $achat_id);
                $stmt->execute();

                $message = "Bonne nouvelle ! Votre ticket pour l'événement '" . htmlspecialchars($event_title) . "' a été validé.";
                $link = "?page=ticket&id=" . $achat_id;
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, related_link) VALUES (?, ?, ?)");
                $stmt_notif->bind_param("iss", $user_id, $message, $link);
                $stmt_notif->execute();

            } elseif ($action === 'rejeter' && !empty($remarque)) {
                $stmt = $conn->prepare("UPDATE achat SET Statut = 'rejeté', remarque_rejet = ? WHERE Id_Achat = ?");
                $stmt->bind_param("si", $remarque, $achat_id);
                $stmt->execute();

                $message = "Malheureusement, votre ticket pour l'événement '" . htmlspecialchars($event_title) . "' a été rejeté. Raison : " . htmlspecialchars($remarque);
                $link = "?page=panier";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, related_link) VALUES (?, ?, ?)");
                $stmt_notif->bind_param("iss", $user_id, $message, $link);
                $stmt_notif->execute();
            }
            $conn->commit();
            $_SESSION['success_message'] = "Action effectuée avec succès.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }
    header("Location: index.php?page=gerer_achats");
    exit;
}

// Récupération des achats en attente ('payé')
$stmt = $conn->prepare("
    SELECT a.Id_Achat, a.DateAchat, u.Email, e.Titre AS Titre_Evenement, t.Titre AS Titre_Ticket, t.Prix
    FROM achat a
    JOIN utilisateur u ON a.Id_Utilisateur = u.Id_Utilisateur
    JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
    JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
    WHERE a.Statut = 'payé'
    ORDER BY a.DateAchat ASC
");
$stmt->execute();
$achats = $stmt->get_result();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<h1 class="section-title"><i class="fas fa-shopping-cart"></i> Gérer les Achats en Attente</h1>

<div class="admin-container">
    <?php
    if (isset($_SESSION['success_message'])) {
        echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
        unset($_SESSION['error_message']);
    }
    ?>

    <?php if ($achats->num_rows > 0): ?>
        <?php while ($achat = $achats->fetch_assoc()): ?>
            <div class="purchase-card">
                <h3>Achat #<?= $achat['Id_Achat'] ?></h3>
                <p><strong>Utilisateur:</strong> <?= htmlspecialchars($achat['Email']) ?></p>
                <p><strong>Événement:</strong> <?= htmlspecialchars($achat['Titre_Evenement']) ?></p>
                <p><strong>Ticket:</strong> <?= htmlspecialchars($achat['Titre_Ticket']) ?> (<?= number_format($achat['Prix'], 2, ',', ' ') ?> €)</p>
                <p><strong>Date d'achat:</strong> <?= date('d/m/Y H:i', strtotime($achat['DateAchat'])) ?></p>

                <form method="POST" action="?page=gerer_achats">
                    <input type="hidden" name="achat_id" value="<?= $achat['Id_Achat'] ?>">

                    <button type="submit" class="accept" name="action" value="valider"><i class="fas fa-check-circle"></i> Valider</button>

                    <button type="button" onclick="toggleRejectForm(this)" class="reject"><i class="fas fa-times-circle"></i> Rejeter</button>

                    <div class="reject-box">
                        <textarea name="remarque_rejet" rows="3" placeholder="Raison du rejet (obligatoire si rejet)..." required></textarea>
                        <button type="submit" name="action" value="rejeter" class="reject"><i class="fas fa-paper-plane"></i> Confirmer le Rejet</button>
                    </div>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-events-message">Aucun achat en attente de validation.</p>
    <?php endif; ?>
</div>

<script>
function toggleRejectForm(btn) {
    const form = btn.closest('form');
    const rejectBox = form.querySelector('.reject-box');
    rejectBox.style.display = rejectBox.style.display === 'none' ? 'flex' : 'none';
}
</script>

<style>
    .purchase-card { background: var(--card-bg); padding: 20px; border-left: 8px solid var(--accent-blue); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); margin-bottom: 20px; }
    .purchase-card h3 { font-family: 'Montserrat', sans-serif; font-size: 1.1em; color: var(--text-dark); margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px; }
    .purchase-card p { margin-bottom: 8px; font-size: 0.9em; color: var(--text-medium); }
    .purchase-card p strong { color: var(--text-dark); font-weight: 600; }
    .purchase-card form { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .purchase-card button { padding: 10px 20px; border: none; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 0.85em; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .purchase-card button:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
    .accept { background-color: #28a745; color: white; }
    .accept:hover { background-color: #218838; }
    .reject { background-color: #dc3545; color: white; }
    .reject:hover { background-color: #c82333; }
    .reject-box { display: none; margin-top: 10px; width: 100%; flex-direction: column; gap: 10px; }
    .reject-box textarea { width: 100%; padding: 10px; border-radius: var(--border-radius-md); border: 1px solid var(--border-light); font-family: 'Inter', sans-serif; resize: vertical; min-height: 70px; font-size: 0.9em; box-sizing: border-box; }
    .no-events-message { text-align: center; padding: 30px; background-color: var(--card-bg); border: 1px dashed var(--border-light); border-radius: var(--border-radius-md); color: var(--text-medium); }
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
