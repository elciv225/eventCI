<?php
// Activez l'affichage des erreurs PHP pour le débogage (À DÉSACTIVER EN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Démarre la session pour accéder à l'ID de l'utilisateur connecté

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

$message = '';
$tickets = []; // Pour stocker les tickets récupérés
$errors = []; // Pour stocker les erreurs

// --- Récupération de l'ID de l'utilisateur connecté ---
$loggedInUserId = null;
if (isset($_SESSION['utilisateur']) && isset($_SESSION['utilisateur']['id'])) {
    $loggedInUserId = $_SESSION['utilisateur']['id'];
} else {
    // Si l'utilisateur n'est pas connecté, affichez un message d'erreur et arrêtez l'exécution.
    $errors['auth'] = "Vous devez être connecté pour voir vos tickets.";
}

// Récupérer les tickets seulement si l'utilisateur est connecté
if ($loggedInUserId) {
    // Requête SQL pour récupérer les tickets liés aux événements créés par l'utilisateur connecté
    // Nous joignons 'ticketevenement' avec 'evenement' sur Id_Evenement,
    // puis filtrons par l'Id_Utilisateur de la table 'evenement'.
    // J'ai vérifié votre schéma (image_e8d5f8.png, image_074ef4.png, la table.png).
    // Le nom de la colonne dans 'evenement' est bien 'Id_Utilisateur'.
    // Si l'erreur persiste, VÉRIFIEZ LA CASSE EXACTE DU NOM DE LA COLONNE DANS VOTRE BASE DE DONNÉES VIA PHPMMYADMIN.
$sqlTickets = "SELECT
                    te.Id_TicketEvenement,
                    te.Titre AS TicketTitre,
                    te.Description AS TicketDescription,
                    te.Prix,
                    te.NombreDisponible,
                    e.Titre AS EvenementTitre,
                    e.DateDebut,
                    e.DateFin
                FROM
                    ticketevenement te
                JOIN
                    evenement e ON te.Id_Evenement = e.Id_Evenement
                JOIN
                    creer c ON e.Id_Evenement = c.Id_Evenement
                WHERE
                    c.Id_Utilisateur = ?
                ORDER BY
                    e.DateDebut DESC, te.Titre ASC";
    if ($stmt = $conn->prepare($sqlTickets)) {
        $stmt->bind_param("i", $loggedInUserId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $tickets[] = $row;
                }
            } else {
                $message = '<p class="message info">Vous n\'avez pas encore créé de tickets pour vos événements.</p>';
            }
        } else {
            // Erreur lors de l'exécution de la requête préparée
            $errors['db_query'] = "Erreur lors de l'exécution de la requête des tickets : " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Erreur lors de la préparation de la requête
        $errors['db_query'] = "Erreur lors de la préparation de la requête des tickets : " . $conn->error;
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
    <title>Mes Tickets Créés</title>
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
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons a {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 0.9em;
        }
        .edit-btn {
            background-color: #007bff;
        }
        .edit-btn:hover {
            background-color: #0056b3;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .add-ticket-btn {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .add-ticket-btn:hover {
            background-color: #218838;
        }
        .back-button { /* Nouveau style pour le bouton retour */
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            margin-left: 10px; /* Petit espace avec l'autre bouton */
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mes Tickets Créés</h1>

        <?php if (!empty($errors['auth'])): ?>
            <div class="message error">
                <?php echo $errors['auth']; ?>
            </div>
            <p>Veuillez vous <a href="login.php">connecter</a> pour voir vos tickets.</p>
        <?php elseif (!empty($errors['db_query'])): ?>
            <div class="message error">
                <?php echo $errors['db_query']; ?>
            </div>
        <?php else: // Afficher le contenu seulement si l'utilisateur est connecté et aucune erreur de base de données ?>
            <?php if (!empty($message)): ?>
                <?php echo $message; ?>
            <?php endif; ?>

            <a href="tickets.php" class="add-ticket-btn">Créer un nouveau Ticket</a>
            <a href="menu_utilisateur.php" class="back-button">Retour au menu</a> <?php if (!empty($tickets)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Ticket</th>
                            <th>Titre Ticket</th>
                            <th>Description</th>
                            <th>Prix (CFA)</th>
                            <th>Disponible</th>
                            <th>Événement Associé</th>
                            <th>Date Début Événement</th>
                            <th>Date Fin Événement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['Id_TicketEvenement']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['TicketTitre']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['TicketDescription']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($ticket['Prix'], 2, ',', ' ')); ?></td>
                                <td><?php echo htmlspecialchars($ticket['NombreDisponible']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['EvenementTitre']); ?></td>
                                <td><?php echo htmlspecialchars((new DateTime($ticket['DateDebut']))->format('d/m/Y H:i')); ?></td>
                                <td><?php echo htmlspecialchars((new DateTime($ticket['DateFin']))->format('d/m/Y H:i')); ?></td>
                                <td class="action-buttons">
                                    <a href="modifier_ticket.php?id=<?php echo $ticket['Id_TicketEvenement']; ?>" class="edit-btn">Modifier</a>
                                    <a href="supprimer_ticket.php?id=<?php echo $ticket['Id_TicketEvenement']; ?>" class="delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce ticket ?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <?php if (empty($message)): // Seulement si $message n'a pas déjà été défini par "aucun ticket" ?>
                    <p class="message info">Aucun ticket n'a été trouvé pour vos événements.</p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
