<?php
// Activez l'affichage des erreurs PHP pour le débogage (À DÉSACTIVER EN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Démarre la session

require_once 'base.php'; // Inclure le fichier de connexion à la base de données

// --- Vérification des privilèges de l'administrateur unique ---
// On vérifie si la variable de session spécifique à l'admin fixe est définie et vraie.
$isAdmin = false;
if (isset($_SESSION['is_admin_fixed']) && $_SESSION['is_admin_fixed'] === true) {
    $isAdmin = true;
}

if (!$isAdmin) {
    // Si l'utilisateur n'est PAS l'administrateur fixe, rediriger.
    header("Location: connexion.php"); // Rediriger vers la page de connexion
    exit("Accès non autorisé. Vous n'avez pas les privilèges d'administrateur.");
}

// Optionnel: Récupérer des statistiques rapides pour le tableau de bord
$totalUsers = 0;
$totalEvents = 0;
$totalTicketsSold = 0;
$totalRevenue = 0;

// Requête pour le nombre total d'utilisateurs
$sqlUsers = "SELECT COUNT(Id_Utilisateur) AS total_users FROM utilisateur";
if ($result = $conn->query($sqlUsers)) {
    $row = $result->fetch_assoc();
    $totalUsers = $row['total_users'];
    $result->free();
}

// Requête pour le nombre total d'événements
$sqlEvents = "SELECT COUNT(Id_Evenement) AS total_events FROM evenement";
if ($result = $conn->query($sqlEvents)) {
    $row = $result->fetch_assoc();
    $totalEvents = $row['total_events'];
    $result->free();
}

// Requête pour le nombre total de tickets vendus (nombre d'enregistrements dans la table 'achat')
$sqlTicketsSold = "SELECT COUNT(Id_Achat) AS total_tickets_sold FROM achat";
if ($result = $conn->query($sqlTicketsSold)) {
    $row = $result->fetch_assoc();
    $totalTicketsSold = $row['total_tickets_sold'];
    $result->free();
}

// Requête pour le chiffre d'affaires total (somme des prix des tickets achetés)
$sqlRevenue = "SELECT SUM(te.Prix) AS total_revenue
               FROM achat a
               JOIN ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement";
