<?php
session_start();

// 🔒 Vérification de session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Connexion à la base
$conn = new mysqli('localhost', 'root', '', 'gestiondebillet');
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 🎫 Tickets liés aux événements créés par l'utilisateur via la table 'creer'
$tickets = [];
$stmt = $conn->prepare("
    SELECT t.Id_TicketEvenement, t.Titre, t.Prix, t.NombreDisponible
    FROM ticketevenement t
    JOIN evenement e ON t.Id_Evenement = e.Id_Evenement
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement
    WHERE c.Id_Utilisateur = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();

// 🧾 Achats effectués par l'utilisateur
$achats = [];
$stmt = $conn->prepare("
    SELECT a.DateAchat, t.Titre, t.Prix
    FROM achat a
    JOIN ticketevenement t ON a.Id_TicketEvenement = t.Id_TicketEvenement
    WHERE a.Id_Utilisateur = ?
    ORDER BY a.DateAchat DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $achats[] = $row;
}
$stmt->close();

// Ajout du traitement de la recherche d'événement
$search_keyword = isset($_GET['search_event']) ? trim($_GET['search_event']) : '';

// Récupération des événements créés par l'utilisateur (filtrés si recherche)
$evenements = [];
if (!empty($user_id)) {
    $sql = "SELECT e.Id_Evenement, e.Titre, e.DateDebut, e.DateFin FROM evenement e JOIN creer c ON e.Id_Evenement = c.Id_Evenement WHERE c.Id_Utilisateur = ?";
    $params = [$user_id];
    $types = "i";
    if ($search_keyword !== '') {
        $sql .= " AND e.Titre LIKE ?";
        $params[] = "%$search_keyword%";
        $types .= "s";
    }
    $sql .= " ORDER BY e.DateDebut DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $evenements[] = $row;
    }
    $stmt->close();
}

// Récupération des villes distinctes pour le menu déroulant
$villes = [];
$sql = "SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $villes[] = $row;
}

// Récupération du choix de ville
$search_ville = isset($_GET['search_ville']) ? trim($_GET['search_ville']) : '';

