<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}

// Inclure les fichiers pour le QR code et l'email
require_once __DIR__ . '/../config/qrcode.php';
require_once __DIR__ . '/../config/mail.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    // Rediriger vers la page d'authentification si non connecté
    header('Location: authentification.php');
    exit;
}

$user_id = $_SESSION['utilisateur']['id'];
$user_email = $_SESSION['utilisateur']['email'] ?? '';
$user_name = $_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom'];

// Étape 1: Récupérer les informations du ticket
$ticket_id = isset($_GET['ticket']) ? intval($_GET['ticket']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Si on est en mode checkout, on traite le panier complet
if ($action === 'checkout') {
    // Traitement du paiement du panier complet
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
        // Récupérer tous les tickets dans le panier
        $cart_query = "SELECT a.Id_Achat, a.Id_TicketEvenement, t.Titre AS Titre_Ticket, 
                       e.Titre AS Titre_Evenement, e.DateDebut, v.Libelle AS Lieu
                       FROM achat a
                       JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
                       JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
                       LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
                       WHERE a.Id_Utilisateur = ? AND a.Statut = 'panier'";

        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_result = $stmt->get_result();

        if ($cart_result && $cart_result->num_rows > 0) {
            // Mettre à jour le statut des tickets à "payé"
            $update_query = "UPDATE achat SET Statut = 'payé', DatePaiement = NOW() WHERE Id_Utilisateur = ? AND Statut = 'panier'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Générer les QR codes et envoyer les emails pour chaque ticket
            while ($ticket = $cart_result->fetch_assoc()) {
                // Générer un QR code unique pour ce ticket
                $ticket_data = json_encode([
                    'id' => $ticket['Id_Achat'],
                    'event' => $ticket['Titre_Evenement'],
                    'ticket' => $ticket['Titre_Ticket'],
                    'user' => $user_id,
                    'timestamp' => time()
                ]);

                $qrcode_base64 = generateQrBase64($ticket_data);

                // Sauvegarder le QR code dans la base de données
                $qrcode_query = "UPDATE achat SET QRCode = ? WHERE Id_Achat = ?";
                $stmt = $conn->prepare($qrcode_query);
                $stmt->bind_param("si", $qrcode_base64, $ticket['Id_Achat']);
                $stmt->execute();

                // Préparer les données pour l'email
                $ticket_email_data = [
                    'Id_Achat' => $ticket['Id_Achat'],
                    'Titre_Evenement' => $ticket['Titre_Evenement'],
                    'Titre_Ticket' => $ticket['Titre_Ticket'],
                    'Date_Evenement' => (new DateTime($ticket['DateDebut']))->format('d/m/Y à H:i'),
                    'Lieu' => $ticket['Lieu'] ?? 'N/A',
                    'QRCode' => $qrcode_base64
                ];

                // Envoyer l'email avec le ticket
                $view_ticket_url = "http://" . $_SERVER['HTTP_HOST'] . "/?page=ticket&id=" . $ticket['Id_Achat'];
                sendTicketReceiptEmail($user_email, $user_name, $ticket_email_data, $view_ticket_url);
            }

            // Rediriger vers une page de confirmation
            header('Location: ?page=confirmation');
            exit;
        } else {
            $error_message = "Votre panier est vide.";
        }
    }

    // Récupérer les articles du panier pour affichage
    $cart_query = "SELECT 
                    a.Id_Achat, 
                    t.Titre AS Titre_Ticket, 
                    t.Prix,
                    e.Titre AS Titre_Evenement,
                    e.DateDebut AS DateDebut_Evenement,
                    v.Libelle AS Lieu_Evenement
                  FROM achat a
                  JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
                  JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
                  LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
                  WHERE a.Id_Utilisateur = ? AND a.Statut = 'panier'
                  ORDER BY a.DateAchat DESC";

    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $cart_total = 0;

    if ($result && $result->num_rows > 0) {
        while ($item = $result->fetch_assoc()) {
            $cart_items[] = $item;
            $cart_total += $item['Prix'];
        }
    }

    // Afficher la page de paiement
    $page_title = "Finaliser votre commande";
    $checkout_mode = true;
} 
// Sinon, on traite l'ajout d'un ticket au panier
elseif ($ticket_id > 0) {
    // Récupérer les informations du ticket
    $ticket_query = "SELECT 
                      t.Id_TicketEvenement, t.Titre, t.Description, t.Prix, t.NombreDisponible,
                      e.Id_Evenement, e.Titre AS Titre_Evenement, e.DateDebut, e.DateFin,
                      v.Libelle AS Lieu_Evenement
                    FROM ticketevenement t
                    JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
                    LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
                    WHERE t.Id_TicketEvenement = ?";

    $stmt = $conn->prepare($ticket_query);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $ticket = $result->fetch_assoc();

        // Vérifier si le ticket est disponible
        if ($ticket['NombreDisponible'] <= 0) {
            $error_message = "Ce ticket n'est plus disponible.";
        } else {
            // Traitement de l'ajout au panier
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
                // Ajouter le ticket au panier
                $insert_query = "INSERT INTO achat (Id_Utilisateur, Id_TicketEvenement, DateAchat, Statut) 
                                VALUES (?, ?, NOW(), 'panier')";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("ii", $user_id, $ticket_id);

                if ($stmt->execute()) {
                    // Mettre à jour le nombre de tickets disponibles
                    $update_query = "UPDATE ticketevenement SET NombreDisponible = NombreDisponible - 1 
                                    WHERE Id_TicketEvenement = ? AND NombreDisponible > 0";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("i", $ticket_id);
                    $stmt->execute();

                    // Rediriger vers le panier
                    header('Location: ?page=panier');
                    exit;
                } else {
                    $error_message = "Erreur lors de l'ajout au panier.";
                }
            }
        }
    } else {
        $error_message = "Ticket non trouvé.";
    }

    // Afficher la page de commande d'un ticket
    $page_title = "Commander un ticket";
    $checkout_mode = false;
} else {
    // Rediriger vers la page d'accueil si aucun ticket n'est spécifié
    header('Location: ?page=accueil');
    exit;
}

