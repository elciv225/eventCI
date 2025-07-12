<?php
require_once 'base.php';

// Vérification de l'ID de l'événement
if (!isset($_GET['id'])) {
    header('Location: liste_evenement.php?msg=error&text=Événement+introuvable');
    exit;
}

$id_evenement = intval($_GET['id']);

// Récupération de l'événement + statut d'approbation
$stmt = $conn->prepare("SELECT Titre, statut_approbation FROM evenement WHERE Id_Evenement = ?");
$stmt->bind_param("i", $id_evenement);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: liste_evenement.php?msg=error&text=Aucun+événement+trouvé');
    exit;
}

$evenement = $result->fetch_assoc();

// Bloquer l'accès si l'événement n'est pas approuvé
if ($evenement['statut_approbation'] !== 'approuve') {
    echo 'Les tickets pour cet événement ne sont pas disponibles car il est "' . htmlspecialchars($evenement['statut_approbation']) . '".';
    exit;
}

// Récupération des tickets associés
$stmt = $conn->prepare("SELECT * FROM ticketevenement WHERE Id_Evenement = ?");
$stmt->bind_param("i", $id_evenement);
$stmt->execute();
$ticket_result = $stmt->get_result();

$tickets = [];
while ($row = $ticket_result->fetch_assoc()) {
    $tickets[] = $row;
}

$conn->close();
?>
rtir d’ici tu peux utiliser $evenement et $tickets dans ton HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tickets - <?= htmlspecialchars($evenement['Titre']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f4f8;
            margin: 0; padding: 0;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .ticket-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .ticket-card h2 {
            margin: 0;
            font-size: 1.3em;
            color: #34495e;
        }
        .ticket-card p {
            margin: 10px 0;
            color: #555;
        }
        .price {
            font-weight: bold;
            color: #28a745;
        }
        .buy-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .buy-button:hover {
            background-color: #218838;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 35px;
        }
        .back-link a {
            text-decoration: none;
            color: #007bff;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
   <h1>Tickets pour : <?= htmlspecialchars($evenement['Titre'] ?? 'Titre non disponible') ?></h1>

    <div class="ticket-list">
        <?php if (!empty($tickets)): ?>
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card">
                    <h2><?= htmlspecialchars($ticket['Nom'] ?? 'Nom non défini') ?></h2>
                    <p class="description"><?= htmlspecialchars($ticket['Description'] ?? 'Aucune description') ?></p>
                    <p class="price">
                        Prix :
                        <?= isset($ticket['Prix']) ? number_format((float)$ticket['Prix'], 0, ',', ' ') . ' FCFA' : 'Non indiqué' ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-ticket">
                <p>Aucun ticket disponible pour cet événement.</p>
            </div>
        <?php endif; ?>

        <a href="liste_evenement.php" class="back-button">← Retour à la liste</a>
    </div>

</body>
</html>

























