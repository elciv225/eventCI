<?php
// mon_profil.php

// Inclure le gestionnaire d'erreurs personnalisé
require_once __DIR__ . '/error_handler.php';

// Démarrer la session. C'est le SEUL session_start() pour cette page.
// Si vous avez un fichier d'inclusion (comme 'base.php' ou 'connexion.php')
// qui contient déjà session_start(), RETIREZ ce session_start() d'ici.
session_start();

// --- Configuration de la connexion à la base de données (MySQLi) ---
// Remplacez 'localhost', 'root', '' par vos identifiants réels
// Remplacez 'gestiondebillet' par le nom de votre base de données réelle
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gestiondebillet";

// Crée une nouvelle connexion MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifie la connexion
if ($conn->connect_error) {
    custom_die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

// Définit le jeu de caractères pour la connexion
$conn->set_charset("utf8mb4");

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    header('Location: connexion.php'); // Rediriger vers la page de connexion
    exit();
}

$id_utilisateur_connecte = $_SESSION['utilisateur']['id'];

// Requête SQL pour récupérer les infos du profil en utilisant MySQLi
// Utilisez un 'LEFT JOIN' si 'Photo' est dans une autre table ou peut être NULL
$sql = "SELECT Nom, Prenom, DateNaissance, Photo, Telephone, Email, Type_utilisateur FROM utilisateur WHERE Id_Utilisateur = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    custom_die("Erreur de préparation de la requête : " . $conn->error);
}

// Lie le paramètre
$stmt->bind_param("i", $id_utilisateur_connecte); // "i" pour integer (Id_Utilisateur)

// Exécute la requête
$stmt->execute();

// Récupère le résultat
$result = $stmt->get_result();
$utilisateur = $result->fetch_assoc();

// Ferme le statement
$stmt->close();

// Ferme la connexion (peut être fermée plus tard si d'autres requêtes sont nécessaires sur la page)
// Pour une page simple comme celle-ci, la fermer ici est acceptable.
$conn->close();

// Vérifie si l'utilisateur a été trouvé
if (!$utilisateur) {
    // Si l'ID de session ne correspond à aucun utilisateur, invalider la session et rediriger
    session_destroy();
    header('Location: connexion.php');
    custom_die("Profil non trouvé ou session invalide.");
}

