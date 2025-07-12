<?php
session_start();
require_once 'base.php';

// 🛡️ Sécurisation : vérifier si l'utilisateur est créateur ou administrateur
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['is_admin_fixed'] ?? false;
$evenementId = $_GET['id'] ?? null;

$evenement = null;
$message = '';
$errors = [];

if (!$evenementId || !is_numeric($evenementId)) {
    $message = "ID d'événement invalide.";
} else {
    // Si pas admin, vérifier que l'utilisateur est créateur
    if (!$is_admin) {
        $stmtCheck = $conn->prepare("SELECT 1 FROM creer WHERE Id_Evenement = ? AND Id_Utilisateur = ?");
        $stmtCheck->bind_param("ii", $evenementId, $user_id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        if ($resultCheck->num_rows === 0) {
            echo "<p style='color:red;padding:20px;'>⛔ Accès refusé : vous n’êtes pas autorisé à modifier cet événement.</p>";
            exit;
        }
        $stmtCheck->close();
    }

    // 🧾 Charger les infos de l'événement
    $stmt = $conn->prepare("SELECT 
        e.Id_Evenement, e.Titre, e.Description, e.Adresse, e.DateDebut, e.DateFin, e.Id_Ville, e.Id_CategorieEvenement,
        (SELECT Lien FROM imageevenement WHERE Id_Evenement = e.Id_Evenement ORDER BY Id_ImageEvenement LIMIT 1) AS CurrentImageLien
        FROM evenement e WHERE Id_Evenement = ?");
    $stmt->bind_param("i", $evenementId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $evenement = $result->fetch_assoc();
    } else {
        $message = "Événement non trouvé.";
        $evenementId = null;
    }
    $stmt->close();
}

// 🛠️ Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST" && $evenementId !== null) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $dateDebut = $_POST['dateDebut'] ?? '';
    $dateFin = $_POST['dateFin'] ?? '';
    $idVille = $_POST['idVille'] ?? '';
    $idCategorieEvenement = $_POST['idCategorieEvenement'] ?? '';
    $imageUploadedFileName = $evenement['CurrentImageLien'] ?? '';
    $targetDir = "image/";

    // 🔍 Validation
    if (empty($titre)) $errors['titre'] = "Titre requis";
    if (empty($description)) $errors['description'] = "Description requise";
    if (empty($adresse)) $errors['adresse'] = "Adresse requise";
    if (empty($dateDebut)) $errors['dateDebut'] = "Date de début requise";
    if (empty($dateFin)) $errors['dateFin'] = "Date de fin requise";
    if (empty($idVille)) $errors['idVille'] = "Ville requise";
    if (empty($idCategorieEvenement)) $errors['idCategorieEvenement'] = "Catégorie requise";

    if (!empty($dateDebut) && !empty($dateFin)) {
        try {
            $dtDebut = new DateTime($dateDebut);
            $dtFin = new DateTime($dateFin);
            if ($dtDebut >= $dtFin) {
                $errors['dateFin'] = "La fin doit être après le début";
            }
        } catch (Exception $e) {
            $errors['dates'] = "Format de date invalide";
        }
    }

    // 📸 Upload image
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $fileName = uniqid() . '_' . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileType, $allowTypes)) {
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $errors['image'] = "Image trop lourde (>5MB)";
            } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                if (!empty($imageUploadedFileName) && file_exists($imageUploadedFileName)) {
                    unlink($imageUploadedFileName);
                }
                $imageUploadedFileName = $targetFilePath;
            } else {
                $errors['image'] = "Erreur d’upload image";
            }
        } else {
            $errors['image'] = "Format non autorisé";
        }
    }

    // 🧩 Mise à jour en base
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE evenement SET Titre=?, Description=?, Adresse=?, DateDebut=?, DateFin=?, Id_Ville=?, Id_CategorieEvenement=? WHERE Id_Evenement=?");
            $stmt->bind_param("sssssiii", $titre, $description, $adresse, $dateDebut, $dateFin, $idVille, $idCategorieEvenement, $evenementId);
            $stmt->execute();
            $stmt->close();

            // ⚡ Image : update ou insert
            if (!empty($imageUploadedFileName)) {
                $stmtCheck = $conn->prepare("SELECT Id_ImageEvenement FROM imageevenement WHERE Id_Evenement = ?");
                $stmtCheck->bind_param("i", $evenementId);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();

                if ($resultCheck->num_rows > 0) {
                    $imageId = $resultCheck->fetch_assoc()['Id_ImageEvenement'];
                    $stmtUpdate = $conn->prepare("UPDATE imageevenement SET Lien=? WHERE Id_ImageEvenement=?");
                    $stmtUpdate->bind_param("si", $imageUploadedFileName, $imageId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                } else {
                    $stmtInsert = $conn->prepare("INSERT INTO imageevenement (Titre, Description, Lien, Id_Evenement) VALUES (?, ?, ?, ?)");
                    $imageTitre = "Image pour " . $titre;
                    $imageDesc = "Image mise à jour";
                    $stmtInsert->bind_param("sssi", $imageTitre, $imageDesc, $imageUploadedFileName, $evenementId);
                    $stmtInsert->execute();
                    $stmtInsert->close();
                }
                $stmtCheck->close();
            }

            $conn->commit();
            header("Location: modifier_evenement.php?id=" . $evenementId . "&msg=success");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            if (!empty($targetFilePath) && file_exists($targetFilePath)) unlink($targetFilePath);
            $message = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    } else {
        $message = "Des erreurs sont présentes dans le formulaire.";
    }
}

