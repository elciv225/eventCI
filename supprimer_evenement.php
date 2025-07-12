<?php
// Activez l'affichage des erreurs PHP pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

$message = '';
$evenementId = null;

// --- 1. Récupération de l'ID de l'événement depuis l'URL ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $evenementId = $_GET['id'];

    // Démarrer une transaction pour assurer l'atomicité
    $conn->begin_transaction();

    try {
        // --- 2. Récupérer les liens des images associées avant de les supprimer de la BD ---
        $image_links_to_delete = [];
        $sql_select_images = "SELECT Lien FROM imageevenement WHERE Id_Evenement = ?";
        if ($stmt_select_images = $conn->prepare($sql_select_images)) {
            $stmt_select_images->bind_param("i", $evenementId);
            $stmt_select_images->execute();
            $result_images = $stmt_select_images->get_result();
            while ($row = $result_images->fetch_assoc()) {
                $image_links_to_delete[] = $row['Lien'];
            }
            $stmt_select_images->close();
        } else {
            throw new Exception("Erreur de préparation pour la sélection des images : " . $conn->error);
        }

        // --- 3. Supprimer les entrées d'images de la table 'imageevenement' ---
        $sql_delete_images = "DELETE FROM imageevenement WHERE Id_Evenement = ?";
        if ($stmt_delete_images = $conn->prepare($sql_delete_images)) {
            $stmt_delete_images->bind_param("i", $evenementId);
            if (!$stmt_delete_images->execute()) {
                throw new Exception("Erreur lors de la suppression des images de la base de données : " . $stmt_delete_images->error);
            }
            $stmt_delete_images->close();
        } else {
            throw new Exception("Erreur de préparation pour la suppression des images : " . $conn->error);
        }

        // --- 4. Supprimer l'événement de la table 'evenement' ---
        $sql_delete_evenement = "DELETE FROM evenement WHERE Id_Evenement = ?";
        if ($stmt_delete_evenement = $conn->prepare($sql_delete_evenement)) {
            $stmt_delete_evenement->bind_param("i", $evenementId);
            if (!$stmt_delete_evenement->execute()) {
                throw new Exception("Erreur lors de la suppression de l'événement de la base de données : " . $stmt_delete_evenement->error);
            }
            // Vérifier si une ligne a bien été affectée
            if ($stmt_delete_evenement->affected_rows === 0) {
                throw new Exception("Événement non trouvé ou déjà supprimé.");
            }
            $stmt_delete_evenement->close();
        } else {
            throw new Exception("Erreur de préparation pour la suppression de l'événement : " . $conn->error);
        }

        // Si toutes les opérations de base de données réussissent, on valide la transaction
        $conn->commit();
        $message = "Événement et ses images supprimés avec succès !";

        // --- 5. Supprimer les fichiers d'images physiques après la suppression réussie en BD ---
        foreach ($image_links_to_delete as $link) {
            // Assurez-vous que le lien est un chemin valide et sécurisé pour éviter de supprimer des fichiers système
            // Par exemple, ne pas permettre la suppression de "../../../config.php"
            $absolutePath = realpath($link); // Convertit le chemin en chemin absolu et vérifie l'existence
            $targetDirPrefix = realpath("image/"); // Chemin absolu du dossier "image/"

            // Ne supprimer que si le fichier est dans le dossier "image/"
            if ($absolutePath && strpos($absolutePath, $targetDirPrefix) === 0 && file_exists($absolutePath)) {
                if (!unlink($absolutePath)) {
                    // Si la suppression physique échoue, loggez l'erreur mais ne pas annuler la transaction BD
                    error_log("Erreur lors de la suppression physique du fichier : " . $absolutePath);
                    $message .= " Cependant, une ou plusieurs images n'ont pas pu être supprimées physiquement.";
                }
            } else {
                 error_log("Tentative de suppression de fichier hors répertoire autorisé ou fichier inexistant: " . $link);
            }
        }
        
        // Redirection vers la liste des événements
        header("Location: liste_evenement.php?msg=deleted_success");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Annuler la transaction en cas d'erreur
        $message = "Erreur lors de la suppression de l'événement : " . $e->getMessage();
        // Redirection vers la liste des événements avec un message d'erreur
        header("Location: liste_evenement.php?msg=deleted_error&error=" . urlencode($message));
        exit();
    }
} else {
    $message = "ID d'événement non spécifié ou invalide.";
    header("Location: liste_evenement.php?msg=deleted_error&error=" . urlencode($message));
    exit();
}

$conn->close(); // Fermer la connexion (normalement déjà fait par exit() mais bonne pratique)
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppression d'événement</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            width: 60%;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            text-align: center;
        }
        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-size: 1.1em;
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
        .back-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            margin-top: 20px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, 'succès') !== false) ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            <a href="liste_evenement.php" class="back-button">Retour à la liste des événements</a>
        <?php endif; ?>
    </div>
</body>
</html>