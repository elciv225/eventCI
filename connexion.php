<?php

session_start();



// Identifiants admin fixes

$admin_username = "admin_user"; // À changer pour un nom d'utilisateur sécurisé

$admin_password_hash = password_hash("12345678", PASSWORD_DEFAULT); // CHANGEZ CECI !

// Initialiser le message d'erreur

$message_erreur = '';



if ($_SERVER["REQUEST_METHOD"] == "POST") {



    $email = $_POST['email'] ?? '';

    $password = $_POST['password'] ?? '';


    // Vérification de l'administrateur

    if ($email === $admin_username && password_verify($password, $admin_password_hash)) {



        $_SESSION['user_id'] = -1; // Un ID fictif pour l'admin (non issu de la BDD)

        $_SESSION['user_email'] = $admin_username; // Ou un email générique pour l'admin

        $_SESSION['is_admin_fixed'] = true; // Variable spécifique pour marquer l'admin fixe

        header("Location: menu_admin.php");

        exit();

    } else {



        if (!empty($email) && !empty($password)) {

            $conn = new mysqli('localhost', 'root', '', 'gestiondebillet');

            if ($conn->connect_error) {

                die("Erreur de connexion : " . $conn->connect_error);

            }

            $conn->set_charset("utf8mb4");



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

                        $_SESSION['user_id'] = $id;

                        $_SESSION['user_email'] = $email_db;

                        header("Location: menu_utilisateur.php");

                        exit();

                    } else {

                        $message_erreur = "Mot de passe incorrect.";

                    }

                } else {

                    $message_erreur = "Adresse email inconnue.";

                }

                $stmt->close();

            } else {

                $message_erreur = "Erreur de requête.";

            }



            $conn->close();

        } else {

            $message_erreur = "Veuillez remplir tous les champs.";

        }

    }

}



?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        /* Styles CSS inchangés par rapport à la version précédente */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
            /* Image d'arrière-plan */
            background-image: url('images/background-event.jpg'); /* Assurez-vous que le chemin est correct */
            background-size: cover; /* Couvre toute la surface */
            background-position: center; /* Centre l'image */
            background-repeat: no-repeat; /* Ne répète pas l'image */
            background-attachment: fixed; /* L'image reste fixe lors du défilement */
            position: relative; /* Pour le pseudo-élément de superposition */
        }

        /* Superposition sombre pour améliorer la lisibilité du texte du formulaire */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Couleur noire avec 50% d'opacité */
            z-index: 0; /* Assure que la superposition est en dessous du formulaire */
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.95); /* Fond blanc légèrement transparent */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); /* Ombre plus prononcée */
            text-align: center;
            max-width: 450px; /* Plus large pour un meilleur look */
            width: 90%; /* Responsive */
            box-sizing: border-box;
            position: relative; /* Pour que le z-index fonctionne par rapport à body::before */
            z-index: 1; /* Assure que le formulaire est au-dessus de la superposition */
            backdrop-filter: blur(5px); /* Effet de flou sur l'arrière-plan du container */
            border: 1px solid rgba(255, 255, 255, 0.3); /* Bordure subtile */
        }

        .login-container h2 {
            margin-bottom: 30px;
            color: #2c3e50; /* Couleur plus foncée pour le titre */
            font-size: 2.2em; /* Taille de police plus grande */
            font-weight: 700;
            letter-spacing: 1px;
        }

        .login-container input[type="email"],
        .login-container input[type="password"] {
            width: calc(100% - 24px); /* Ajustement pour le padding */
            padding: 14px 12px; /* Plus de padding */
            margin-bottom: 20px;
            border: 1px solid #c9d2da; /* Bordure plus douce */
            border-radius: 8px; /* Rayon plus prononcé */
            font-size: 1.05em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container input[type="email"]:focus,
        .login-container input[type="password"]:focus {
            border-color: #007bff; /* Bordure bleue au focus */
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2); /* Ombre légère au focus */
            outline: none; /* Supprime l'outline par défaut du navigateur */
        }

        .login-container button {
            width: 100%;
            padding: 15px; /* Plus de padding */
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em; /* Texte plus grand */
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2); /* Ombre pour le bouton */
        }

        .login-container button:hover {
            background-color: #0056b3;
            transform: translateY(-2px); /* Léger soulèvement */
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
        }

        .login-container .error-message {
            color: #e74c3c;
            background-color: #fce8e8;
            border: 1px solid #e74c3c;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.95em;
        }

        .login-container p {
            margin-top: 30px;
            font-size: 1em;
            color: #555;
        }

        .login-container a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-container a:hover {
            text-decoration: underline;
            color: #0056b3;
        }

        /* Responsive adjustments */
        @media (max-width: 500px) {
            .login-container {
                padding: 30px 20px;
                border-radius: 8px;
            }
            .login-container h2 {
                font-size: 1.8em;
            }
            .login-container input,
            .login-container button {
                padding: 12px;
                font-size: 1em;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Connexion</h2>
        <?php if (!empty($message_erreur)): ?>
            <p class="error-message"><?php echo $message_erreur; ?></p>
        <?php endif; ?>
        <form action="connexion.php" method="post">
            <input type="text'" name="email" placeholder="Adresse email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <p>Pas encore inscrit ? <a href="inscription.php">Créer un compte</a></p>
    </div>
</body>
</html>






























