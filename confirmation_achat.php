<?php
require_once 'base.php';

if (!isset($_POST['ticket_id'])) {
    die("Aucun ticket sélectionné.");
}

$ticket_id = intval($_POST['ticket_id']);

// Récupérer les infos du ticket
$stmt = $conn->prepare("SELECT te.*, e.Titre AS EvenementTitre 
                        FROM ticketevenement te
                        JOIN evenement e ON te.Id_Evenement = e.Id_Evenement
                        WHERE te.Id_TicketEvenement = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Ticket introuvable.");
}

$ticket = $result->fetch_assoc();
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmer l’achat</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            padding: 40px;
        }
        .container {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .ticket-info p {
            margin: 8px 0;
        }
        form {
            margin-top: 30px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        .payment-option {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
        }
        .payment-option input {
            margin-bottom: 8px;
        }
        .payment-option img {
            max-height: 40px;
        }
        .submit-btn {
            display: block;
            width: 100%;
            background: #28a745;
            color: white;
            font-size: 1.1em;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Confirmer l’achat du ticket</h1>

    <div class="ticket-info">
        <p><strong>Événement :</strong> <?= htmlspecialchars($ticket['EvenementTitre']) ?></p>
        <p><strong>Ticket :</strong> <?= htmlspecialchars($ticket['Titre']) ?></p>
        <p><strong>Prix :</strong> <?= number_format($ticket['Prix'], 2, ',', ' ') ?> €</p>
    </div>

    <form action="valider_paiement.php" method="POST">
        <input type="hidden" name="ticket_id" value="<?= $ticket['Id_TicketEvenement'] ?>">

        <label for="id_utilisateur">ID Utilisateur :</label>
        <input type="number" name="id_utilisateur" id="id_utilisateur" required>

        <label for="mot_de_passe">Mot de passe :</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" required>

        <div class="payment-options">
            <label class="payment-option">
                <input type="radio" name="paiement" value="wave" required>
                <img src="image/wave.png" alt="Wave">
                <div>Wave</div>
            </label>
            <label class="payment-option">
                <input type="radio" name="paiement" value="orange_money" required>
                <img src="image/orange.png" alt="Orange Money">
                <div>Orange Money</div>
            </label>
            <label class="payment-option">
                <input type="radio" name="paiement" value="mtn_money" required>
                <img src="image/mtn.png" alt="MTN Money">
                <div>MTN Money</div>
            </label>
            <label class="payment-option">
                <input type="radio" name="paiement" value="moov_money" required>
                <img src="image/moov.png" alt="Moov Money">
                <div>Moov Money</div>
            </label>
        </div>

        <button type="submit" class="submit-btn">🎫 Payer maintenant</button>
    </form>
</div>
</body>
</html>