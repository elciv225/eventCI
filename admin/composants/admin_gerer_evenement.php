<?php
// Traitement d'une validation ou d'un rejet
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'], $_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $remarque = $_POST['remarque'] ?? '';

    // Récupérer l'ID du créateur de l'événement et le titre
    $info_query = "SELECT c.Id_Utilisateur, e.Titre
                   FROM evenement e
                   JOIN creer c ON e.Id_Evenement = c.Id_Evenement
                   WHERE e.Id_Evenement = ?";
    $stmt_info = $conn->prepare($info_query);
    $stmt_info->bind_param("i", $id);
    $stmt_info->execute();
    $result_info = $stmt_info->get_result();
    $info = $result_info->fetch_assoc();

    if ($info) {
        $creator_id = $info['Id_Utilisateur'];
        $event_title = $info['Titre'];

        $conn->begin_transaction();
        try {
            if ($action === 'approuve') {
                $stmt = $conn->prepare("UPDATE evenement SET statut_approbation = 'approuve' WHERE Id_Evenement = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $message = "Bonne nouvelle ! Votre événement '" . htmlspecialchars($event_title) . "' a été approuvé et est maintenant visible sur le site.";
                $link = "?page=details&id=" . $id;
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, related_link) VALUES (?, ?, ?)");
                $stmt_notif->bind_param("iss", $creator_id, $message, $link);
                $stmt_notif->execute();

            } elseif ($action === 'rejete') {
                $stmt = $conn->prepare("UPDATE evenement SET statut_approbation = 'rejete', remarque_rejet = ? WHERE Id_Evenement = ?");
                $stmt->bind_param("si", $remarque, $id);
                $stmt->execute();

                $message = "Votre événement '" . htmlspecialchars($event_title) . "' a été rejeté. Raison : " . htmlspecialchars($remarque);
                $link = "?page=mon-profil&tab=en_attente";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, message, related_link) VALUES (?, ?, ?)");
                $stmt_notif->bind_param("iss", $creator_id, $message, $link);
                $stmt_notif->execute();
            }
            $conn->commit();
            $_SESSION['success_message'] = "Action effectuée avec succès.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }
    // Rediriger pour éviter la resoumission du formulaire
    header("Location: index.php?page=admin_gerer_evenement");
    exit;
}

// Récupération des événements en attente
$sql = "SELECT Id_Evenement, Titre, Description, Adresse, DateDebut, DateFin FROM evenement WHERE statut_approbation = 'en_attente'";
$result = $conn->query($sql);
?>

<h1 class="section-title animate_animated animate_fadeIn">Événements en attente de validation</h1>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .event { background: var(--card-bg); padding: 30px; border-left: 8px solid var(--accent-orange); border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); margin-bottom: 30px; width: 100%; box-sizing: border-box; transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .event:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .event h3 { font-family: 'Montserrat', sans-serif; font-size: 1.2em; color: var(--text-dark); margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid rgba(0, 0, 0, 0.1); padding-bottom: 10px; }
    .event p { margin-bottom: 10px; font-size: 0.95em; color: var(--text-medium); }
    .event p strong { color: var(--text-dark); font-weight: 600; }
    .event form { margin-top: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
    .event button { padding: 12px 22px; border: none; border-radius: 30px; cursor: pointer; font-weight: 600; font-size: 0.9em; transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
    .event button:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); }
    .accept { background-color: #28a745; color: white; }
    .accept:hover { background-color: #218838; }
    .reject { background-color: #dc3545; color: white; }
    .reject:hover { background-color: #c82333; }
    .reject-box { display: none; margin-top: 20px; width: 100%; flex-direction: column; gap: 10px; }
    .reject-box textarea { width: 100%; padding: 12px; border-radius: var(--border-radius-md); border: 1px solid var(--border-light, #E0E0E0); font-family: 'Inter', sans-serif; resize: vertical; min-height: 80px; font-size: 0.95em; box-sizing: border-box; }
    .no-events-message { text-align: center; padding: 30px; background-color: var(--card-bg); border: 1px dashed rgba(0, 0, 0, 0.1); border-radius: var(--border-radius-md); color: var(--text-medium); font-size: 1em; margin: 50px auto; box-shadow: var(--shadow-sm); }
</style>

<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($event = $result->fetch_assoc()): ?>
        <div class="event">
            <h3><?= htmlspecialchars($event['Titre']) ?></h3>
            <p><?= nl2br(htmlspecialchars($event['Description'])) ?></p>
            <p><strong>Adresse :</strong> <?= htmlspecialchars($event['Adresse']) ?></p>
            <p><strong>Du :</strong> <?= date('d/m/Y H:i', strtotime($event['DateDebut'])) ?> <strong>au</strong> <?= date('d/m/Y H:i', strtotime($event['DateFin'])) ?></p>

            <form method="POST" action="index.php?page=admin_gerer_evenement">
                <input type="hidden" name="id" value="<?= $event['Id_Evenement'] ?>">

                <button type="submit" class="accept" name="action" value="approuve"><i class="fas fa-check-circle"></i> Approuver</button>

                <button type="button" onclick="toggleRejectForm(this)" class="reject"><i class="fas fa-times-circle"></i> Rejeter</button>

                <div class="reject-box">
                    <textarea name="remarque" rows="3" placeholder="Raison du rejet (obligatoire si rejet)..."></textarea>
                    <button type="submit" name="action" value="rejete" class="reject"><i class="fas fa-paper-plane"></i> Confirmer le Rejet</button>
                </div>
            </form>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p class="no-events-message">Aucun événement en attente de validation pour le moment. Tout est à jour !</p>
<?php endif; ?>

<?php
// Afficher les messages de session
if (isset($_SESSION['success_message'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']);
}
?>

<script>
    function toggleRejectForm(btn) {
        const box = btn.closest("form").querySelector(".reject-box");
        box.style.display = box.style.display === 'none' ? 'flex' : 'none';
    }
</script>
