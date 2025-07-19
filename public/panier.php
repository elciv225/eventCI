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

// Pour les besoins de la démo, utilisons des données statiques
$cart_items = [
    [
        'Id_Achat' => 1,
        'Id_TicketEvenement' => 1,
        'Titre_Ticket' => 'Place Standard',
        'Prix' => 25.00,
        'DateAchat' => '2023-11-20 14:30:00',
        'Titre_Evenement' => 'Concert de musique classique',
        'DateDebut_Evenement' => '2023-12-15 19:30:00',
        'DateFin_Evenement' => '2023-12-15 22:00:00',
        'Lieu_Evenement' => 'Paris'
    ],
    [
        'Id_Achat' => 2,
        'Id_TicketEvenement' => 2,
        'Titre_Ticket' => 'Place VIP',
        'Prix' => 50.00,
        'DateAchat' => '2023-11-21 10:15:00',
        'Titre_Evenement' => 'Festival de Jazz',
        'DateDebut_Evenement' => '2023-12-20 18:00:00',
        'DateFin_Evenement' => '2023-12-20 23:00:00',
        'Lieu_Evenement' => 'Lyon'
    ],
    [
        'Id_Achat' => 3,
        'Id_TicketEvenement' => 3,
        'Titre_Ticket' => 'Pack Famille',
        'Prix' => 80.00,
        'DateAchat' => '2023-11-22 16:45:00',
        'Titre_Evenement' => 'Spectacle pour enfants',
        'DateDebut_Evenement' => '2023-12-25 14:00:00',
        'DateFin_Evenement' => '2023-12-25 16:00:00',
        'Lieu_Evenement' => 'Marseille'
    ]
];

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
                            <button class="btn-danger remove-item" data-id="<?php echo $item['Id_Achat']; ?>">Supprimer</button>
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
        const removeButtons = document.querySelectorAll('.remove-item');

        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                if (confirm('Êtes-vous sûr de vouloir supprimer ce ticket de votre panier ?')) {
                    // Dans une implémentation réelle, on enverrait une requête AJAX
                    // Pour la démo, on supprime simplement l'élément du DOM
                    this.closest('.cart-item').remove();

                    // Vérifier s'il reste des éléments dans le panier
                    const remainingItems = document.querySelectorAll('.cart-item');
                    if (remainingItems.length === 0) {
                        // Afficher le message "panier vide"
                        const cartItems = document.querySelector('.cart-items');
                        const cartSummary = document.querySelector('.cart-summary');

                        const emptyCart = document.createElement('div');
                        emptyCart.className = 'cart-empty';
                        emptyCart.innerHTML = `
                            <p>Votre panier est vide.</p>
                            <a href="?page=accueil" class="btn-primary">Découvrir des événements</a>
                        `;

                        cartItems.replaceWith(emptyCart);
                        cartSummary.remove();
                    }
                }
            });
        });
    });
</script>
