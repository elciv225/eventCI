<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'base.php';

$message = '';
$errors = [];
$evenementId = null;

// Vérifie si l'utilisateur est connecté
$loggedInUserId = $_SESSION['user_id'] ?? null;
if (!$loggedInUserId) {
    $errors['auth'] = "Vous devez être connecté pour créer un événement.";
}

// Chargement des catégories et des villes
$categories = [];
$villes = [];

$result_categories = $conn->query("SELECT Id_CategorieEvenement, Libelle FROM categorieevenement ORDER BY Libelle");
if ($result_categories) {
    while ($row = $result_categories->fetch_assoc()) $categories[] = $row;
}

$result_villes = $conn->query("SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle");
if ($result_villes) {
    while ($row = $result_villes->fetch_assoc()) $villes[] = $row;
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$errors['auth']) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $dateDebut = $_POST['dateDebut'] ?? '';
    $dateFin = $_POST['dateFin'] ?? '';
    $idVille = $_POST['idVille'] ?? '';
    $idCategorieEvenement = $_POST['idCategorieEvenement'] ?? '';

    // Validation des champs
    if (!$titre) $errors['titre'] = "Le titre est requis.";
    if (!$description) $errors['description'] = "La description est requise.";
    if (!$adresse) $errors['adresse'] = "L'adresse est requise.";
    if (!$dateDebut) $errors['dateDebut'] = "La date de début est requise.";
    if (!$dateFin) $errors['dateFin'] = "La date de fin est requise.";
    if (!$idVille) $errors['idVille'] = "La ville est requise.";
    if (!$idCategorieEvenement) $errors['idCategorieEvenement'] = "La catégorie est requise.";

    if ($dateDebut && $dateFin) {
        try {
            $dtDebut = new DateTime($dateDebut);
            $dtFin = new DateTime($dateFin);
            if ($dtDebut >= $dtFin) {
                $errors['dateFin'] = "La date de fin doit être postérieure à la date de début.";
            }
        } catch (Exception $e) {
            $errors['date_format'] = "Format de date invalide.";
        }
    }

    // Upload des images
    $uploadedImageFiles = [];
    $targetDir = "image/";

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        $errors['images'][] = "Impossible de créer le dossier image/.";
    }

    if (empty($errors['images']) && isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
        $maxFileSize = 5 * 1024 * 1024;

        foreach ($_FILES['images']['name'] as $i => $fileName) {
            $fileTmpName = $_FILES['images']['tmp_name'][$i];
            $fileSize = $_FILES['images']['size'][$i];
            $fileError = $_FILES['images']['error'][$i];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileError === 0 && in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
                $newFileName = uniqid() . '_' . basename($fileName);
                $targetFilePath = $targetDir . $newFileName;

                if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                    $uploadedImageFiles[] = $targetFilePath;
                } else {
                    $errors['images'][] = "Échec du téléchargement de $fileName.";
                }
            }
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Insère l'événement avec statut 'en_attente'
            $statut = 'en_attente';
            $stmt = $conn->prepare("INSERT INTO evenement (Titre, Description, Adresse, DateDebut, DateFin, Id_Ville, Id_CategorieEvenement, statut_approbation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssis", $titre, $description, $adresse, $dateDebut, $dateFin, $idVille, $idCategorieEvenement, $statut);
            if (!$stmt->execute()) throw new Exception("Erreur événement : " . $stmt->error);
            $evenementId = $conn->insert_id;
            $stmt->close();

            // Liaison utilisateur → événement
            $stmt_creer = $conn->prepare("INSERT INTO creer (Id_Utilisateur, Id_Evenement, DateCreation) VALUES (?, ?, NOW())");
            $stmt_creer->bind_param("ii", $loggedInUserId, $evenementId);
            if (!$stmt_creer->execute()) throw new Exception("Erreur lien événement : " . $stmt_creer->error);
            $stmt_creer->close();

            // Insertion des images
            if ($uploadedImageFiles) {
                $stmt_img = $conn->prepare("INSERT INTO imageevenement (Titre, Description, Lien, Id_Evenement) VALUES (?, ?, ?, ?)");
                foreach ($uploadedImageFiles as $img) {
                    $imgTitre = "Image pour " . $titre;
                    $imgDesc = "Illustration de l'événement : " . $titre;
                    $stmt_img->bind_param("sssi", $imgTitre, $imgDesc, $img, $evenementId);
                    if (!$stmt_img->execute()) throw new Exception("Erreur image : " . $stmt_img->error);
                }
                $stmt_img->close();
            }

            $conn->commit();
            header("Location: liste_evenement.php?msg=created_success");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            foreach ($uploadedImageFiles as $img) if (file_exists($img)) unlink($img);
            $message = "Erreur : " . $e->getMessage();
        }
    } else {
        $message = "Veuillez corriger les erreurs ci-dessous.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Nouvel Événement</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles pour les messages */
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
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

        .error-message {
            color: red;
            font-size: 0.8em;
            margin-top: 5px;
            display: block;
        }

        /* Styles du formulaire */
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="datetime-local"],
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button[type="submit"] {
            background-color: #28a745;
            /* Vert */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }

        .back-button {
            background-color: #6c757d;
            /* Gris */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            /* Pour que le lien ressemble à un bouton */
            display: inline-block;
            /* Pour qu'il puisse être stylisé comme un bouton */
        }

        .back-button:hover {
            background-color: #5a6268;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            /* Pour espacer les boutons */
            align-items: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Créer un Nouvel Événement</h1>

        <?php if (!empty($errors['auth'])): // Affiche le message si l'utilisateur n'est pas connecté ?>
            <div class="message error">
                <?php echo $errors['auth']; ?>
            </div>
            <p>Veuillez vous <a href="login.php">connecter</a> pour créer un événement.</p>
        <?php else: // Affiche le formulaire seulement si l'utilisateur est connecté ?>
            <?php if (!empty($message)): ?>
                <div class="message <?php echo (strpos($message, 'succès') !== false) ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"
                enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titre">Titre de l'événement :</label>
                    <input type="text" id="titre" name="titre"
                        value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>" required>
                    <?php if (isset($errors['titre'])): ?><span
                            class="error-message"><?php echo $errors['titre']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description" rows="5"
                        required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <?php if (isset($errors['description'])): ?><span
                            class="error-message"><?php echo $errors['description']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse :</label>
                    <input type="text" id="adresse" name="adresse"
                        value="<?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?>" required>
                    <?php if (isset($errors['adresse'])): ?><span
                            class="error-message"><?php echo $errors['adresse']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="dateDebut">Date et heure de début :</label>
                    <input type="datetime-local" id="dateDebut" name="dateDebut"
                        value="<?php echo htmlspecialchars($_POST['dateDebut'] ?? ''); ?>" required>
                    <?php if (isset($errors['dateDebut'])): ?><span
                            class="error-message"><?php echo $errors['dateDebut']; ?></span><?php endif; ?>
                    <?php if (isset($errors['date_format'])): ?><span
                            class="error-message"><?php echo $errors['date_format']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="dateFin">Date et heure de fin :</label>
                    <input type="datetime-local" id="dateFin" name="dateFin"
                        value="<?php echo htmlspecialchars($_POST['dateFin'] ?? ''); ?>" required>
                    <?php if (isset($errors['dateFin'])): ?><span
                            class="error-message"><?php echo $errors['dateFin']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="idVille">Ville :</label>
                    <select id="idVille" name="idVille" required>
                        <option value="">Sélectionnez une ville</option>
                        <?php foreach ($villes as $ville): ?>
                            <option value="<?php echo htmlspecialchars($ville['Id_Ville']); ?>" <?php echo (isset($_POST['idVille']) && $_POST['idVille'] == $ville['Id_Ville']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ville['Libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['idVille'])): ?><span
                            class="error-message"><?php echo $errors['idVille']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="idCategorieEvenement">Catégorie :</label>
                    <select id="idCategorieEvenement" name="idCategorieEvenement" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo htmlspecialchars($categorie['Id_CategorieEvenement']); ?>" <?php echo (isset($_POST['idCategorieEvenement']) && $_POST['idCategorieEvenement'] == $categorie['Id_CategorieEvenement']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categorie['Libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['idCategorieEvenement'])): ?><span
                            class="error-message"><?php echo $errors['idCategorieEvenement']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="images">Image(s) de l'événement :</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                    <small>Vous pouvez sélectionner plusieurs images (JPG, JPEG, PNG, GIF, Max 5MB par image).</small>
                    <?php
                    // Affiche les erreurs d'images si elles existent
                    if (isset($errors['images']) && is_array($errors['images'])):
                        foreach ($errors['images'] as $imageError): ?>
                            <span class="error-message"><?php echo $imageError; ?></span><br>
                        <?php endforeach;
                    endif;
                    ?>
                </div>

                <div class="form-actions">
                    <button type="submit">Créer l'événement</button>
                    <a href="liste_evenement.php" class="back-button">Retour à la liste</a>
                </div>
            </form>
        <?php endif; // Fin du bloc conditionnel pour afficher le formulaire ?>
    </div>
</body>

</html>
<?php
// Fermer la connexion à la base de données à la fin de la page principale
$conn->close();
?>