// Recherche globale sur tous les événements (filtrage par ville et mot-clé)
$evenements_globaux = [];
if ($search_keyword !== '' || $search_ville !== '') {
    $sql = "SELECT e.Id_Evenement, e.Titre, e.DateDebut, e.DateFin, v.Libelle AS Ville FROM evenement e JOIN ville v ON e.Id_Ville = v.Id_Ville WHERE 1";
    $params = [];
    $types = "";
    if ($search_keyword !== '') {
        $sql .= " AND e.Titre LIKE ?";
        $params[] = "%$search_keyword%";
        $types .= "s";
    }
    if ($search_ville !== '') {
        $sql .= " AND e.Id_Ville = ?";
        $params[] = $search_ville;
        $types .= "i";
    }
    $sql .= " ORDER BY e.DateDebut DESC";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $evenements_globaux[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace - Gestion Billet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Styles généraux pour correspondre à l'image */
        body {
            font-family: 'Open Sans', sans-serif; /* Similaire à Eventbrite */
            background-color: #f7f8fa; /* Fond clair */
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* --- Header Top Navigation --- */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 40px;
            background-color: #fff;
            border-bottom: 1px solid #e6e6e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .top-header .logo img {
            height: 30px; /* Taille du logo Eventbrite */
        }

        .top-header .search-location {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
            background-color: #f9f9f9;
        }

        .top-header .search-location i {
            color: #777;
            margin-right: 8px;
        }

        .top-header .search-location input {
            border: none;
            outline: none;
            padding: 5px;
            background-color: transparent;
            font-size: 15px;
        }

        .top-header .nav-icons {
            display: flex;
            align-items: center;
        }

        .top-header .nav-icons a {
            display: flex;
            align-items: center;
            color: #555;
            text-decoration: none;
            margin-left: 20px;
            font-size: 15px;
        }

        .top-header .nav-icons a i {
            margin-right: 5px;
            font-size: 1.2em;
            color: #666;
        }

        /* Dropdown pour le profil utilisateur */
        .dropdown {
            position: relative;
            display: inline-block;
            margin-left: 20px;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            color: #555;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: #f0f3f8; /* Fond gris clair comme sur l'image */
            transition: background-color 0.2s ease;
        }

        .dropdown-toggle:hover {
            background-color: #e6e9ed;
        }

        .dropdown-toggle img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 8px;
            border: 1px solid #ccc; /* Petite bordure pour l'avatar */
        }

        .dropdown-toggle span {
            margin-right: 5px;
        }

        .dropdown-toggle i.fa-caret-down {
            font-size: 0.8em;
            color: #888;
        }

        .dropdown-content {
            display: none; /* Caché par défaut */
            position: absolute;
            background-color: #f9f9f9;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 10;
            border-radius: 8px;
            right: 0; /* Aligne le menu à droite du bouton */
            margin-top: 10px; /* Espace sous le bouton */
            overflow: hidden; /* Pour les bords arrondis */
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 15px;
            transition: background-color 0.1s ease;
            margin-left: 0; /* Annule la marge des nav-icons */
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown-content a:first-child {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .dropdown-content a:last-child {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .dropdown.show .dropdown-content {
            display: block; /* Affiché quand la classe 'show' est présente */
        }

        /* --- Main Banner / Hero Section --- */
        .hero-banner {
            background-image: url('https://picsum.photos/1200/400?random=1'); /* Placeholder, remplacer par votre image */
            background-size: cover;
            background-position: center;
            height: 300px; /* Hauteur de la bannière */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden; /* Pour les flèches du carrousel */
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4); /* Overlay sombre pour la lisibilité du texte */
        }

        .hero-banner .content {
            position: relative;
            z-index: 1;
            padding: 20px;
        }

        .hero-banner .content h1 {
            font-size: 3.5em;
            margin: 0;
            font-weight: 800;
            line-height: 1.1;
        }

        .hero-banner .content p {
            font-size: 1.2em;
            margin-top: 10px;
            font-weight: 300;
        }

        .hero-banner .content .banner-button {
            display: inline-block;
            background-color: #ff3366; /* Couleur accent Eventbrite */
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .hero-banner .content .banner-button:hover {
            background-color: #e6004c;
        }

        /* Flèches de navigation de la bannière */
        .hero-banner .nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5em;
            z-index: 2;
            transition: background-color 0.3s ease;
        }

        .hero-banner .nav-arrow:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }

        .hero-banner .nav-arrow.left {
            left: 20px;
        }

        .hero-banner .nav-arrow.right {
            right: 20px;
        }

        /* --- Categories Section --- */
        .categories-section {
            padding: 30px 40px;
            background-color: #fff;
            margin-top: 20px; /* Espace après la bannière */
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin: 20px 40px; /* Marge autour pour qu'elle soit contenue */
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }

        .category-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin: 10px 15px;
            text-decoration: none;
            color: #555;
            font-size: 0.9em;
            transition: transform 0.2s ease;
        }

        .category-item:hover {
            transform: translateY(-3px);
            color: #007bff;
        }

        .category-item .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f0f3f8; /* Fond gris clair pour les icônes */
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 8px;
        }

        .category-item .icon-circle i {
            font-size: 1.8em;
            color: #666;
        }

        /* --- Secondary Navigation / Filters --- */
        .secondary-nav {
            padding: 15px 40px;
            background-color: #fff;
            border-bottom: 1px solid #e6e6e6;
            margin-top: 20px; /* Espace après les catégories */
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .secondary-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 25px; /* Espace entre les éléments */
        }

        .secondary-nav ul li a {
            text-decoration: none;
            color: #555;
            font-weight: 600;
            padding: 5px 0;
            border-bottom: 2px solid transparent;
            transition: color 0.3s ease, border-bottom-color 0.3s ease;
        }

        .secondary-nav ul li a.active,
        .secondary-nav ul li a:hover {
            color: #007bff;
            border-bottom-color: #007bff;
        }

        .secondary-nav .dropdown-filter { /* Renommé pour éviter le conflit */
            margin-left: auto; /* Aligne le dropdown à droite */
            position: relative;
        }

        .secondary-nav .dropdown-filter select {
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 4px;
            background-color: white;
            font-size: 0.95em;
            cursor: pointer;
            -webkit-appearance: none; /* Supprime le style par défaut sur Webkit */
            -moz-appearance: none;    /* Supprime le style par défaut sur Mozilla */
            appearance: none;         /* Supprime le style par défaut */
            padding-right: 30px; /* Espace pour la flèche personnalisée */
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%204%205%22%3E%3Cpath%20fill%3D%22%23333%22%20d%3D%22M2%200L0%202h4L2%200z%22%2F%3E%3C%2Fsvg%3E'); /* Flèche vers le bas */
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 8px;
        }


        /* --- Main Content Sections (Vos tableaux) --- */
        /* Ces sections sont maintenant affichées via JavaScript lorsqu'elles sont appelées par le menu déroulant */
        .content-section {
            /* Garde les styles pour quand elles seront affichées dynamiquement */
            display: none; /* Caché par défaut */
            padding: 30px 40px;
            margin-top: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin: 20px 40px; /* Marge autour */
        }

        .content-section.active {
            display: block; /* Affiche la section active */
        }


        .content-section h2 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .content-section table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: #fff;
        }

        .content-section th, .content-section td {
            border: 1px solid #e6e6e6;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.95em;
        }

        .content-section th {
            background-color: #f5f8fa;
            font-weight: 600;
            color: #555;
        }

        .content-section tr:nth-child(even) {
            background-color: #fdfdfd;
        }

        .content-section tr:hover {
            background-color: #f0f6fc;
        }

        .content-section p {
            color: #666;
            font-style: italic;
        }

        /* Bouton "Créer un nouvel événement" */
        .btn-creer {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background-color: #007bff; /* Couleur primaire */
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .btn-creer:hover {
            background-color: #0056b3;
        }

        /* Responsive design basique */
        @media (max-width: 768px) {
            .top-header,
            .secondary-nav ul,
            .categories-section,
            .content-section {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
                margin: 10px 20px;
            }

            .top-header .nav-icons {
                margin-top: 15px;
                flex-wrap: wrap;
                gap: 10px;
            }

            .top-header .search-location {
                width: 100%;
                margin-top: 15px;
            }

            .hero-banner {
                height: 200px;
            }
            .hero-banner .content h1 {
                font-size: 2em;
            }
            .hero-banner .content p {
                font-size: 0.9em;
            }

            .categories-section {
                justify-content: flex-start;
            }

            .category-item {
                width: 30%; /* Pour afficher 3 par ligne sur petits écrans */
                margin: 8px 5px;
            }

            .secondary-nav ul {
                gap: 15px;
            }

            .secondary-nav .dropdown-filter {
                margin-left: 0;
                margin-top: 15px;
                width: 100%;
            }

            .content-section th, .content-section td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <div class="logo">
            <img src="https://www.eventbrite.fr/assets/img/og-images/eventbrite-logo.png" alt="Logo Gestion Billet">
        </div>
        <div class="search-location">
            <form method="get" action="">
                <i class="fas fa-search"></i>
                <input type="text" name="search_event" placeholder="Rechercher un événement..." value="<?= htmlspecialchars($search_keyword) ?>">
                <select name="search_ville" style="margin-left:10px;">
                    <option value="">Toutes les villes</option>
                    <?php foreach ($villes as $ville): ?>
                        <option value="<?= $ville['Id_Ville'] ?>" <?= ($search_ville==$ville['Id_Ville'])?'selected':'' ?>><?= htmlspecialchars($ville['Libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" style="display:none"></button>
            </form>
        </div>
        <nav class="nav-icons">
            <a href="creer_evenement.php"><i class="fas fa-plus"></i> Créer événement</a>
            <a href="tickets.php"><i class="fas fa-ticket-alt"></i> Tickets</a>

            <div class="dropdown">
                <a href="#" class="dropdown-toggle" id="profileDropdownToggle">
                    <img src="https://via.placeholder.com/30/007bff/ffffff?text=U" alt="Profil">
                    <span><?= htmlspecialchars($user_email) ?></span>
                    <i class="fas fa-caret-down"></i>
                </a>
                <div class="dropdown-content" id="profileDropdownContent">
                    <a href="mes_tickets.php">Mes tickets créés</a>
                    <a href="panier.php">Mes achats</a>
                    <a href="mes_evenements.php">Mes événements</a>
                    <a href="mon_profil.php">Mon profil</a> <a href="mon_panier.php">Mon panier</a> <a href="parametres.php">Paramètres</a> <a href="connexion.php">Déconnexion</a>
                </div>
            </div>
        </nav>
    </header>

    <section class="hero-banner">
        <div class="nav-arrow left"><i class="fas fa-chevron-left"></i></div>
        <div class="content">
            <h1>GET INTO IT</h1>
            <p>FROM SMOKED MEATS TO SWEET TREATS</p>
            <a href="#" class="banner-button">Get Into Food Festivals</a>
        </div>
        <div class="nav-arrow right"><i class="fas fa-chevron-right"></i></div>
    </section>

    <section class="categories-section">
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-music"></i></div>
            <span>Musique</span>
        </a>
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-moon"></i></div>
            <span>Vie Nocturne</span>
        </a>
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-mask"></i></div>
            <span>Arts de la Scène</span>
        </a>
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-calendar-alt"></i></div>
            <span>Vacances</span>
        </a>
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-heart"></i></div>
            <span>Rencontres</span>
        </a>
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-dumbbell"></i></div>
            <span>Loisirs</span>
        </a>
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-briefcase"></i></div>
            <span>Affaires</span>
        </a>
        <a href="#" class="category-item">
            <div class="icon-circle"><i class="fas fa-utensils"></i></div>
            <span>Nourriture & Boissons</span>
        </a>
        </section>

    <nav class="secondary-nav">
        <ul>
            <li><a href="#" class="active">All</a></li>
            <li><a href="#">For you</a></li>
            <li><a href="#">Online</a></li>
            <li><a href="#">Today</a></li>
            <li><a href="#">This weekend</a></li>
            <li><a href="#">Free</a></li>
            <li><a href="#">Music</a></li>
            <li><a href="#">Food & Drink</a></li>
            <li><a href="#">Charity & Causes</a></li>
            <li class="dropdown-filter">
                <select name="location">
                    <option value="lagunes">Lagunes</option>
                    <option value="abidjan">Abidjan</option>
                    <option value="bouake">Bouaké</option>
                </select>
            </li>
        </ul>
    </nav>

    <div id="dynamic-content-container">
        <section class="content-section" id="mes-tickets-crees">
            <h2>🎫 Mes tickets créés</h2>
            <?php if (count($tickets)): ?>
                <table>
                    <tr><th>Titre</th><th>Prix</th><th>Disponibles</th></tr>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= htmlspecialchars($ticket['Titre']) ?></td>
                            <td><?= htmlspecialchars($ticket['Prix']) ?> FCFA</td>
                            <td><?= htmlspecialchars($ticket['NombreDisponible']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Aucun ticket lié à vos événements.</p>
            <?php endif; ?>
        </section>

        <section class="content-section" id="mes-achats">
            <h2>🧾 Mes achats</h2>
            <?php if (count($achats)): ?>
                <table>
                    <tr><th>Ticket</th><th>Prix</th><th>Date d'achat</th></tr>
                    <?php foreach ($achats as $achat): ?>
                        <tr>
                            <td><?= htmlspecialchars($achat['Titre']) ?></td>
                            <td><?= htmlspecialchars($achat['Prix']) ?> FCFA</td>
                            <td><?= htmlspecialchars($achat['DateAchat']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Vous n’avez encore effectué aucun achat.</p>
            <?php endif; ?>
        </section>

        <section class="content-section" id="mes-evenements">
            <h2>📅 Mes événements</h2>
            <?php if (count($evenements)): ?>
                <table>
                    <tr><th>Titre</th><th>Date début</th><th>Date fin</th></tr>
                    <?php foreach ($evenements as $event): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['Titre']) ?></td>
                            <td><?= htmlspecialchars($event['DateDebut']) ?></td>
                            <td><?= htmlspecialchars($event['DateFin']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Vous n’avez encore créé aucun événement<?php if ($search_keyword) echo ' ou aucun résultat pour "' . htmlspecialchars($search_keyword) . '"'; ?>.</p>
            <?php endif; ?>
            <a href="creer_evenement.php" class="btn-creer">➕ Créer un nouvel événement</a>
        </section>

        <section class="content-section" id="evenements-globaux" style="display:block;">
            <h2>🌍 Tous les événements</h2>
            <?php if ($search_keyword !== '' || $search_ville !== ''): ?>
                <?php if (count($evenements_globaux)): ?>
                    <table>
                        <tr><th>Titre</th><th>Date début</th><th>Date fin</th><th>Ville</th></tr>
                        <?php foreach ($evenements_globaux as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['Titre']) ?></td>
                                <td><?= htmlspecialchars($event['DateDebut']) ?></td>
                                <td><?= htmlspecialchars($event['DateFin']) ?></td>
                                <td><?= htmlspecialchars($event['Ville']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>Aucun événement trouvé pour
                        <?php if ($search_keyword) echo 'mot-clé "'.htmlspecialchars($search_keyword).'"'; ?>
                        <?php if ($search_ville) echo ' et ville sélectionnée.'; ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileDropdownToggle = document.getElementById('profileDropdownToggle');
            const profileDropdownContent = document.getElementById('profileDropdownContent');
            const dropdown = document.querySelector('.dropdown');
            const contentSections = document.querySelectorAll('.content-section'); // All sections to manage

            // Toggle dropdown visibility on click
            profileDropdownToggle.addEventListener('click', function(event) {
                event.preventDefault(); // Empêche le lien de naviguer
                dropdown.classList.toggle('show');
            });

            // Close the dropdown if the user clicks outside of it
            window.addEventListener('click', function(event) {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // Handle clicks on dropdown menu items
            profileDropdownContent.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();

                    const targetId = this.getAttribute('href').substring(1); // Get the ID without '#'
                    
                    // Hide all content sections
                    contentSections.forEach(section => {
                        section.classList.remove('active');
                    });

                    // Show the selected section
                    const targetSection = document.getElementById(targetId);
                    if (targetSection) {
                        targetSection.classList.add('active');
                        // Scroll to the top of the selected section
                        targetSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start' // Scroll to the top of the section
                        });
                    }
                    
                    dropdown.classList.remove('show'); // Close dropdown after clicking
                });
            });

            // Optionally, hide all content sections on initial load
            // This ensures they are not visible until explicitly selected from the dropdown
            contentSections.forEach(section => {
                section.classList.remove('active'); // Ensure none are active by default
            });
        });
    </script> 

</body>
</html>