// 📚 Villes et catégories
$categories = [];
$villes = [];

$resCat = $conn->query("SELECT Id_CategorieEvenement, Libelle FROM categorieevenement ORDER BY Libelle");
if ($resCat) while ($row = $resCat->fetch_assoc()) $categories[] = $row;

$resVille = $conn->query("SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle");
if ($resVille) while ($row = $resVille->fetch_assoc()) $villes[] = $row;

if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
    $message = "✅ Événement modifié avec succès.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Événement</title>
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
        .current-image-preview {
            max-width: 200px;
            height: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            background-color: #f9f9f9;
        }
        .form-actions {
            display: flex;
            justify-content: space-between; /* Pour espacer les boutons */
            align-items: center;
            margin-top: 20px;
        }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        .back-button {
            background-color: #6c757d; /* Gris */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none; /* Pour que le lien ressemble à un bouton */
            display: inline-block; /* Pour qu'il puisse être stylisé comme un bouton */
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modifier l'Événement</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, 'succès') !== false) ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($evenementId === null && empty($message)): ?>
            <div class="message error">
                Impossible de charger l'événement. Veuillez revenir à la <a href="liste_evenements.php">liste des événements</a>.
            </div>
        <?php elseif ($evenement !== null): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . htmlspecialchars($evenementId); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="evenementId" value="<?php echo htmlspecialchars($evenement['Id_Evenement']); ?>">

                <div class="form-group">
                    <label for="titre">Titre de l'événement :</label>
                    <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($_POST['titre'] ?? $evenement['Titre'] ?? ''); ?>" required>
                    <?php if (isset($errors['titre'])): ?><span class="error-message"><?php echo $errors['titre']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($_POST['description'] ?? $evenement['Description'] ?? ''); ?></textarea>
                    <?php if (isset($errors['description'])): ?><span class="error-message"><?php echo $errors['description']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse :</label>
                    <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($_POST['adresse'] ?? $evenement['Adresse'] ?? ''); ?>" required>
                    <?php if (isset($errors['adresse'])): ?><span class="error-message"><?php echo $errors['adresse']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="dateDebut">Date et heure de début :</label>
                    <input type="datetime-local" id="dateDebut" name="dateDebut" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($_POST['dateDebut'] ?? $evenement['DateDebut'] ?? ''))); ?>" required>
                    <?php if (isset($errors['dateDebut'])): ?><span class="error-message"><?php echo $errors['dateDebut']; ?></span><?php endif; ?>
                    <?php if (isset($errors['date_format'])): ?><span class="error-message"><?php echo $errors['date_format']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="dateFin">Date et heure de fin :</label>
                    <input type="datetime-local" id="dateFin" name="dateFin" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($_POST['dateFin'] ?? $evenement['DateFin'] ?? ''))); ?>" required>
                    <?php if (isset($errors['dateFin'])): ?><span class="error-message"><?php echo $errors['dateFin']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="idVille">Ville :</label>
                    <select id="idVille" name="idVille" required>
                        <option value="">Sélectionnez une ville</option>
                        <?php foreach ($villes as $ville): ?>
                            <option value="<?php echo htmlspecialchars($ville['Id_Ville']); ?>"
                                <?php echo (isset($_POST['idVille']) && $_POST['idVille'] == $ville['Id_Ville']) || ($evenement['Id_Ville'] == $ville['Id_Ville'] && !isset($_POST['idVille'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ville['Libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['idVille'])): ?><span class="error-message"><?php echo $errors['idVille']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="idCategorieEvenement">Catégorie :</label>
                    <select id="idCategorieEvenement" name="idCategorieEvenement" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo htmlspecialchars($categorie['Id_CategorieEvenement']); ?>"
                                <?php echo (isset($_POST['idCategorieEvenement']) && $_POST['idCategorieEvenement'] == $categorie['Id_CategorieEvenement']) || ($evenement['Id_CategorieEvenement'] == $categorie['Id_CategorieEvenement'] && !isset($_POST['idCategorieEvenement'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categorie['Libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['idCategorieEvenement'])): ?><span class="error-message"><?php echo $errors['idCategorieEvenement']; ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Image actuelle :</label>
                    <?php if (!empty($evenement['CurrentImageLien'])): ?>
                        <img src="<?php echo htmlspecialchars($evenement['CurrentImageLien']); ?>" alt="Image actuelle de l'événement" class="current-image-preview">
                        <p><small>Lien: <?php echo htmlspecialchars($evenement['CurrentImageLien']); ?></small></p>
                    <?php else: ?>
                        <p>Aucune image associée actuellement.</p>
                    <?php endif; ?>
                    
                    <label for="image" style="margin-top: 15px;">Changer l'image (optionnel) :</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small>Fichiers acceptés : JPG, JPEG, PNG, GIF (Max 5MB). Laissez vide pour conserver l'image actuelle.</small>
                    <?php if (isset($errors['image_upload'])): ?><span class="error-message"><?php echo $errors['image_upload']; ?></span><?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit">Mettre à jour l'événement</button>
                    <a href="liste_evenement.php" class="back-button">Retour à la liste</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>