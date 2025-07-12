<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'base.php';

$id_evenement = $_GET['id'] ?? null;
$evenement_titre = '';
$message = '';
$message_type = '';

if ($id_evenement) {
    // Récupérer le titre de l'événement pour l'affichage
    $stmt_event = $conn->prepare("SELECT Titre FROM evenement WHERE Id_Evenement = ?");
    $stmt_event->bind_param("i", $id_evenement);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();
    if ($result_event->num_rows > 0) {
        $evenement_titre = $result_event->fetch_assoc()['Titre'];
    } else {
        $message = "Événement introuvable.";
        $message_type = "error";
        $id_evenement = null; // Empêche l'affichage du formulaire si l'événement n'existe pas
    }
    $stmt_event->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $id_evenement) {
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $prix = $_POST['prix'] ?? 0;
    $nombre_disponible = $_POST['nombre_disponible'] ?? 0;

    // Validation simple
    if (empty($titre) || empty($prix) || !is_numeric($prix) || $prix < 0 || !is_numeric($nombre_disponible) || $nombre_disponible < 0) {
        $message = "Veuillez remplir tous les champs obligatoires correctement (Titre, Prix, Nombre Disponible).";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO ticketevenement (Titre, Description, Prix, NombreDisponible, Id_Evenement) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssddi", $titre, $description, $prix, $nombre_disponible, $id_evenement);
            if ($stmt->execute()) {
                $message = "Ticket ajouté avec succès !";
                $message_type = "success";
                // Réinitialiser les champs du formulaire après succès
                $titre = '';
                $description = '';
                $prix = '';
                $nombre_disponible = '';
            } else {
                $message = "Erreur lors de l'ajout du ticket : " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Erreur de préparation de la requête : " . $conn->error;
            $message_type = "error";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Ticket pour <?php echo htmlspecialchars($evenement_titre); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 2em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: calc(100% - 20px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-submit {
            flex-grow: 1;
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
        }
        .btn-submit:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .btn-back {
            flex-grow: 1;
            background-color: #6c757d; /* Gris */
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 1.1em;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
        }
        .btn-back:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Ajouter un Ticket pour<br><?php echo htmlspecialchars($evenement_titre); ?></h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($id_evenement): ?>
            <form action="ajouter_ticket.php?id=<?php echo htmlspecialchars($id_evenement); ?>" method="POST">
                <div class="form-group">
                    <label for="titre">Titre du Ticket :</label>
                    <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($titre ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="prix">Prix (€ ou devise locale) :</label>
                    <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?php echo htmlspecialchars($prix ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="nombre_disponible">Nombre Disponible :</label>
                    <input type="number" id="nombre_disponible" name="nombre_disponible" min="0" value="<?php echo htmlspecialchars($nombre_disponible ?? ''); ?>" required>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn-submit">Ajouter le Ticket</button>
                    <a href="details_evenement.php?id=<?php echo htmlspecialchars($id_evenement); ?>" class="btn-back">Retour aux détails de l'événement</a>
                </div>
            </form>
        <?php else: ?>
            <div class="button-group">
                <a href="liste_evenement.php" class="btn-back">Retour à la liste des événements</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>