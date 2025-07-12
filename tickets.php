<?php
// Activez l'affichage des erreurs PHP pour le débogage (À DÉSACTIVER EN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Démarre la session pour accéder à l'ID de l'utilisateur connecté

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

$message = '';
$errors = []; // Pour stocker les erreurs spécifiques aux champs

// --- Récupération de l'ID de l'utilisateur connecté ---
$loggedInUserId = null;
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
} else {
    // Si l'utilisateur n'est pas connecté, affichez un message d'erreur et ne proposez pas le formulaire.
    $errors['auth'] = "Vous devez être connecté pour créer un ticket.";
    // Optionnel: Redirection
    // header("Location: login.php");
    // exit();
}

$evenements = [];
// Récupérer SEULEMENT les événements créés par l'utilisateur connecté
if ($loggedInUserId) {
    // Supposons que votre table 'evenement' a une colonne 'Id_Utilisateur' pour le créateur
$sqlEvent = "
    SELECT e.Id_Evenement, e.Titre
    FROM evenement e
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement
    WHERE c.Id_Utilisateur = ?
    ORDER BY e.Titre ASC
";
    if ($stmtEvent = $conn->prepare($sqlEvent)) {
        $stmtEvent->bind_param("i", $loggedInUserId);
        $stmtEvent->execute();
        $resultEvent = $stmtEvent->get_result();
        if ($resultEvent && $resultEvent->num_rows > 0) {
            while ($row = $resultEvent->fetch_assoc()) {
                $evenements[] = $row;
            }
        }
        $stmtEvent->close();
    } else {
        $errors['db_events'] = "Erreur lors de la préparation de la requête des événements : " . $conn->error;
    }

    // Si aucun événement n'est trouvé pour l'utilisateur, on peut ajouter une erreur
    if (empty($evenements) && empty($errors['db_events'])) {
        $errors['no_events'] = "Vous devez d'abord créer un événement pour pouvoir créer un ticket. <a href='creer_evenement.php'>Créer un événement</a>";
    }
}


