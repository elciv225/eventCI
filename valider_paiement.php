<?php
session_start();
require_once 'base.php';

// Vérification des données
if (!isset($_POST['ticket_id']) || !isset($_POST['paiement'])) {
    die("Informations de paiement manquantes.");
}

$ticket_id = intval($_POST['ticket_id']);
$paiement = htmlspecialchars($_POST['paiement']); // Ex : 'wave', 'mtn_money'

// Étape 1 : récupérer les détails du ticket + événement
$stmt = $conn->prepare("
    SELECT te.*, e.Titre AS EvenementTitre
    FROM ticketevenement te
    JOIN evenement e ON te.Id_Evenement = e.Id_Evenement
    WHERE te.Id_TicketEvenement = ?
");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Ticket introuvable.");
}

$ticket = $result->fetch_assoc();

// Étape 2 : vérifier le stock
if ($ticket['NombreDisponible'] <= 0) {
    die("Ce ticket est épuisé.");
}

// Étape 3 : mettre à jour le stock
$nouveau_stock = $ticket['NombreDisponible'] - 1;
$update = $conn->prepare("UPDATE ticketevenement SET NombreDisponible = ? WHERE Id_TicketEvenement = ?");
$update->bind_param("ii", $nouveau_stock, $ticket_id);
$update->execute();
$update->close();
$conn->close();

// Étape 4 : ajouter au panier session
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$_SESSION['panier'][] = [
    'titre' => $ticket['Titre'],
    'evenement' => $ticket['EvenementTitre'],
    'prix' => $ticket['Prix'],
    'paiement' => $paiement
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Achat confirmé</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f2f4f8;
            text-align: center;
            padding-top: 60px;
        }
        .box {
            background: white;
            padding: 40px;
            margin: auto;
            max-width: 550px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .success {
            font-size: 1.4em;
            color: #28a745;
            margin-bottom: 20px;
        }
        .info {
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .btns {
            margin-top: 30px;
        }
        .btns a {
            display: inline-block;
            margin: 8px;
            padding: 12px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btns a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="success">✅ Achat confirmé avec succès !</div>
        <div class="info">Ticket : <strong><?= htmlspecialchars($ticket['Titre']) ?></strong></div>
        <div class="info">Événement : <strong><?= htmlspecialchars($ticket['EvenementTitre']) ?></strong></div>
        <div class="info">Paiement via : <strong><?= ucfirst(str_replace('_', ' ', $paiement)) ?></strong></div>
        <div class="info">Tickets restants : <strong><?= $nouveau_stock ?></strong></div>

        <div class="btns">
            <a href="liste_evenement.php">← Retour aux événements</a>
            <a href="panier.php">🧺 Voir mon panier</a>
        </div>
    </div>
</body>
</html>