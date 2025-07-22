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

// Traiter la suppression d'un article du panier si demandé
if (isset($_POST['remove_item']) && isset($_POST['item_id'])) {
    $item_id = intval($_POST['item_id']);
    $user_id = $_SESSION['utilisateur']['id'];

    // Supprimer l'achat de la base de données
    $delete_query = "DELETE FROM achat WHERE Id_Achat = ? AND Id_Utilisateur = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();

    // Rediriger pour éviter la resoumission du formulaire
    header('Location: ?page=panier');
    exit;
}

// Récupérer les articles du panier de l'utilisateur depuis la base de données
$user_id = $_SESSION['utilisateur']['id'];

$cart_query = "SELECT 
                a.Id_Achat, 
                a.Id_TicketEvenement,
                a.DateAchat,
                t.Titre AS Titre_Ticket, 
                t.Prix,
                e.Titre AS Titre_Evenement,
                e.DateDebut AS DateDebut_Evenement,
                e.DateFin AS DateFin_Evenement,
                v.Libelle AS Lieu_Evenement
              FROM achat a
              JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
              JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
              LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
              WHERE a.Id_Utilisateur = ?
              ORDER BY a.DateAchat DESC";

$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
while ($item = $result->fetch_assoc()) {
    $cart_items[] = $item;
}

// Calculer le total du panier
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['Prix'];
}
?>

<main class="page-container">
    <section class="cart-section">
        <h1 class="section-title">Mon Panier</h1>

        <?php if (empty($cart_items)): ?>
            <div class="cart-empty">
                <p>Votre panier est vide.</p>
                <a href="?page=accueil" class="btn-primary">Découvrir des événements</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h3 class="cart-item-title"><?php echo htmlspecialchars($item['Titre_Evenement']); ?></h3>
                            <div class="cart-item-details">
                                <p class="cart-item-ticket"><?php echo htmlspecialchars($item['Titre_Ticket']); ?></p>
                                <p class="cart-item-date">
                                    Le <?php echo date('d/m/Y à H:i', strtotime($item['DateDebut_Evenement'])); ?>
                                </p>
                                <p class="cart-item-location"><?php echo htmlspecialchars($item['Lieu_Evenement']); ?></p>
                            </div>
                        </div>
                        <div class="cart-item-price">
                            <?php echo number_format($item['Prix'], 2, ',', ' '); ?> €
                        </div>
                        <div class="cart-item-actions">
                            <a href="?page=ticket&id=<?php echo $item['Id_Achat']; ?>" class="btn-secondary">Voir le ticket</a>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="item_id" value="<?php echo $item['Id_Achat']; ?>">
                                <button type="submit" name="remove_item" class="btn-danger remove-item" data-id="<?php echo $item['Id_Achat']; ?>">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="cart-total">
                    <span class="total-label">Total:</span>
                    <span class="total-value"><?php echo number_format($cart_total, 2, ',', ' '); ?> €</span>
                </div>
                <div class="cart-actions">
                    <a href="?page=accueil" class="btn-secondary">Continuer mes achats</a>
                    <a href="?page=commande&action=checkout" class="btn-primary">Procéder au paiement</a>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des boutons de suppression
        const removeForms = document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce ticket de votre panier ?')) {
                    e.preventDefault(); // Annuler la soumission du formulaire si l'utilisateur annule
                }
            });
        });
    });
</script>