// Traitement du formulaire si l'utilisateur est connecté et qu'il n'y a pas d'erreurs d'authentification ou d'événements
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($errors['auth']) && empty($errors['no_events'])) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = $_POST['prix'] ?? ''; // Laisser comme string pour la validation is_numeric
    $nombre = $_POST['nombre_disponible'] ?? '';
    $idEvenement = $_POST['id_evenement'] ?? '';

    // Validation
    if (empty($titre)) {
        $errors['titre'] = "Le titre du ticket est requis.";
    }
    // Description est optionnelle, donc pas de validation empty()

    if (empty($prix)) {
        $errors['prix'] = "Le prix est requis.";
    } elseif (!is_numeric($prix) || $prix < 0) {
        $errors['prix'] = "Le prix doit être un nombre positif.";
    }

    if (empty($nombre)) {
        $errors['nombre_disponible'] = "Le nombre disponible est requis.";
    } elseif (!filter_var($nombre, FILTER_VALIDATE_INT) || $nombre < 0) {
        $errors['nombre_disponible'] = "Le nombre disponible doit être un entier positif.";
    }

    if (empty($idEvenement)) {
        $errors['id_evenement'] = "Veuillez sélectionner un événement.";
    } elseif (!filter_var($idEvenement, FILTER_VALIDATE_INT) || $idEvenement <= 0) {
        $errors['id_evenement'] = "L'ID de l'événement est invalide.";
    } else {
        // Vérifier que l'événement sélectionné appartient bien à l'utilisateur connecté
        $eventBelongsToUser = false;
        foreach ($evenements as $event) {
            if ($event['Id_Evenement'] == $idEvenement) {
                $eventBelongsToUser = true;
                break;
            }
        }
        if (!$eventBelongsToUser) {
            $errors['id_evenement'] = "L'événement sélectionné n'est pas valide ou ne vous appartient pas.";
        }
    }


    // Si aucune erreur de validation, procéder à l'insertion
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO ticketevenement (Titre, Description, Prix, NombreDisponible, Id_Evenement) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            // 'ssdii' -> string, string, double (for price), integer, integer
            $stmt->bind_param("ssdii", $titre, $description, $prix, $nombre, $idEvenement);
            if ($stmt->execute()) {
                $message = '<p class="message success">🎉 Félicitations ! Vous avez créé le ticket avec succès.</p>';
                // Optionnel: Réinitialiser les champs du formulaire après succès
                $_POST = [];
            } else {
                $message = '<p class="message error">Erreur lors de l\'exécution de la requête : ' . $stmt->error . '</p>';
            }
            $stmt->close();
        } else {
            $message = '<p class="message error">Erreur lors de la préparation de la requête : ' . $conn->error . '</p>';
        }
    } else {
        $message = '<p class="message error">Veuillez corriger les erreurs dans le formulaire.</p>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un Nouveau Ticket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20px;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .input-monnaie {
            position: relative;
        }
        .input-monnaie input {
            padding-right: 50px;
        }
        .input-monnaie .suffix {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-weight: bold;
            pointer-events: none;
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            font-size: 16px;
            border-radius: 4px;
            margin-top: 20px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .message {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .error-message { /* Style pour les erreurs de validation de champ */
            color: red;
            font-size: 0.8em;
            margin-top: 5px;
            display: block;
        }
        .button-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 4px;
        }
        .button-link:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Créer un Nouveau Ticket</h2>

        <?php if (!empty($errors['auth'])): // Message si non connecté?>
            <div class="message error">
                <?php echo $errors['auth']; ?>
            </div>
            <p>Veuillez vous <a href="login.php">connecter</a> pour créer un ticket.</p>
        <?php elseif (!empty($errors['no_events'])): // Message si aucun événement n'est créé?>
            <div class="message error">
                <?php echo $errors['no_events']; ?>
            </div>
        <?php else: // Affiche le formulaire seulement si l'utilisateur est connecté et a des événements?>
            <?= $message ?>
            <form method="POST" action="tickets.php"> <label for="titre">Titre du Ticket :</label>
                <input type="text" id="titre" name="titre" required value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>">
                <?php if (isset($errors['titre'])): ?><span class="error-message"><?php echo $errors['titre']; ?></span><?php endif; ?>

                <label for="description">Description :</label>
                <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <?php // Description is optional, so no error message for empty here ?>

                <label for="prix">Prix :</label>
                <div class="input-monnaie">
                    <input type="number" id="prix" name="prix" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['prix'] ?? '') ?>">
                    <span class="suffix">CFA</span>
                </div>
                <?php if (isset($errors['prix'])): ?><span class="error-message"><?php echo $errors['prix']; ?></span><?php endif; ?>

                <label for="nombre_disponible">Nombre Disponible :</label>
                <input type="number" id="nombre_disponible" name="nombre_disponible" min="0" required value="<?= htmlspecialchars($_POST['nombre_disponible'] ?? '') ?>">
                <?php if (isset($errors['nombre_disponible'])): ?><span class="error-message"><?php echo $errors['nombre_disponible']; ?></span><?php endif; ?>

                <label for="id_evenement">Sélectionner un Événement :</label>
                <select id="id_evenement" name="id_evenement" required>
                    <option value="">-- Sélectionner un événement --</option>
                    <?php foreach ($evenements as $event): ?>
                        <option value="<?= htmlspecialchars($event['Id_Evenement']) ?>" <?= (($_POST['id_evenement'] ?? '') == $event['Id_Evenement']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['Titre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['id_evenement'])): ?><span class="error-message"><?php echo $errors['id_evenement']; ?></span><?php endif; ?>

                <button type="submit">Créer le Ticket</button>
            </form>
            <a href="liste_tickets.php" class="button-link">Voir la liste des tickets</a>
        <?php endif; // Fin du bloc conditionnel pour afficher le formulaire ?>
    </div>
</body>
</html>
<?php
// Fermer la connexion à la base de données à la fin de la page principale
$conn->close();
?>