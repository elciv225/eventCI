<?php
// Démarrer la mise en tampon de sortie
ob_start();

session_start();

// Connexion à la base de données
if (empty($conn)){
    require '../config/base.php';
}

// Vérification que l'utilisateur connecté est un administrateur.
if ($_SESSION['utilisateur']['id'] !== -1) {
    header("Location: ../authentification.php");
    exit("Accès refusé.");
}

// Récupérer la page courante
$page = $_GET['page'] ?? 'dashboard';

// Variables pour les statistiques du tableau de bord
$totalUsers = 0;
$totalEvents = 0;
$totalTicketsSold = 0;
$totalRevenue = 0;

// Récupérer les statistiques uniquement si on est sur le tableau de bord
if ($page === 'dashboard') {
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
}

// Inclusion des composants en fonction de la page demandée
switch ($page) {
    case 'dashboard':
        // Le tableau de bord est affiché directement dans ce fichier
        break;
    case 'gerer_utilisateur':
        include 'composants/gerer_utilisateur.php';
        break;
    case 'admin_gerer_evenement':
        include 'composants/admin_gerer_evenement.php';
        break;
    case 'gerer_ticket_admin':
        include 'composants/gerer_ticket_admin.php';
        break;
    case 'valider_tickets':
        include 'composants/valider_tickets.php';
        break;
    case 'gerer_categorie_evenement':
        include 'composants/gerer_categorie_evenement.php';
        break;
    case 'rapport_statistique':
        include 'composants/rapport_statistique.php';
        break;
    case 'admin_parametres_site':
        include 'composants/admin_parametres_site.php';
        break;
    case 'admin_modifier_motdepasse':
        include 'composants/admin_modifier_motdepasse.php';
        break;
    case 'messagerie':
        include 'composants/messagerie.php';
        break;
    case 'supprimer_utilisateur':
        include 'composants/supprimer_utilisateur.php';
        break;
    case 'modifier_utilisateur':
        include 'composants/modifier_utilisateur.php';
        break;
    case 'supprimer_ticket_evenement':
        include 'composants/supprimer_ticket_evenement.php';
        break;
    case 'modifier_ticket_evenement':
        include 'composants/modifier_ticket_evenement.php';
        break;
    case 'supprimer_categorie_evenement':
        include 'composants/supprimer_categorie_evenement.php';
        break;
    case 'modifier_categorie_evenement':
        include 'composants/modifier_categorie_evenement.php';
        break;
    case 'modifier_evenement':
        include 'composants/modifier_evenement.php';
        break;
    default:
        // Si la page demandée n'existe pas, rediriger vers le tableau de bord
        header('Location: index.php?page=dashboard');
        exit;
}

// On récupère le contenu de la page depuis la mémoire tampon
$page_content = ob_get_clean();

