<?php
// Script pour afficher les achats en attente de validation

// Pagination
$pageNum = isset($_GET['pageNum']) ? max(1, intval($_GET['pageNum'])) : 1;
$limit = 10;
$start = ($pageNum - 1) * $limit;

// Récupérer les achats avec le statut 'payé' (en attente de validation)
$stmt = $conn->prepare("
    SELECT
        a.Id_Achat,
        a.DateAchat,
        a.Statut,
        u.email AS UserEmail,
        e.Titre AS EventTitre,
        t.Titre AS TicketTitre,
        t.Prix
    FROM achat a
    JOIN utilisateur u ON a.Id_Utilisateur = u.Id_Utilisateur
    JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
    JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
    WHERE a.Statut = 'payé'
    ORDER BY a.DateAchat ASC
    LIMIT ?, ?
");
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$achats = $stmt->get_result();

// Compter le total pour la pagination
$countQuery = $conn->query("SELECT COUNT(*) AS total FROM achat WHERE Statut = 'payé'");
$totalRows = $countQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
?>

<h1 class="section-title"><i class="fas fa-check-circle"></i> Validation des Tickets Achetés</h1>

<div class="admin-container">
    <?php if (isset($_SESSION['validation_message'])): ?>
        <div class="alert alert-<?= $_SESSION['validation_message_type'] ?? 'info' ?>">
            <?= htmlspecialchars($_SESSION['validation_message']) ?>
        </div>
        <?php unset($_SESSION['validation_message'], $_SESSION['validation_message_type']); ?>
    <?php endif; ?>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID Achat</th>
                    <th>Utilisateur</th>
                    <th>Événement</th>
                    <th>Ticket</th>
                    <th>Prix</th>
                    <th>Date d'Achat</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($achats->num_rows > 0): ?>
                    <?php while ($achat = $achats->fetch_assoc()): ?>
                        <tr>
                            <td><?= $achat['Id_Achat'] ?></td>
                            <td><?= htmlspecialchars($achat['UserEmail']) ?></td>
                            <td><?= htmlspecialchars($achat['EventTitre']) ?></td>
                            <td><?= htmlspecialchars($achat['TicketTitre']) ?></td>
                            <td><?= number_format($achat['Prix'], 2, ',', ' ') ?> €</td>
                            <td><?= date('d/m/Y H:i', strtotime($achat['DateAchat'])) ?></td>
                            <td class="actions-cell">
                                <form action="traitement_validation_achat.php" method="POST" class="validation-form">
                                    <input type="hidden" name="id_achat" value="<?= $achat['Id_Achat'] ?>">

                                    <button type="submit" name="action" value="valider" class="btn-success">
                                        <i class="fas fa-check"></i> Valider
                                    </button>

                                    <button type="button" class="btn-danger reject-btn">
                                        <i class="fas fa-times"></i> Rejeter
                                    </button>

                                    <div class="rejection-reason" style="display: none; margin-top: 10px;">
                                        <textarea name="remarque_de_rejet" placeholder="Motif du rejet..." rows="2"></textarea>
                                        <button type="submit" name="action" value="rejeter" class="btn-danger">
                                            Confirmer Rejet
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fas fa-info-circle"></i> Aucun ticket en attente de validation.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($totalPages > 1): ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=valider_achats&pageNum=<?= $i ?>"
                   class="pagination-link <?= ($i === $pageNum) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rejectButtons = document.querySelectorAll('.reject-btn');
    rejectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reasonDiv = this.nextElementSibling;
            if (reasonDiv.style.display === 'none') {
                reasonDiv.style.display = 'block';
                this.style.display = 'none'; // Cacher le bouton "Rejeter" initial
            }
        });
    });
});
</script>