if ($result = $conn->query($sqlRevenue)) {
    $row = $result->fetch_assoc();
    $totalRevenue = $row['total_revenue'] ?? 0; // Assurez-vous que c'est 0 si SUM est null
    $result->free();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Administrateur</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* Police plus moderne */
            background-color: #f0f2f5;
            /* Fond légèrement plus clair */
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
            /* Améliore la lisibilité du texte */
        }

        /* --- Header --- */
        .header {
            background-color: #2c3e50;
            /* Garde la couleur sombre */
            color: #fff;
            padding: 18px 30px;
            /* Augmente le padding */
            font-size: 1.6em;
            /* Légèrement plus petit pour l'harmonie */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            /* Ombre plus prononcée */
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            /* Rendre le header fixe au défilement */
            top: 0;
            z-index: 1000;
            /* Assure que le header reste au-dessus des autres éléments */
        }

        .header span {
            font-weight: 600;
            /* Texte de l'en-tête plus gras */
        }

        .header .logout-btn {
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            /* Augmente le padding du bouton */
            border-radius: 25px;
            /* Bordures plus arrondies */
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
            /* Transition plus douce */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            /* Ajoute une ombre au bouton */
        }

        .header .logout-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            /* Léger effet de soulèvement au survol */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        /* --- Conteneur principal --- */
        .container {
            display: flex;
            min-height: calc(100vh - 76px);
            /* Ajuste la hauteur min après le nouveau padding du header */
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 260px;
            /* Légèrement plus large */
            background-color: #34495e;
            padding: 25px 0;
            /* Padding ajusté */
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            /* Ombre plus distincte */
            color: #ecf0f1;
            display: flex;
            /* Utilisation de flexbox pour l'alignement */
            flex-direction: column;
            position: sticky;
            /* Rendre la sidebar fixe */
            top: 76px;
            /* Commence après le header */
            height: calc(100vh - 76px);
            /* Occupe le reste de la hauteur de la fenêtre */
            overflow-y: auto;
            /* Permet le défilement si le contenu dépasse */
        }

        .sidebar h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 35px;
            /* Marge plus grande */
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            /* Bordure plus subtile */
            padding-bottom: 20px;
            font-size: 1.6em;
            /* Taille de titre plus grande */
            letter-spacing: 1px;
            /* Espacement entre les lettres */
        }

        .sidebar ul {
            list-style: none;
            padding: 0 20px;
            /* Padding latéral pour les éléments de liste */
            flex-grow: 1;
            /* Permet à la liste de prendre l'espace disponible */
        }

        .sidebar ul li {
            margin-bottom: 8px;
            /* Espacement réduit entre les éléments */
        }

        .sidebar ul li a {
            display: flex;
            /* Utilisation de flexbox pour les icônes (si ajoutées plus tard) */
            align-items: center;
            gap: 10px;
            /* Espace entre icône et texte */
            color: #ecf0f1;
            text-decoration: none;
            padding: 14px 15px;
            /* Padding ajusté */
            border-radius: 8px;
            /* Bordures plus arrondies */
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
            font-weight: 500;
            /* Texte un peu plus épais */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #2980b9;
            color: #fff;
            transform: translateX(5px);
            /* Léger décalage vers la droite au survol/actif */
        }

        /* --- Main Content --- */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: #ffffff;
            /* Fond blanc pur */
            margin: 20px;
            border-radius: 10px;
            /* Bordures plus arrondies */
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            /* Ombre plus douce et plus étendue */
        }

        .section-title {
            font-size: 2em;
            /* Taille de titre plus grande */
            color: #2c3e50;
            /* Couleur du header pour les titres */
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            /* Bordure plus épaisse */
            padding-bottom: 15px;
            font-weight: 700;
            /* Titre plus gras */
            text-transform: uppercase;
            /* Met le titre en majuscules */
            letter-spacing: 0.5px;
        }

        /* --- Dashboard Cards --- */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            /* Cartes légèrement plus grandes */
            gap: 25px;
            /* Espacement plus grand entre les cartes */
            margin-bottom: 40px;
        }

        .card {
            background-color: #ffffff;
            /* Fond blanc pour les cartes */
            padding: 25px;
            /* Padding augmenté */
            border-radius: 10px;
            /* Bordures plus arrondies */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            /* Ombre plus distincte */
            text-align: center;
            border-left: 6px solid;
            /* Bordure plus épaisse */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            /* Transitions pour l'interactivité */
        }

        .card:hover {
            transform: translateY(-5px);
            /* Soulève la carte au survol */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            /* Ombre plus intense au survol */
        }

        .card h3 {
            color: #555;
            margin-top: 0;
            font-size: 1.2em;
            /* Taille de titre de carte plus grande */
            font-weight: 600;
            margin-bottom: 15px;
        }

        .card p {
            font-size: 2.8em;
            /* Chiffres plus grands */
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
            /* Supprime la marge par défaut */
        }

        /* Couleurs des bordures des cartes - légèrement ajustées */
        .card.users {
            border-color: #2ecc71;
        }

        /* Vert plus vif */
        .card.events {
            border-color: #f1c40f;
        }

        /* Jaune plus vif */
        .card.tickets {
            border-color: #9b59b6;
        }

        /* Violet conservé */
        .card.revenue {
            border-color: #16a085;
        }

        /* Vert-bleu plus profond */

        /* --- Quick Actions --- */
        .quick-actions {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            /* Espacement plus grand entre les boutons */
        }

        .quick-actions a {
            background-color: #3498db;
            color: white;
            padding: 14px 25px;
            /* Padding augmenté pour les boutons */
            border-radius: 25px;
            /* Bordures très arrondies */
            text-decoration: none;
            font-size: 1em;
            /* Taille de police légèrement plus grande */
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            /* Ombre pour les boutons */
            font-weight: 500;
        }

        .quick-actions a:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
            /* Effet de soulèvement plus prononcé */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }

        /* --- Responsive Design (Ajout) --- */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
                /* La sidebar passe au-dessus en colonne */
            }

            .sidebar {
                width: 100%;
                /* La sidebar prend toute la largeur */
                height: auto;
                /* Hauteur automatique */
                position: static;
                /* Ne plus être fixe */
                padding-bottom: 0;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            }

            .sidebar h2 {
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            .sidebar ul {
                display: flex;
                /* Les éléments de menu s'alignent horizontalement */
                flex-wrap: wrap;
                justify-content: center;
                padding: 0 10px;
            }

            .sidebar ul li {
                margin: 5px 10px;
                /* Espacement entre les éléments horizontaux */
            }

            .sidebar ul li a {
                padding: 10px 15px;
            }

            .main-content {
                margin: 20px 15px;
                /* Marges ajustées pour les petits écrans */
                padding: 20px;
            }

            .dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                /* Ajuste la taille des cartes */
                gap: 15px;
            }

            .quick-actions {
                justify-content: center;
                /* Centre les boutons d'action rapide */
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                /* Le header passe en colonne */
                padding: 15px;
                font-size: 1.5em;
            }

            .header span {
                margin-bottom: 10px;
                /* Marge entre le titre et le bouton */
            }

            .section-title {
                font-size: 1.6em;
                text-align: center;
            }

            .card h3 {
                font-size: 1em;
            }

            .card p {
                font-size: 2.2em;
            }

            .quick-actions a {
                padding: 10px 15px;
                font-size: 0.9em;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                margin: 10px;
                padding: 15px;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
                /* Une seule colonne sur très petits écrans */
            }

            .sidebar ul {
                flex-direction: column;
                /* Les éléments de la sidebar reviennent en colonne */
                align-items: center;
            }

            .sidebar ul li {
                width: 90%;
                /* Prend plus de place sur la largeur */
                text-align: center;
                margin-bottom: 8px;
            }

            .sidebar ul li a {
                justify-content: center;
                /* Centre le texte des liens */
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <span>Tableau de Bord Administrateur</span>
        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </div>

    <div class="container">
        <aside class="sidebar">
            <h2>Navigation Admin</h2>
            <ul>
                <li><a href="menu_administrateur.php" class="active">Tableau de Bord</a></li>
                <li><a href="admin_gerer_utilisateurs.php">Gérer les Utilisateurs</a></li>
                <li><a href="admin_gerer_evenement.php">Gérer les Événements</a></li>
                <li><a href="admin_gerer_tickets.php">Gérer les Tickets</a></li>
                <li><a href="admin_gerer_categories.php">Gérer les Catégories</a></li>
                <li><a href="admin_rapporst.php">Rapports et Stats</a></li>
                <li><a href="admin_parametres_site.php">Paramètres du Site</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1 class="section-title">Aperçu du Système</h1>

            <div class="dashboard-cards">
                <div class="card users">
                    <h3>Total Utilisateurs</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
                <div class="card events">
                    <h3>Total Événements</h3>
                    <p><?php echo $totalEvents; ?></p>
                </div>
                <div class="card tickets">
                    <h3>Tickets Vendus</h3>
                    <p><?php echo $totalTicketsSold; ?></p>
                </div>
                <div class="card revenue">
                    <h3>Chiffre d'Affaires Total</h3>
                    <p><?php echo number_format($totalRevenue, 2, ',', ' '); ?> CFA</p>
                </div>
            </div>

            <h1 class="section-title">Actions Rapides</h1>
            <div class="quick-actions">
                <a href="admin_ajouter_utilisateur.php">Ajouter un nouvel utilisateur</a>
                <a href="admin_creer_evenement.php">Créer un événement (Admin)</a>
                <a href="admin_modifier_motdepasse.php">Modifier mon mot de passe</a>
                <a href="admin_param_email.php">Paramètres Email</a>
            </div>

        </main>
    </div>
</body>

</html>