<?php
session_start();
require_once 'config/base.php';
if (!isset($conn)) {
    custom_die("Vérifier l'import de la base.php");
}
// Identifiants admin fixes
$admin_username = "admin"; // À changer pour un nom d'utilisateur sécurisé
$admin_password_hash = password_hash("admin", PASSWORD_DEFAULT); // CHANGEZ CECI !

// Initialiser les messages d'erreur
$message_erreur = '';

// Récupérer les messages d'erreur depuis la session
if (isset($_SESSION['error'])) {
    $message_erreur = $_SESSION['error'];
    unset($_SESSION['error']); // Effacer le message après l'avoir récupéré
}
if (isset($_SESSION['success'])) {
    $message_erreur = "✅ " . $_SESSION['success'];
    unset($_SESSION['success']); // Effacer le message après l'avoir récupéré
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérification de l'administrateur
    if ($email === $admin_username && password_verify($password, $admin_password_hash)) {
        $_SESSION['utilisateur']['id'] = -1; // Un ID fictif pour l'admin (non issu de la BDD)
        $_SESSION['utilisateur']['email'] = $admin_username; // Ou un email générique pour l'admin
        $_SESSION['is_admin_fixed'] = true; // Variable spécifique pour marquer l'admin fixe
        header("Location: menu_admin.php");
        exit();
    } else {
        if (!empty($email) && !empty($password)) {
            $stmt = $conn->prepare("SELECT Id_Utilisateur, Email, MotDePasse FROM utilisateur WHERE Email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($id, $email_db, $motdepasse_clair);
                    $stmt->fetch();
                    if ($password === $motdepasse_clair) {
                        session_regenerate_id(true);
                        $_SESSION['utilisateur']['id'] = $id;
                        $_SESSION['utilisateur']['email'] = $email_db;
                        header("Location: menu_utilisateur.php");
                        exit();
                    } else {
                        $_SESSION['error'] = "Mot de passe incorrect.";
                        header("Location: authentification.php#connexion");
                        exit();
                    }
                } else {
                    $_SESSION['error'] = "Adresse email inconnue.";
                    header("Location: authentification.php#connexion");
                    exit();
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Erreur de requête.";
                header("Location: authentification.php#connexion");
                exit();
            }
            $conn->close();
        } else {
            $_SESSION['error'] = "Veuillez remplir tous les champs.";
            header("Location: authentification.php#connexion");
            exit();
        }
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscription'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $dateNaissance = trim($_POST['date_naissance'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmerMotDePasse = $_POST['confirmer_mot_de_passe'] ?? '';

    // Chemin où les photos seront stockées
    $uploadDir = 'uploads/'; // Assurez-vous que ce dossier existe et est inscriptible !
    $photoFileName = ''; // Initialise le nom du fichier photo

    // 1. Vérification des champs obligatoires
    if (empty($nom) || empty($prenom) || empty($dateNaissance) || empty($telephone) || empty($email) || empty($motDePasse) || empty($confirmerMotDePasse)) {
        $_SESSION['error'] = "❌ Veuillez remplir tous les champs obligatoires.";
        header("Location: authentification.php#inscription");
        exit();
    } elseif ($motDePasse !== $confirmerMotDePasse) {
        $_SESSION['error'] = "❌ Les mots de passe ne correspondent pas.";
        header("Location: authentification.php#inscription");
        exit();
    } else {
        // 2. Traitement de l'upload de photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileSize = $_FILES['photo']['size'];
            $fileType = $_FILES['photo']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg'];

            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Générer un nom de fichier unique pour éviter les collisions
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $photoFileName = $destPath; // Chemin relatif de la photo à stocker dans la DB
                } else {
                    $_SESSION['error'] = "❌ Erreur lors du déplacement du fichier photo.";
                    header("Location: authentification.php#inscription");
                    exit();
                }
            } else {
                $_SESSION['error'] = "❌ Type de fichier photo non autorisé. Seuls JPG, JPEG, PNG, GIF sont acceptés.";
                header("Location: authentification.php#inscription");
                exit();
            }
        } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Gérer les autres erreurs d'upload (taille max dépassée, etc.)
            $_SESSION['error'] = "❌ Erreur lors du téléchargement de la photo (code: " . $_FILES['photo']['error'] . ").";
            header("Location: authentification.php#inscription");
            exit();
        }
        // Si aucune photo n'est uploadée, $photoFileName reste vide, ce qui est correct si le champ est facultatif.

        // Si aucun message d'erreur n'a été généré par l'upload ou la validation de mot de passe
        if (empty($message_erreur)) {
            // Connexion avec MySQLi (Gardée telle quelle, mais PDO est recommandé)
            $conn = new mysqli('localhost', 'root', '', 'gestiondebillet');

            if ($conn->connect_error) {
                custom_die("Erreur de connexion : " . $conn->connect_error);
            }

            $conn->set_charset("utf8mb4");

            // Hachage du mot de passe
            $motDePasseHashed = password_hash($motDePasse, PASSWORD_DEFAULT);

            // Préparation de la requête
            $stmt = $conn->prepare("INSERT INTO utilisateur (Nom, Prenom, DateNaissance, Photo, Telephone, Email, MotDePasse, Type_utilisateur) VALUES (?, ?, ?, ?, ?, ?, ?, 'client')");

            $stmt->bind_param("sssssss", $nom, $prenom, $dateNaissance, $photoFileName, $telephone, $email, $motDePasseHashed);

            $result = $stmt->execute();
            $error = $stmt->error;

            $stmt->close();
            $conn->close();

            if ($result) {
                $_SESSION['success'] = "Inscription réussie ! Vous pouvez vous connecter.";
                header("Location: authentification.php#connexion");
                exit();
            } else {
                $_SESSION['error'] = "❌ Erreur lors de l'inscription : " . $error;
                header("Location: authentification.php#inscription");
                exit();
            }
        }
    }

}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion & Inscription</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body data-theme="dark" class="body-authentification">

