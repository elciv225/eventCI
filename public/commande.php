<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}
require_once 'config/qrcode.php';
require_once 'config/mail.php';

// Vérifier si la colonne QRCode existe dans la table achat
$check_column = $conn->query("SHOW COLUMNS FROM achat LIKE 'QRCode'");
if ($check_column->num_rows === 0) {
    // La colonne n'existe pas, l'ajouter
    $conn->query("ALTER TABLE achat ADD COLUMN QRCode LONGTEXT NULL");
}

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
    // Traitement de la validation du panier (sans paiement)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_purchase'])) {
        // Récupérer tous les tickets dans le panier
        $cart_query = "SELECT a.Id_Achat, a.Id_TicketEvenement, t.Titre AS Titre_Ticket, t.Prix,
                       e.Titre AS Titre_Evenement, e.DateDebut, e.Salle AS Lieu
                       FROM achat a
                       JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
                       JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
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

            // Définir un message de succès
            $_SESSION['success_message'] = "Vos tickets ont été validés avec succès ! Vous pouvez les consulter dans la section 'Mes Tickets Achetés'.";

            // Générer les QR codes et envoyer les emails pour chaque ticket
            while ($ticket = $cart_result->fetch_assoc()) {
                // Créer l'URL du ticket pour le QR code
                $ticket_url = "http://" . $_SERVER['HTTP_HOST'] . "/?page=ticket&id=" . $ticket['Id_Achat'];

                // Générer le QR code avec l'URL
                $qrcode_base64 = generateQrBase64($ticket_url);

                // Sauvegarder le QR code et l'URL dans la base de données
                $qrcode_query = "UPDATE achat SET QRCode = ?, TicketUrl = ?, DatePaiement = NOW() WHERE Id_Achat = ?";
                $stmt = $conn->prepare($qrcode_query);
                $stmt->bind_param("ssi", $qrcode_base64, $ticket_url, $ticket['Id_Achat']);
                $stmt->execute();

                // Préparer les données pour l'email
                $ticket_email_data = [
                    'Id_Achat' => $ticket['Id_Achat'],
                    'Titre_Evenement' => $ticket['Titre_Evenement'],
                    'Titre_Ticket' => $ticket['Titre_Ticket'],
                    'Date_Evenement' => (new DateTime($ticket['DateDebut']))->format('d/m/Y à H:i'),
                    'Lieu' => $ticket['Lieu'] ?? 'N/A',
                    'QRCode' => $qrcode_base64,
                    'Prix' => $ticket['Prix'] ?? 0,
                    'DateAchat' => date('Y-m-d H:i:s'),
                    'DatePaiement' => date('Y-m-d H:i:s')
                ];

                // Envoyer l'email avec le ticket
                $email_sent = sendTicketReceiptEmail($user_email, $user_name, $ticket_email_data, $ticket_url);

                if (!$email_sent) {
                    error_log("Erreur lors de l'envoi de l'email pour le ticket ID: " . $ticket['Id_Achat']);
                }
            }

            // Rediriger vers la page des tickets achetés
            header('Location: ?page=mes-ticket&tab=achats');
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
                    e.Salle AS Lieu_Evenement
                  FROM achat a
                  JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
                  JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
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
                      e.Salle AS Lieu_Evenement
                    FROM ticketevenement t
                    JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
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
                // Vérifier si l'utilisateur a déjà acheté ce ticket
                $check_query = "SELECT COUNT(*) as count FROM achat 
                               WHERE Id_Utilisateur = ? AND Id_TicketEvenement = ? AND Statut = 'payé'";
                $stmt = $conn->prepare($check_query);
                $stmt->bind_param("ii", $user_id, $ticket_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $already_purchased = $result->fetch_assoc()['count'] > 0;

                if ($already_purchased) {
                    $error_message = "Vous avez déjà acheté ce ticket. Vous ne pouvez l'acheter qu'une seule fois.";
                } else {
                    // Vérifier si le ticket est déjà dans le panier
                    $check_cart_query = "SELECT COUNT(*) as count FROM achat 
                                       WHERE Id_Utilisateur = ? AND Id_TicketEvenement = ? AND Statut = 'panier'";
                    $stmt = $conn->prepare($check_cart_query);
                    $stmt->bind_param("ii", $user_id, $ticket_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $already_in_cart = $result->fetch_assoc()['count'] > 0;

                    if ($already_in_cart) {
                        $error_message = "Ce ticket est déjà dans votre panier.";
                    } else {
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

                            // Définir un message de succès
                            $_SESSION['success_message'] = "Le ticket a été ajouté à votre panier avec succès.";

                            // Rediriger vers le panier
                            header('Location: ?page=mes-ticket');
                            exit;
                        } else {
                            $error_message = "Erreur lors de l'ajout au panier.";
                        }
                    }
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
    <div class="content-wrapper">
        <h1 class="section-title"><?php echo htmlspecialchars($page_title); ?></h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($checkout_mode): ?>
            <!-- ============================================= -->
            <!-- Mode: Paiement du Panier (Checkout)          -->
            <!-- ============================================= -->
            <?php if (empty($cart_items)): ?>
                <div class="cart-empty">
                    <p>Votre panier est vide.</p>
                    <a href="?page=accueil" class="btn">Découvrir des événements</a>
                </div>
            <?php else: ?>
                <div class="checkout-container">
                    <div class="card cart-summary">
                        <h2 class="section-subtitle">Récapitulatif de votre commande</h2>
                        <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="cart-item-info">
                                        <h3><?php echo htmlspecialchars($item['Titre_Evenement']); ?></h3>
                                        <p>
                                            <?php echo htmlspecialchars($item['Titre_Ticket']); ?><br>
                                            <?php echo date('d/m/Y à H:i', strtotime($item['DateDebut_Evenement'])); ?><br>
                                            <?php echo htmlspecialchars($item['Lieu_Evenement']); ?>
                                        </p>
                                    </div>
                                    <div class="cart-item-price">
                                        <?php echo number_format($item['Prix'], 0, '', ' '); ?> FCFA
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="cart-total">
                            <span>Total:</span>
                            <span><?php echo number_format($cart_total, 0, '', ' '); ?> FCFA</span>
                        </div>
                    </div>

                    <div class="card purchase-form">
                        <h2 class="section-subtitle">Confirmation de l'achat</h2>
                        <form method="post" action="">
                            <div class="form-group">
                                <p>Vous êtes sur le point de valider votre panier. Cliquez sur le bouton ci-dessous pour confirmer votre achat.</p>
                            </div>
                            <div class="form-actions">
                                <a href="?page=mes-ticket" class="btn btn-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                        <path d="M228,128a12,12,0,0,1-12,12H69l51.52,51.51a12,12,0,0,1-17,17l-72-72a12,12,0,0,1,0-17l72-72a12,12,0,0,1,17,17L69,116H216A12,12,0,0,1,228,128Z"></path>
                                    </svg>
                                    Retour au panier
                                </a>
                                <button type="submit" name="confirm_purchase" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                        <path d="M224,48H32A16,16,0,0,0,16,64V192a16,16,0,0,0,16,16H224a16,16,0,0,0,16-16V64A16,16,0,0,0,224,48Zm0,144H32V64H224V192ZM180,128a28,28,0,0,1-28,28H76a12,12,0,0,1,0-24h76a4,4,0,0,0,0-8H76a12,12,0,0,1,0-24h76A28,28,0,0,1,180,128Z"></path>
                                    </svg>
                                    Confirmer l'achat
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ============================================= -->
            <!-- Mode: Ajout d'un Ticket au Panier             -->
            <!-- ============================================= -->
            <?php if (isset($ticket)): ?>
                <div class="ticket-order-container">
                    <div class="ticket-info">
                        <div class="card ticket-details">
                            <h2 class="section-subtitle"><?php echo htmlspecialchars($ticket['Titre_Evenement']); ?></h2>
                            <p>
                                <strong>Date:</strong> <?php echo date('d/m/Y à H:i', strtotime($ticket['DateDebut'])); ?><br>
                                <strong>Lieu:</strong> <?php echo htmlspecialchars($ticket['Lieu_Evenement']); ?>
                            </p>
                        </div>
                        <div class="card ticket-details">
                            <h3 style="font-size: 1.25rem;"><?php echo htmlspecialchars($ticket['Titre']); ?></h3>
                            <p><?php echo htmlspecialchars($ticket['Description']); ?></p>
                            <div class="ticket-price">
                                <?php echo number_format($ticket['Prix'], 0, '', ' '); ?> FCFA
                            </div>
                            <p>
                                <strong>Disponibilité:</strong> <?php echo $ticket['NombreDisponible']; ?> tickets restants
                            </p>
                        </div>
                    </div>

                    <div class="order-form-container">
                        <div class="card">
                            <h2 class="section-subtitle">Finaliser votre achat</h2>
                            <form method="post" action="">
                                <div class="form-group terms-conditions">
                                    <label>
                                        <input type="checkbox" required> J'accepte les <a href="#">conditions générales de vente</a>.
                                    </label>
                                </div>
                                <div class="form-actions">
                                    <a href="?page=details&id=<?php echo $ticket['Id_Evenement']; ?>" class="btn btn-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                            <path d="M228,128a12,12,0,0,1-12,12H69l51.52,51.51a12,12,0,0,1-17,17l-72-72a12,12,0,0,1,0-17l72-72a12,12,0,0,1,17,17L69,116H216A12,12,0,0,1,228,128Z"></path>
                                        </svg>
                                        Retour
                                    </a>
                                    <button type="submit" name="add_to_cart" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                            <path d="M100,216a20,20,0,1,1-20-20A20,20,0,0,1,100,216Zm84-20a20,20,0,1,0,20,20A20,20,0,0,0,184,196ZM235.47,75.53l-27.29,88.7A27.87,27.87,0,0,1,181.41,184H82.93A28.13,28.13,0,0,1,56,163.47L21.25,44H12a12,12,0,0,1,0-24H24.51A20,20,0,0,1,44.34,36.8L56,64H224a12,12,0,0,1,11.47,15.53ZM208.89,88H62.65l21.16,72h97.6a4,4,0,0,0,3.81-2.75l23.72-77.25Z"></path>
                                        </svg>
                                        Ajouter au panier
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
