<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

$tickets = [];
$message = '';
$message_type = '';

// Récupérer les messages de l'URL si redirection depuis une action (suppression, modification, ajout)
if (isset($_GET['msg']) && isset($_GET['text'])) {
    $message_type = htmlspecialchars($_GET['msg']);
    $message = htmlspecialchars($_GET['text']);
}

// Requête pour récupérer tous les tickets avec le titre de l'événement associé
$sql = "SELECT
            te.Id_TicketEvenement,
            te.Titre AS TicketTitre,
            te.Description AS TicketDescription,
            te.Prix,
            te.NombreDisponible,
            te.Id_Evenement,
            e.Titre AS EvenementTitre
        FROM
            ticketevenement te
        JOIN
            evenement e ON te.Id_Evenement = e.Id_Evenement
        ORDER BY
            e.Titre, te.Titre ASC"; // Tri par événement puis par titre de ticket

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
} else {
    $message = "Aucun ticket trouvé.";
    $message_type = "info"; // Utiliser 'info' pour les messages sans erreur
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste de tous les Tickets</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }
        .container {
            width: 90%;
            margin: 30px auto;
            max-width: 1200px;
            padding: 0 15px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 40px;
            font-size: 2.5em;
            font-weight: 600;
        }
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1em;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 20px 0;
        }

        .ticket-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .card-content {
            padding: 20px;
            flex-grow: 1;
        }

        .card-content h2 {
            font-size: 1.5em;
            margin-top: 0;
            margin-bottom: 10px;
            color: #34495e;
            font-weight: 600;
        }
        .card-content h3 {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .card-content p {
            font-size: 0.95em;
            color: #666;
            margin-bottom: 8px;
        }
        .card-content .price {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745; /* Vert pour le prix */
            margin-top: 15px;
        }

        .card-actions {
            padding: 0 20px 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end; /* Alignement à droite des boutons */
            margin-top: auto;
        }
        .action-button {
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            text-align: center;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .edit-button {
            background-color: #ffc107; /* Jaune */
            color: #333;
        }
        .edit-button:hover {
            background-color: #e0a800;
            transform: translateY(-1px);
        }
        .delete-button {
            background-color: #dc3545; /* Rouge */
            color: white;
        }
        .delete-button:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }
        .details-event-button {
            background-color: #007bff; /* Bleu */
            color: white;
        }
        .details-event-button:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .add-button-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .add-button {
            display: inline-block;
            background-color: #007bff; /* Bleu vif pour l'ajout */
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
        }
        .add-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        /* Pour un retour à la liste des événements */
        .back-to-events {
            display: block;
            text-align: center;
            margin-top: 30px;
        }
        .back-to-events a {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-to-events a:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Liste de tous les Tickets</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($tickets)): ?>
            <div class="ticket-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card">
                        <div class="card-content">
                            <h2><?php echo htmlspecialchars($ticket['TicketTitre']); ?></h2>
                            <h3>Pour l'événement : <a href="details_evenement.php?id=<?php echo htmlspecialchars($ticket['Id_Evenement']); ?>"><?php echo htmlspecialchars($ticket['EvenementTitre']); ?></a></h3>
                            <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($ticket['TicketDescription'])); ?></p>
                            <p><strong>Nombre Disponible :</strong> <?php echo htmlspecialchars($ticket['NombreDisponible']); ?></p>
                            <p class="price">Prix : <?php echo htmlspecialchars(number_format($ticket['Prix'], 2, ',', ' ')); ?> €</p>
                        </div>
                        <div class="card-actions">
                            <a href="modifier_ticket.php?id=<?php echo htmlspecialchars($ticket['Id_TicketEvenement']); ?>" class="action-button edit-button">Modifier</a>
                            <a href="supprimer_ticket.php?id=<?php echo htmlspecialchars($ticket['Id_TicketEvenement']); ?>" class="action-button delete-button" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce type de ticket ?');">Supprimer</a>
                            <a href="details_evenement.php?id=<?php echo htmlspecialchars($ticket['Id_Evenement']); ?>" class="action-button details-event-button">Voir Événement</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #777;">Aucun ticket n'a été créé pour le moment.</p>
        <?php endif; ?>

        <div class="back-to-events">
            <a href="liste_evenement.php">Retour à la liste des événements</a>
        </div>
    </div>
</body>
</html>