<div class="container" id="container">
    <!-- Zone du Carrousel d'image -->
    <div class="left-panel">
        <div class="carousel-container">
            <div class="carousel-slide active">
                <h1>Capturer des moments</h1>
                <p>Créer des souvenirs inoubliables.</p>
            </div>
            <div class="carousel-slide">
                <h1>Explorer sans limites</h1>
                <p>Votre prochaine grande aventure n'est qu'à quelques clics.</p>
            </div>
        </div>
        <div class="carousel-indicators" id="carouselIndicators"></div>
    </div>
    <div class="right-panel" id="formPanel">
        <div class="form-wrapper">
            <!-- Formulaire de connexion -->
            <div class="form-container" id="connexion">
                <form action="" method="post">
                    <h2 class="titre">Connexion</h2>
                    <?php if (!empty($message_erreur)): ?>
                    <div class="error-message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <?php echo $message_erreur; ?>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <input id="email-signin" name="email" type="email" required placeholder=" "/>
                        <label for="email-signin">Adresse email</label>
                    </div>
                    <div class="form-group password-wrapper">
                        <input id="password-signin" name="password" type="password" required placeholder=" "/>
                        <label for="password-signin">Mot de passe</label>
                        <span class="toggle-password">
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     fill="currentColor" viewBox="0 0 16 16"><path
                                            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path
                                            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     fill="currentColor" style="display: none;" viewBox="0 0 16 16"><path
                                            d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/><path
                                            d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/></svg>
                            </span>
                    </div>
                    <button type="submit" name="connexion" class="btn-form">Se connecter</button>
                    <p class="switch-form-text">Pas encore de compte ? <a href="#inscription">S'inscrire</a></p>
                </form>
            </div>

            <!-- Formulaire d'inscription -->
            <div class="form-container" id="inscription">
                <form action="" method="post">
                    <h2 class="titre">Créer un compte</h2>
                    <?php if (!empty($message_erreur)): ?>
                    <div class="error-message <?php echo (strpos($message_erreur, '✅') !== false) ? 'success-message' : ''; ?>">
                        <?php if (strpos($message_erreur, '✅') !== false): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                        </svg>
                        <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        <?php endif; ?>
                        <?php echo $message_erreur; ?>
                    </div>
                    <?php endif; ?>
                    <label class="photo-uploader" for="photo" id="photoUploader">
                            <span class="photo-uploader-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor"
                                     viewBox="0 0 16 16"><path
                                            d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14a1 1 0 0 1 1 1v6zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2z"/><path
                                            d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zm0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM3 6.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/></svg>
                            </span>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </label>
                    <div class="form-group"><input type="text" id="nom" name="nom" required placeholder=" "><label
                                for="nom">Nom</label></div>
                    <div class="form-group"><input type="text" id="prenom" name="prenom" required placeholder=" "><label
                                for="prenom">Prénom</label></div>
                    <div class="form-group"><input type="date" id="date_naissance" name="date_naissance" required
                                                   placeholder=" "><label for="date_naissance">Date de naissance</label>
                    </div>
                    <div class="form-group"><input type="tel" id="telephone" name="telephone" required
                                                   placeholder=" "><label for="telephone">Téléphone</label></div>
                    <div class="form-group"><input type="email" id="email" name="email" required placeholder=" "><label
                                for="email">Email</label></div>
                    <div class="form-group password-wrapper">
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required placeholder=" ">
                        <label for="mot_de_passe">Mot de passe</label>
                        <span class="toggle-password">
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     fill="currentColor" viewBox="0 0 16 16"><path
                                            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path
                                            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     fill="currentColor" style="display: none;" viewBox="0 0 16 16"><path
                                            d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/><path
                                            d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/></svg>
                            </span>
                    </div>
                    <div class="form-group password-wrapper">
                        <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" required
                               placeholder=" ">
                        <label for="confirmer_mot_de_passe">Confirmer le mot de passe</label>
                        <span class="toggle-password">
                                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     fill="currentColor" viewBox="0 0 16 16"><path
                                            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path
                                            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     fill="currentColor" style="display: none;" viewBox="0 0 16 16"><path
                                            d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/><path
                                            d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/></svg>
                            </span>
                    </div>
                    <button type="submit" name="inscription" class="btn-form">Créer mon compte</button>
                    <p class="switch-form-text">Déjà un compte ? <a href="#connexion">Se connecter</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/form.js"></script>

</body>
</html>
