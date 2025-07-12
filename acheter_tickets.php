<?php
require_once 'base.php';

// Vérifier que l'ID du ticket est présent
if (!isset($_GET['id'])) {
    die("Aucun ticket sélectionné.");
}

$id_ticket = intval($_GET['id']);

// Récupérer les infos du ticket
$stmt = $conn->prepare("
    SELECT te.*, e.Titre AS EvenementTitre 
    FROM ticketevenement te
    JOIN evenement e ON te.Id_Evenement = e.Id_Evenement
    WHERE te.Id_TicketEvenement = ?
");
$stmt->bind_param("i", $id_ticket);
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
    <title>Achat de ticket</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f4f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .ticket-details {
            margin-bottom: 30px;
        }
        .ticket-details p {
            margin: 8px 0;
            font-size: 1.05em;
        }
        .price {
            font-size: 1.3em;
            color: #28a745;
            font-weight: bold;
        }
        .actions {
            text-align: center;
        }
        .buy-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 1.1em;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .buy-button:hover {
            background-color: #218838;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Acheter ce Ticket</h1>

    <div class="ticket-details">
        <p><strong>Événement :</strong> <?= htmlspecialchars($ticket['EvenementTitre']) ?></p>
        <p><strong>Titre du ticket :</strong> <?= htmlspecialchars($ticket['Titre']) ?></p>
        <p><strong>Description :</strong><br><?= nl2br(htmlspecialchars($ticket['Description'])) ?></p>
        <p><strong>Nombre disponible :</strong> <?= $ticket['NombreDisponible'] ?></p>
        <p class="price">Prix : <?= number_format($ticket['Prix'], 2, ',', ' ') ?> €</p>
    </div>

    <div class="actions">
        <form action="confirmation_achat.php" method="POST">
            <input type="hidden" name="ticket_id" value="<?= $ticket['Id_TicketEvenement'] ?>">
            <button type="submit" class="buy-button">🎫 Confirmer l’achat</button>
        </form>
        <br>
        <a href="tickets_par_evenement.php?id=<?= $ticket['Id_Evenement'] ?>" class="back-link">← Retour aux tickets</a>
    </div>
</div>
</body>
</html>