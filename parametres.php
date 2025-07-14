<?php
// Activez l'affichage des erreurs PHP pour le débogage (À DÉSACTIVER EN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Démarre la session pour accéder à l'ID de l'utilisateur connecté

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

$userInfo = null; // Variable pour stocker les informations de l'utilisateur
$message = '';
$errors = []; // Pour stocker les erreurs de validation spécifiques aux champs

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || !isset($_SESSION['utilisateur']['id'])) {
    $message = '<p class="message error">Vous devez être connecté pour accéder à cette page.</p>';
    // Redirection vers la page de connexion si non connecté
    // header("Location: login.php");
    // exit();
} else {
    $loggedInUserId = $_SESSION['utilisateur']['id'];

    // --- Récupérer les informations actuelles de l'utilisateur ---
    $sql_fetch = "SELECT Nom, Prenom, Telephone, Email, MotDePasse, Photo, DateNaissance FROM utilisateur WHERE Id_Utilisateur = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $loggedInUserId);
        if ($stmt_fetch->execute()) {
            $result_fetch = $stmt_fetch->get_result();
            if ($result_fetch->num_rows > 0) {
                $userInfo = $result_fetch->fetch_assoc();
            } else {
                $message = '<p class="message error">Erreur : Profil utilisateur non trouvé.</p>';
            }
        } else {
            $message = '<p class="message error">Erreur lors de la récupération des données : ' . $stmt_fetch->error . '</p>';
        }
        $stmt_fetch->close();
    } else {
        $message = '<p class="message error">Erreur lors de la préparation de la requête de récupération : ' . $conn->error . '</p>';
    }

    // --- Traitement du formulaire de mise à jour ---
    if ($_SERVER["REQUEST_METHOD"] === "POST" && $userInfo) { // S'assurer que les infos sont chargées avant de traiter le POST
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmNewPassword = $_POST['confirm_new_password'] ?? '';

        // Validation des champs
        if (empty($nom)) {
            $errors['nom'] = "Le nom est requis.";
        }
        if (empty($prenom)) {
            $errors['prenom'] = "Le prénom est requis.";
        }
        if (empty($telephone)) {
            $errors['telephone'] = "Le téléphone est requis.";
        } elseif (!preg_match("/^[0-9]{8,15}$/", $telephone)) { // Exemple de validation pour un numéro de téléphone
            $errors['telephone'] = "Format de téléphone invalide.";
        }
        if (empty($email)) {
            $errors['email'] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Format d'email invalide.";
        }

        // --- Logique de changement de mot de passe ---
        if (!empty($newPassword)) { // Si l'utilisateur veut changer de mot de passe
            if (empty($currentPassword)) {
                $errors['current_password'] = "Veuillez entrer votre mot de passe actuel pour le modifier.";
            } elseif (!password_verify($currentPassword, $userInfo['MotDePasse'])) {
                $errors['current_password'] = "Le mot de passe actuel est incorrect.";
            }

            if (strlen($newPassword) < 6) { // Exemple de longueur minimale
                $errors['new_password'] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
            }
            if ($newPassword !== $confirmNewPassword) {
                $errors['confirm_new_password'] = "Les nouveaux mots de passe ne correspondent pas.";
            }
        }

        // --- Mise à jour des informations si aucune erreur ---
        if (empty($errors)) {
            $updateFields = [];
            $updateValues = [];
            $updateTypes = '';

            // Ajouter les champs modifiables
            if ($nom !== $userInfo['Nom']) {
                $updateFields[] = "Nom = ?";
                $updateValues[] = $nom;
                $updateTypes .= 's';
            }
            if ($prenom !== $userInfo['Prenom']) {
                $updateFields[] = "Prenom = ?";
                $updateValues[] = $prenom;
                $updateTypes .= 's';
            }
            if ($telephone !== $userInfo['Telephone']) {
                $updateFields[] = "Telephone = ?";
                $updateValues[] = $telephone;
                $updateTypes .= 's';
            }
            if ($email !== $userInfo['Email']) {
                $updateFields[] = "Email = ?";
                $updateValues[] = $email;
                $updateTypes .= 's';
                // Mettre à jour l'email dans la session si nécessaire
                $_SESSION['utilisateur']['email'] = $email;
            }

            // Ajouter le nouveau mot de passe haché si défini
            if (!empty($newPassword) && empty($errors['new_password'])) { // S'assurer qu'il n'y a pas d'erreur sur le nouveau mot de passe
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateFields[] = "MotDePasse = ?";
                $updateValues[] = $hashedPassword;
                $updateTypes .= 's';
            }

            // Si des champs ont été modifiés, construire et exécuter la requête de mise à jour
            if (!empty($updateFields)) {
                $sql_update = "UPDATE utilisateur SET " . implode(", ", $updateFields) . " WHERE Id_Utilisateur = ?";
                $updateValues[] = $loggedInUserId; // Ajouter l'ID de l'utilisateur à la fin des valeurs
                $updateTypes .= 'i'; // Ajouter le type pour l'ID de l'utilisateur

                if ($stmt_update = $conn->prepare($sql_update)) {
                    // Utiliser call_user_func_array pour bind_param car les valeurs sont variables

                  $refs = [];
                 foreach ($updateValues as $key => $value) {
                  $refs[$key] = &$updateValues[$key];
                       }
                     call_user_func_array([$stmt_update, 'bind_param'], array_merge([$updateTypes], $refs));

                    if ($stmt_update->execute()) {
                        $message = '<p class="message success">Vos informations ont été mises à jour avec succès !</p>';
                        // Recharger les informations de l'utilisateur après la mise à jour
                        $userInfo['Nom'] = $nom;
                        $userInfo['Prenom'] = $prenom;
                        $userInfo['Telephone'] = $telephone;
                        $userInfo['Email'] = $email;
                        if (!empty($newPassword)) {
                             $userInfo['MotDePasse'] = $hashedPassword; // Mettre à jour le mot de passe haché en mémoire
                        }
                    } else {
                        $message = '<p class="message error">Erreur lors de la mise à jour : ' . $stmt_update->error . '</p>';
                    }
                    $stmt_update->close();
                } else {
                    $message = '<p class="message error">Erreur lors de la préparation de la requête de mise à jour : ' . $conn->error . '</p>';
                }
            } else {
                $message = '<p class="message info">Aucune modification détectée.</p>';
            }
        } else {
            $message = '<p class="message error">Veuillez corriger les erreurs dans le formulaire.</p>';
        }
    }
}

