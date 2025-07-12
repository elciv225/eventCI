<?php
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

require_once 'base.php';

// Initialiser le tableau des événements
$evenements = [];

$stmt = $conn->prepare("
    SELECT 
        e.Id_Evenement, 
        e.Titre, 
        e.Description, 
        e.Adresse,
        e.DateDebut, 
        e.DateFin, 
        e.statut_approbation,
        v.Libelle AS NomVille,
        ce.Libelle AS NomCategorie
    FROM evenement e
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement
    JOIN ville v ON e.Id_Ville = v.Id_Ville
    JOIN categorieevenement ce ON e.Id_CategorieEvenement = ce.Id_CategorieEvenement
    WHERE c.Id_Utilisateur = ?
    ORDER BY e.DateDebut DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $evenement = $row;
    $evenement['images'] = [];

    // Récupérer les images liées à l'événement
    $stmt_images = $conn->prepare("SELECT Lien FROM imageevenement WHERE Id_Evenement = ?");
    $stmt_images->bind_param("i", $evenement['Id_Evenement']);
    $stmt_images->execute();
    $result_images = $stmt_images->get_result();

    while ($img = $result_images->fetch_assoc()) {
        $evenement['images'][] = $img['Lien'];
    }

    $stmt_images->close();
    $evenements[] = $evenement;
}

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes événements</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f8fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.07);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            border: 1px solid #e6e6e6;
            padding: 10px 14px;
            text-align: left;
        }
        th {
            background: #f5f8fa;
            color: #555;
        }
        tr:nth-child(even) {
            background: #fdfdfd;
        }
        .btn-creer {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-creer:hover {
            background-color: #0056b3;
        }
        .back-btn {
            display: inline-block;
            margin-top: 25px;
            margin-left: 10px;
            padding: 12px 25px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📅 Mes événements</h1>
        <?php if (count($evenements)): ?>
            <table>
                <tr>
                    <th>Titre</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                </tr>
                <?php foreach ($evenements as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['Titre']) ?></td>
                        <td><?= htmlspecialchars($event['DateDebut']) ?></td>
                        <td><?= htmlspecialchars($event['DateFin']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Vous n’avez encore créé aucun événement.</p>
        <?php endif; ?>
        <a href="creer_evenement.php" class="btn-creer">➕ Créer un nouvel événement</a>
        <a href="menu_utilisateur.php" class="back-btn">🔙 Retour au menu</a>
    </div>
</body>                         
</html> 