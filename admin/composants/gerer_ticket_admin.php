<?php
$recherche = $_GET['recherche'] ?? '';
$pageNum = isset($_GET['pageNum']) ? max(1, intval($_GET['pageNum'])) : 1;
$limit = 10;
$start = ($pageNum - 1) * $limit;

if ($recherche) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM ticketevenement 
        WHERE Titre LIKE ? 
        ORDER BY Id_TicketEvenement DESC 
        LIMIT ?, ?
    ");
    $searchTerm = '%' . $recherche . '%';
    $stmt->bind_param("sii", $searchTerm, $start, $limit);
} else {
    $stmt = $conn->prepare("
        SELECT * 
        FROM ticketevenement 
        ORDER BY Id_TicketEvenement DESC 
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $start, $limit);
}

$stmt->execute();
$tickets = $stmt->get_result();

$countQuery = $conn->query("SELECT COUNT(*) AS total FROM ticketevenement");
$totalRows = $countQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
?>

<h1 class="section-title"><i class="fas fa-ticket-alt"></i> Gestion des Tickets Événement</h1>

<div class="admin-container">
    <div class="search-box">
        <form method="GET" action="">
            <input type="hidden" name="page" value="gerer_ticket_admin">
            <input type="text" name="recherche" placeholder="Rechercher un titre" value="<?= htmlspecialchars($recherche); ?>">
            <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
        </form>
    </div>

    <div class="ticket-grid">
        <?php if (isset($tickets) && $tickets->num_rows > 0): ?>
            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                <div class="ticket-card">
                    <h3><?= htmlspecialchars($ticket['Titre']) ?></h3>
                    <p><strong>Description :</strong> <?= htmlspecialchars($ticket['Description']) ?></p>
                    <p><strong>Prix :</strong> <?= number_format($ticket['Prix'], 2, ',', ' ') ?> CFA</p>
                    <p><strong>Disponible :</strong> <?= $ticket['NombreDisponible'] ?></p>
                    <p><strong>ID Événement :</strong> <?= $ticket['Id_Evenement'] ?></p>
                    <div class="actions">
                        <a href="?page=modifier_ticket_evenement&id=<?= $ticket['Id_TicketEvenement'] ?>" class="modify-btn">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="?page=supprimer_ticket_evenement&id=<?= $ticket['Id_TicketEvenement'] ?>" class="delete-btn">
                            <i class="fas fa-trash-alt"></i> Supprimer
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-users-message">
                <i class="fas fa-info-circle"></i> Aucun ticket trouvé pour votre recherche.
            </p>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=gerer_ticket_admin&pageNum=<?= $i ?>&recherche=<?= urlencode($recherche ?? '') ?>" 
                   class="pagination-link <?= ($i === $pageNum) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>
