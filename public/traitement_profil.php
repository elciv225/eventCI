<?php
// Fichier pour traiter les modifications de profil
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    $_SESSION['error_message'] = 'Vous devez être connecté pour modifier votre profil.';
    header('Location: index.php?page=accueil');
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../config/base.php';

$userId = $_SESSION['utilisateur']['id'];

// Traitement du formulaire de modification de profil
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['modifier_profil'])) {
    // Récupération et nettoyage des données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $dateNaissance = trim($_POST['date_naissance'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation des données
    $errors = [];

    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    }

    if (empty($prenom)) {
        $errors[] = "Le prénom est requis";
    }

    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }

    // Vérifier si l'email existe déjà pour un autre utilisateur
    if (!empty($email)) {
        $stmt_check_email = $conn->prepare("SELECT Id_Utilisateur FROM utilisateur WHERE Email = ? AND Id_Utilisateur != ?");
        $stmt_check_email->bind_param("si", $email, $userId);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors[] = "Cet email est déjà utilisé par un autre compte";
        }
        $stmt_check_email->close();
    }

    // Vérifier si les mots de passe correspondent
    if (!empty($password) && $password !== $passwordConfirm) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Si des erreurs sont détectées, afficher un message et arrêter le traitement
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header('Location: index.php?page=mon-profil');
        exit;
    }

    // Traitement de l'upload de photo
    $photoFileName = $_SESSION['utilisateur']['photo'] ?? ''; // Garder l'ancienne photo par défaut

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/photos_profil/';
        
        // Vérifier si le répertoire existe, sinon le créer
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error_message'] = "Impossible de créer le dossier pour les photos de profil.";
                header("Location: index.php?page=mon-profil");
                exit();
            }
        }

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
                // Supprimer l'ancienne photo si elle existe
                if (!empty($photoFileName) && file_exists($photoFileName)) {
                    unlink($photoFileName);
                }
                $photoFileName = $destPath;
            } else {
                $_SESSION['error_message'] = "Erreur lors du déplacement du fichier photo.";
                header("Location: index.php?page=mon-profil");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Type de fichier photo non autorisé. Seuls JPG, JPEG, PNG, GIF sont acceptés.";
            header("Location: index.php?page=mon-profil");
            exit();
        }
    }

    // Début de la transaction
    $conn->begin_transaction();

    try {
        // Mise à jour des informations de base
        $stmt_update = $conn->prepare("
            UPDATE utilisateur 
            SET Nom = ?, Prenom = ?, Email = ?, Telephone = ?, DateNaissance = ?, Photo = ? 
            WHERE Id_Utilisateur = ?
        ");
        $stmt_update->bind_param("ssssssi", $nom, $prenom, $email, $telephone, $dateNaissance, $photoFileName, $userId);
        if (!$stmt_update->execute()) {
            throw new Exception("Erreur lors de la mise à jour du profil: " . $stmt_update->error);
        }
        $stmt_update->close();

        // Mise à jour du mot de passe si fourni
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_password = $conn->prepare("UPDATE utilisateur SET MotDePasse = ? WHERE Id_Utilisateur = ?");
            $stmt_password->bind_param("si", $passwordHash, $userId);
            if (!$stmt_password->execute()) {
                throw new Exception("Erreur lors de la mise à jour du mot de passe: " . $stmt_password->error);
            }
            $stmt_password->close();
        }

        $conn->commit();

        // Mettre à jour les données de session
        $_SESSION['utilisateur']['nom'] = $nom;
        $_SESSION['utilisateur']['prenom'] = $prenom;
        $_SESSION['utilisateur']['email'] = $email;
        $_SESSION['utilisateur']['telephone'] = $telephone;
        $_SESSION['utilisateur']['date_naissance'] = $dateNaissance;
        $_SESSION['utilisateur']['photo'] = $photoFileName;

        $_SESSION['success_message'] = 'Profil mis à jour avec succès !';
        header('Location: index.php?page=mon-profil');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Une erreur est survenue lors de la mise à jour du profil: ' . $e->getMessage();
        header('Location: index.php?page=mon-profil');
        exit;
    }
} else {
    // Si le formulaire n'a pas été soumis correctement
    $_SESSION['error_message'] = 'Requête invalide.';
    header('Location: index.php?page=mon-profil');
    exit;
}