// Fermer la connexion à la base de données
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du Profil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .message {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        input[type="tel"] { /* Ajouté tel pour le téléphone */
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Pour inclure padding et border dans la largeur */
        }
        .error-message {
            color: red;
            font-size: 0.85em;
            margin-top: -8px;
            margin-bottom: 10px;
            display: block;
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            margin-top: 20px;
            width: 100%;
        }
        button:hover {
            background-color: #218838;
        }
        .back-button {
            display: block;
            text-align: center;
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 10px;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
        .profile-photo-upload {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-photo-upload img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Paramètres du Profil</h1>

        <?php echo $message; ?>

        <?php if ($userInfo): ?>
            <form action="parametres.php" method="POST" enctype="multipart/form-data">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? $userInfo['Nom']) ?>" required>
                <?php if (isset($errors['nom'])): ?><span class="error-message"><?= $errors['nom'] ?></span><?php endif; ?>

                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? $userInfo['Prenom']) ?>" required>
                <?php if (isset($errors['prenom'])): ?><span class="error-message"><?= $errors['prenom'] ?></span><?php endif; ?>

                <label for="telephone">Téléphone :</label>
                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? $userInfo['Telephone']) ?>" required>
                <?php if (isset($errors['telephone'])): ?><span class="error-message"><?= $errors['telephone'] ?></span><?php endif; ?>

                <label for="email">Email :</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $userInfo['Email']) ?>" required>
                <?php if (isset($errors['email'])): ?><span class="error-message"><?= $errors['email'] ?></span><?php endif; ?>

                <h2>Changer le mot de passe</h2>
                <p>Laissez ces champs vides si vous ne souhaitez pas changer votre mot de passe.</p>

                <label for="current_password">Mot de passe actuel :</label>
                <input type="password" id="current_password" name="current_password">
                <?php if (isset($errors['current_password'])): ?><span class="error-message"><?= $errors['current_password'] ?></span><?php endif; ?>

                <label for="new_password">Nouveau mot de passe :</label>
                <input type="password" id="new_password" name="new_password">
                <?php if (isset($errors['new_password'])): ?><span class="error-message"><?= $errors['new_password'] ?></span><?php endif; ?>

                <label for="confirm_new_password">Confirmer le nouveau mot de passe :</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password">
                <?php if (isset($errors['confirm_new_password'])): ?><span class="error-message"><?= $errors['confirm_new_password'] ?></span><?php endif; ?>

                <button type="submit">Enregistrer les modifications</button>
            </form>
            <a href="menu_utilisateur.php" class="back-button">Retour au Menu</a>
        <?php else: ?>
            <p class="message info">Veuillez vous connecter pour gérer vos paramètres.</p>
            <a href="login.php" class="back-button">Se connecter</a>
        <?php endif; ?>
    </div>
</body>
</html>
