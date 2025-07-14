<?php
// Activez l'affichage des erreurs PHP pour le débogage (À DÉSACTIVER EN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Démarre la session pour accéder à l'ID de l'utilisateur connecté

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

$achats = []; // Pour stocker les achats récupérés
$message = '';
$errors = [];

// --- Récupération de l'ID de l'utilisateur connecté ---
$loggedInUserId = $_SESSION['utilisateur']['id'] ?? null;

if (!$loggedInUserId) {
    // Si l'utilisateur n'est pas connecté, affichez un message d'erreur et ne proposez pas le contenu.
    $errors['auth'] = "Vous devez être connecté pour voir votre panier (vos achats).";
    // Optionnel: Redirection
    // header("Location: login.php");
    // exit();
} else {
    // Requête SQL pour récupérer tous les achats de l'utilisateur connecté
    // Nous joignons 'achat' avec 'ticketevenement' et 'evenement'
    $sqlAchats = "SELECT
                        a.Id_Achat,
                        a.DateAchat,
                        te.Titre AS TicketTitre,
                        te.Description AS TicketDescription,
                        te.Prix AS TicketPrix,
                        e.Titre AS EvenementTitre,
                        e.DateDebut AS EvenementDateDebut,
                        e.DateFin AS EvenementDateFin
                    FROM
                        achat a
                    JOIN
                        ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement
                    JOIN
                        evenement e ON te.Id_Evenement = e.Id_Evenement
                    WHERE
                        a.Id_Utilisateur = ?
                    ORDER BY
                        a.DateAchat DESC";

    if ($stmt = $conn->prepare($sqlAchats)) {
        $stmt->bind_param("i", $loggedInUserId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $achats[] = $row;
                }
            } else {
                $message = '<p class="message info">Votre panier (historique d\'achats) est vide. Vous n\'avez pas encore effectué d\'achats.</p>';
            }
        } else {
            $errors['db_query'] = "Erreur lors de l'exécution de la requête d'achats : " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errors['db_query'] = "Erreur lors de la préparation de la requête d'achats : " . $conn->error;
    }
}

// Assurez-vous de fermer la connexion UNIQUEMENT APRÈS toutes les opérations de base de données.
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier (Mes Achats)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .message {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .info {
            background-color: #e0f7fa;
            color: #00796b;
            border: 1px solid #b2ebf2;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #007bff; /* Bleu pour les en-têtes */
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .back-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mon Panier (Historique des Achats)</h1>

        <?php if (!empty($errors['auth'])): ?>
            <div class="message error">
                <?php echo $errors['auth']; ?>
            </div>
            <p>Veuillez vous <a href="login.php">connecter</a> pour voir votre historique d'achats.</p>
        <?php elseif (!empty($errors['db_query'])): ?>
            <div class="message error">
                <?php echo $errors['db_query']; ?>
            </div>
        <?php else: // Afficher le contenu seulement si l'utilisateur est connecté et aucune erreur de base de données ?>
            <?php if (!empty($message)): ?>
                <?php echo $message; ?>
            <?php endif; ?>

            <?php if (!empty($achats)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Achat</th>
                            <th>Date Achat</th>
                            <th>Titre Ticket</th>
                            <th>Description Ticket</th>
                            <th>Prix Ticket (CFA)</th>
                            <th>Événement</th>
                            <th>Date Début Événement</th>
                            <th>Date Fin Événement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($achats as $achat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($achat['Id_Achat']); ?></td>
                                <td><?php echo htmlspecialchars((new DateTime($achat['DateAchat']))->format('d/m/Y H:i')); ?></td>
                                <td><?php echo htmlspecialchars($achat['TicketTitre']); ?></td>
                                <td><?php echo htmlspecialchars($achat['TicketDescription']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($achat['TicketPrix'], 2, ',', ' ')); ?></td>
                                <td><?php echo htmlspecialchars($achat['EvenementTitre']); ?></td>
                                <td><?php echo htmlspecialchars((new DateTime($achat['EvenementDateDebut']))->format('d/m/Y H:i')); ?></td>
                                <td><?php echo htmlspecialchars((new DateTime($achat['EvenementDateFin']))->format('d/m/Y H:i')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <?php if (empty($message)): // Seulement si $message n'a pas déjà été défini par "aucun achat" ?>
                    <p class="message info">Votre panier (historique d'achats) est actuellement vide.</p>
                <?php endif; ?>
            <?php endif; ?>

            <a href="menu_utilisateur.php" class="back-button">Retour au Menu</a>
        <?php endif; ?>
    </div>
</body>
</html>
