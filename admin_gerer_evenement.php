<?php
session_start();
require_once 'base.php';

// Vérification de l'admin
if (!isset($_SESSION['is_admin_fixed']) || $_SESSION['is_admin_fixed'] !== true) {
    header("Location: connexion.php");
    exit("Accès refusé.");
}

// Traitement d'une validation ou d'un rejet
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if (in_array($action, ['approuve', 'rejete'])) {
        $stmt = $conn->prepare("UPDATE evenement SET statut_approbation = ? WHERE Id_Evenement = ?");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Récupération des événements en attente
$sql = "SELECT Id_Evenement, Titre, Description, Adresse, DateDebut, DateFin FROM evenement WHERE statut_approbation = 'en_attente'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation des Événements</title>
    <style>
        body {
            font-family: Arial;
            background: #f9f9f9;
            padding: 40px;
        }
        h2 {
            color: #333;
        }
        .event {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .event button {
            margin-right: 10px;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .accept { background-color: #2ecc71; color: white; }
        .reject { background-color: #e74c3c; color: white; }
    </style>
</head>
<body>

    <h2>Événements en attente de validation</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($event = $result->fetch_assoc()): ?>
            <div class="event">
                <h3><?= htmlspecialchars($event['Titre']) ?></h3>
                <p><?= nl2br(htmlspecialchars($event['Description'])) ?></p>
                <p><strong>Adresse :</strong> <?= htmlspecialchars($event['Adresse']) ?></p>
                <p><strong>Du :</strong> <?= $event['DateDebut'] ?> <strong>au</strong> <?= $event['DateFin'] ?></p>
                
                <form method="POST" action="admin_gerer_evenement.php">
                    <input type="hidden" name="id" value="<?= $event['Id_Evenement'] ?>">
                    <button class="accept" name="action" value="approuve">✅ Approuver</button>
                    <button class="reject" name="action" value="rejete">❌ Rejeter</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Aucun événement en attente.</p>
    <?php endif; ?>

</body>
</html>