// Fermer la connexion après avoir traité toutes les requêtes
if (isset($conn) && $conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Administrateur - Professionnel</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700;800&display=swap"
        rel="stylesheet">
    <!-- Removed Font Awesome, replaced with Lucide for consistency -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body data-theme="light">
    <header class="header animate_animated animate_fadeInDown">
        <span>Tableau de Bord Administrateur</span>
        <div class="header-actions">
            <button class="icon-btn" id="theme-toggle" title="Changer de thème (clair/sombre)">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256" id="theme-icon">
                    <path d="M233.54,142.23a8,8,0,0,0-8-2,88.08,88.08,0,0,1-109.8-109.8,8,8,0,0,0-10-10,104.84,104.84,0,0,0-52.91,37A104,104,0,0,0,136,224a103.09,103.09,0,0,0,62.52-20.88,104.84,104.84,0,0,0,37-52.91A8,8,0,0,0,233.54,142.23ZM188.9,190.34A88,88,0,0,1,65.66,67.11a89,89,0,0,1,31.4-26A106,106,0,0,0,96,56,104.11,104.11,0,0,0,200,160a106,106,0,0,0,14.92-1.06A89,89,0,0,1,188.9,190.34Z"></path>
                </svg>
            </button>
            <a href="../authentification.php?logout=1" class="logout-btn animate_animated animatepulse animate_infinite">
                <i data-lucide="log-out" class="w-5 h-5"></i> Déconnexion
            </a>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar animate_animated animate_fadeInLeft">
            <h2>Navigation Admin</h2>
            <ul>
                <li><a href="?page=dashboard" class="<?php echo ($page === 'dashboard') ? 'active' : ''; ?>"><i data-lucide="layout-dashboard" class="w-5 h-5"></i> Tableau de
                        Bord</a></li>
                <li><a href="?page=gerer_utilisateur" class="<?php echo ($page === 'gerer_utilisateur') ? 'active' : ''; ?>"><i data-lucide="users" class="w-5 h-5"></i> Gérer les Utilisateurs</a></li>
                <li><a href="?page=admin_gerer_evenement" class="<?php echo ($page === 'admin_gerer_evenement') ? 'active' : ''; ?>"><i data-lucide="calendar" class="w-5 h-5"></i> Gérer les Événements</a>
                </li>
                <li><a href="?page=gerer_ticket_admin" class="<?php echo ($page === 'gerer_ticket_admin') ? 'active' : ''; ?>"><i data-lucide="ticket" class="w-5 h-5"></i> Gérer les Tickets</a></li>
                <li><a href="?page=valider_tickets" class="<?php echo ($page === 'valider_tickets') ? 'active' : ''; ?>"><i data-lucide="check-circle" class="w-5 h-5"></i> Valider les Tickets</a></li>
                <li><a href="?page=gerer_categorie_evenement" class="<?php echo ($page === 'gerer_categorie_evenement') ? 'active' : ''; ?>"><i data-lucide="tags" class="w-5 h-5"></i> Gérer les Catégories</a></li>
                <li><a href="?page=rapport_statistique" class="<?php echo ($page === 'rapport_statistique') ? 'active' : ''; ?>"><i data-lucide="bar-chart-3" class="w-5 h-5"></i> Rapports et Stats</a></li>
                <li><a href="?page=admin_parametres_site" class="<?php echo ($page === 'admin_parametres_site') ? 'active' : ''; ?>"><i data-lucide="settings" class="w-5 h-5"></i> Paramètres du Site</a></li>
            </ul>
        </aside>

        <main class="main-content animate_animated animate_fadeInRight">
            <?php if ($page === 'dashboard'): ?>
                <h1 class="section-title animate_animated animate_fadeIn">Aperçu du Système</h1>

                <div class="dashboard-cards">
                    <div class="card users animate_animated animate_zoomIn">
                        <i data-lucide="users" class="icon-bg"></i>
                        <h3>Total Utilisateurs</h3>
                        <p><?php echo $totalUsers; ?></p>
                    </div>
                    <div class="card events animate_animated animatezoomIn animate_delay-0-1s">
                        <i data-lucide="calendar" class="icon-bg"></i>
                        <h3>Total Événements</h3>
                        <p><?php echo $totalEvents; ?></p>
                    </div>
                    <div class="card tickets animate_animated animatezoomIn animate_delay-0-2s">
                        <i data-lucide="ticket" class="icon-bg"></i>
                        <h3>Tickets Vendus</h3>
                        <p><?php echo $totalTicketsSold; ?></p>
                    </div>
                    <div class="card revenue animate_animated animatezoomIn animate_delay-0-3s">
                        <i data-lucide="dollar-sign" class="icon-bg"></i>
                        <h3>Chiffre d'Affaires Total</h3>
                        <p><?php echo number_format($totalRevenue, 2, ',', ' '); ?> CFA</p>
                    </div>
                </div>

                <h1 class="section-title animate_animated animate_fadeIn">Actions Rapides</h1>
                <div class="quick-actions">
                    <a href="?page=admin_modifier_motdepasse" class="animate_animated animatefadeInUp animate_delay-0-2s">
                        <i data-lucide="key" class="w-5 h-5"></i> Modifier mon mot de passe
                    </a>
                    <a href="?page=messagerie" class="animate_animated animatefadeInUp animate_delay-0-3s">
                        <i data-lucide="mail" class="w-5 h-5"></i> Paramètres Email
                    </a>
                </div>
            <?php else: ?>
                <?php echo $page_content; ?>
            <?php endif; ?>

        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>

</html>