?>

<main class="page-container">
    <section class="order-section">
        <h1 class="section-title"><?php echo $page_title; ?></h1>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message" style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($checkout_mode): ?>
            <!-- Mode paiement du panier -->
            <?php if (empty($cart_items)): ?>
                <div class="cart-empty" style="text-align: center; padding: 40px 0;">
                    <p>Votre panier est vide.</p>
                    <a href="?page=accueil" class="btn-primary" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #667eea; color: white; text-decoration: none; border-radius: 5px;">Découvrir des événements</a>
                </div>
            <?php else: ?>
                <div class="checkout-container" style="max-width: 800px; margin: 0 auto;">
                    <div class="cart-summary" style="margin-bottom: 30px; background-color: #f8f9fa; padding: 20px; border-radius: 10px;">
                        <h2 style="margin-top: 0; color: #333;">Récapitulatif de votre commande</h2>
                        
                        <div class="cart-items" style="margin-bottom: 20px;">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item" style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #eee;">
                                    <div class="cart-item-info">
                                        <h3 style="margin: 0 0 5px 0; font-size: 1.1rem;"><?php echo htmlspecialchars($item['Titre_Evenement']); ?></h3>
                                        <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($item['Titre_Ticket']); ?> - 
                                            <?php echo date('d/m/Y à H:i', strtotime($item['DateDebut_Evenement'])); ?> - 
                                            <?php echo htmlspecialchars($item['Lieu_Evenement']); ?>
                                        </p>
                                    </div>
                                    <div class="cart-item-price" style="font-weight: bold; color: #333;">
                                        <?php echo number_format($item['Prix'], 2, ',', ' '); ?> €
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-total" style="display: flex; justify-content: space-between; padding-top: 15px; font-size: 1.2rem; font-weight: bold;">
                            <span>Total:</span>
                            <span><?php echo number_format($cart_total, 2, ',', ' '); ?> €</span>
                        </div>
                    </div>
                    
                    <div class="payment-form" style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h2 style="margin-top: 0; color: #333;">Informations de paiement</h2>
                        
                        <form method="post" action="" style="margin-top: 20px;">
                            <!-- Simuler un formulaire de paiement -->
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="card_number" style="display: block; margin-bottom: 5px; font-weight: bold;">Numéro de carte</label>
                                <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            
                            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="expiry_date" style="display: block; margin-bottom: 5px; font-weight: bold;">Date d'expiration</label>
                                    <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/AA" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                </div>
                                
                                <div class="form-group" style="flex: 1;">
                                    <label for="cvv" style="display: block; margin-bottom: 5px; font-weight: bold;">CVV</label>
                                    <input type="text" id="cvv" name="cvv" placeholder="123" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="card_holder" style="display: block; margin-bottom: 5px; font-weight: bold;">Nom du titulaire</label>
                                <input type="text" id="card_holder" name="card_holder" placeholder="John Doe" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            
                            <div class="form-actions" style="display: flex; justify-content: space-between; margin-top: 30px;">
                                <a href="?page=panier" class="btn-secondary" style="padding: 12px 20px; background-color: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px; font-weight: bold;">Retour au panier</a>
                                <button type="submit" name="confirm_payment" class="btn-primary" style="padding: 12px 30px; background-color: #667eea; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Confirmer le paiement</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Mode ajout d'un ticket au panier -->
            <?php if (isset($ticket)): ?>
                <div class="ticket-order-container" style="max-width: 800px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 30px;">
                    <div class="ticket-info" style="flex: 1; min-width: 300px;">
                        <div class="event-details" style="margin-bottom: 20px;">
                            <h2 style="margin-top: 0; color: #333;"><?php echo htmlspecialchars($ticket['Titre_Evenement']); ?></h2>
                            <p style="color: #666;">
                                <strong>Date:</strong> <?php echo date('d/m/Y à H:i', strtotime($ticket['DateDebut'])); ?><br>
                                <strong>Lieu:</strong> <?php echo htmlspecialchars($ticket['Lieu_Evenement']); ?>
                            </p>
                        </div>
                        
                        <div class="ticket-details" style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <h3 style="margin-top: 0; color: #333;"><?php echo htmlspecialchars($ticket['Titre']); ?></h3>
                            <p style="color: #666;"><?php echo htmlspecialchars($ticket['Description']); ?></p>
                            <div class="ticket-price" style="font-size: 1.5rem; font-weight: bold; color: #333; margin: 15px 0;">
                                <?php echo number_format($ticket['Prix'], 2, ',', ' '); ?> €
                            </div>
                            <p style="color: #666;">
                                <strong>Disponibilité:</strong> <?php echo $ticket['NombreDisponible']; ?> tickets restants
                            </p>
                        </div>
                    </div>
                    
                    <div class="order-form" style="flex: 1; min-width: 300px; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #333;">Ajouter au panier</h3>
                        
                        <form method="post" action="" style="margin-top: 20px;">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="checkbox" required> J'accepte les <a href="#" style="color: #667eea;">conditions générales de vente</a>
                                </label>
                            </div>
                            
                            <div class="form-actions" style="display: flex; justify-content: space-between; margin-top: 30px;">
                                <a href="?page=details&id=<?php echo $ticket['Id_Evenement']; ?>" class="btn-secondary" style="padding: 12px 20px; background-color: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px; font-weight: bold;">Retour aux détails</a>
                                <button type="submit" name="add_to_cart" class="btn-primary" style="padding: 12px 30px; background-color: #667eea; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Ajouter au panier</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