// Les includes de menu devraient être faits *après* la balise <body> pour ne pas casser les en-têtes.
// Ou mieux encore, structurez votre application avec un gabarit.
// Pour l'instant, je vais les commenter pour s'assurer que la page de profil fonctionne seule.
/*
// Vérifie le type d'utilisateur pour inclure le bon menu
if ($utilisateur['Type_utilisateur'] === 'admin') {
    // include('menu_admin.php'); // Assurez-vous que ce fichier ne contient QUE le HTML du menu
} else {
    // include('menu_utilisateur.php'); // Assurez-vous que ce fichier ne contient QUE le HTML du menu
}
*/
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Styles CSS améliorés */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eef1f5;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
            display: flex; /* Utilisation de flexbox pour centrer le contenu */
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* S'assure que le contenu est centré verticalement */
        }

        .container {
            max-width: 800px; /* Largeur maximale augmentée pour plus d'espace */
            margin: 30px auto;
            background: #fff;
            padding: 40px; /* Padding augmenté */
            border-radius: 12px; /* Coins plus arrondis */
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); /* Ombre plus douce et plus profonde */
            position: relative; /* Pour positionner l'icône de décoration */
            overflow: hidden; /* Cache les débordements */
        }

        /* Ajout d'une forme décorative en arrière-plan */
        .container::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background-color: rgba(0, 123, 255, 0.1); /* Couleur du thème avec opacité */
            border-radius: 50%;
            filter: blur(40px); /* Effet flou */
            z-index: 0;
        }
        .container::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background-color: rgba(44, 62, 80, 0.08); /* Autre couleur du thème */
            border-radius: 50%;
            filter: blur(30px);
            z-index: 0;
        }

        h1 {
            text-align: center;
            color: #2c3e50; /* Couleur plus foncée et professionnelle */
            margin-bottom: 30px; /* Marge augmentée */
            border-bottom: 2px solid #e0e5e9; /* Bordure plus subtile */
            padding-bottom: 15px;
            font-size: 2.2em; /* Taille de titre plus grande */
            font-weight: 700; /* Plus gras */
            position: relative;
            z-index: 1; /* S'assure que le titre est au-dessus des décorations */
        }

        .profile-info {
            display: flex;
            flex-direction: column; /* Passe en colonne par défaut pour une meilleure adaptabilité */
            align-items: center; /* Centre les éléments */
            gap: 30px; /* Espacement plus grand entre les blocs */
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .profile-photo {
            flex-shrink: 0;
            text-align: center;
            position: relative;
        }
        .profile-photo img {
            width: 180px; /* Taille augmentée pour la photo */
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #007bff; /* Bordure primaire */
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2); /* Ombre colorée pour la photo */
            transition: transform 0.3s ease-in-out; /* Animation au survol */
        }
        .profile-photo img:hover {
            transform: scale(1.05); /* Zoom léger au survol */
        }

        /* Badge pour le type d'utilisateur sur la photo */
        .profile-photo::after {
            content: attr(data-type); /* Utilise un attribut data pour le contenu */
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: #28a745; /* Vert pour badge */
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border: 2px solid #fff; /* Bordure blanche pour le contraste */
            text-transform: capitalize; /* Première lettre en majuscule */
        }

        .profile-details {
            flex-grow: 1;
            width: 100%; /* Prend toute la largeur disponible */
        }
        .profile-details p {
            margin-bottom: 12px; /* Espacement ajusté */
            font-size: 1.15em; /* Taille de police légèrement plus grande */
            padding: 8px 0;
            border-bottom: 1px dashed #eee; /* Ligne de séparation subtile */
            display: flex; /* Utilise flexbox pour aligner clé/valeur */
            align-items: center;
            gap: 15px;
        }
        .profile-details p:last-child {
            border-bottom: none; /* Pas de bordure pour le dernier élément */
        }
        .profile-details p strong {
            color: #555;
            min-width: 160px; /* Largeur minimale pour les libellés */
            font-weight: 600; /* Plus gras */
        }
        .profile-details p span {
            color: #000; /* Couleur noire pour les valeurs */
            font-weight: 500;
        }

        .button-group {
            text-align: center;
            margin-top: 40px; /* Marge augmentée pour les boutons */
            position: relative;
            z-index: 1;
        }

        .edit-button, .back-button {
            display: inline-block;
            padding: 14px 28px; /* Padding augmenté pour de plus grands boutons */
            border-radius: 30px; /* Bordures très arrondies */
            text-decoration: none;
            font-size: 1.05em; /* Taille de police légèrement plus grande */
            font-weight: 600;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease-in-out; /* Transitions douces */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); /* Ombre subtile */
        }

        .edit-button {
            background-color: #007bff; /* Bleu primaire */
            color: white;
            border: 2px solid #007bff;
        }
        .edit-button:hover {
            background-color: #0056b3; /* Bleu plus foncé au survol */
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3); /* Ombre plus prononcée */
            transform: translateY(-3px); /* Léger effet de soulèvement */
        }

        .back-button {
            background-color: #6c757d; /* Gris pour le bouton retour */
            color: white;
            border: 2px solid #6c757d;
        }
        .back-button:hover {
            background-color: #5a6268; /* Gris plus foncé au survol */
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            transform: translateY(-3px);
        }

        /* Media Queries pour le Responsive Design */
        @media (min-width: 768px) {
            .profile-info {
                flex-direction: row; /* Reviens en ligne sur les grands écrans */
                align-items: flex-start; /* Aligne les éléments en haut */
            }
            .profile-details {
                text-align: left; /* Alignement du texte à gauche */
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px; /* Réduit le padding sur petits écrans */
                margin: 15px;
            }
            h1 {
                font-size: 1.8em;
                margin-bottom: 20px;
            }
            .profile-photo img {
                width: 120px; /* Réduit la taille de la photo */
                height: 120px;
            }
            .profile-details p {
                flex-direction: column; /* Empile clé et valeur */
                align-items: flex-start;
                font-size: 1em;
                gap: 5px;
            }
            .profile-details p strong {
                min-width: unset; /* Supprime la largeur minimale */
                width: 100%; /* Prend toute la largeur pour le label */
            }
            .edit-button, .back-button {
                padding: 12px 20px;
                font-size: 0.95em;
                margin: 5px;
            }
            .button-group {
                display: flex; /* Pour que les boutons se placent côte à côte */
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mon Profil</h1>

        <div class="profile-info">
            <div class="profile-photo" data-type="<?php echo htmlspecialchars($utilisateur['Type_utilisateur']); ?>">
                <?php
                // Chemin de la photo de profil, ou une image par défaut si elle n'existe pas
                // Assurez-vous que 'images/default_profile.png' existe !
                $photoPath = !empty($utilisateur['Photo']) ? htmlspecialchars($utilisateur['Photo']) : 'images/default_profile.png';
                ?>
                <img src="<?php echo $photoPath; ?>" alt="Photo de profil">
            </div>
            <div class="profile-details">
                <p><strong>Nom :</strong> <span><?php echo htmlspecialchars($utilisateur['Nom']); ?></span></p>
                <p><strong>Prénom :</strong> <span><?php echo htmlspecialchars($utilisateur['Prenom']); ?></span></p>
                <p><strong>Email :</strong> <span><?php echo htmlspecialchars($utilisateur['Email']); ?></span></p>
                <p><strong>Téléphone :</strong> <span><?php echo htmlspecialchars($utilisateur['Telephone']); ?></span></p>
                <p><strong>Date de Naissance :</strong> <span><?php echo htmlspecialchars((new DateTime($utilisateur['DateNaissance']))->format('d/m/Y')); ?></span></p>
                <p><strong>Type d'utilisateur :</strong> <span><?php echo htmlspecialchars($utilisateur['Type_utilisateur']); ?></span></p>
            </div>
        </div>

        <div class="button-group">
            <a href="modifier_profil.php" class="edit-button">Modifier le Profil</a>
            <a href="<?php echo ($utilisateur['Type_utilisateur'] === 'admin') ? 'menu_admin.php' : 'menu_utilisateur.php'; ?>" class="back-button">Retour au Menu</a>
        </div>
    </div>
</body>
</html>
