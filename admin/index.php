<?php
session_start();
require_once '../config/base.php';

// Vérification que l'utilisateur connecté est un administrateur.
if ($_SESSION['utilisateur']['id'] !== -1) {
    header("Location: ../authentification.php");
    exit("Accès refusé.");
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
    <title>Tableau de Bord Administrateur - Professionnel</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700;800&display=swap"
        rel="stylesheet">
    <!-- Removed Font Awesome, replaced with Lucide for consistency -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        /* Modernized Color Palette & Design Tokens */
        :root {
            --primary-gradient: linear-gradient(135deg, #FF9900 0%, #FF6600 100%); /* Vibrant Orange */
            --secondary-gradient: linear-gradient(135deg, #4A5568 0%, #718096 100%); /* Dark Gray to Medium Gray */
            --header-bg: #FFFFFF; /* Pure White */
            --sidebar-bg: #FFFFFF; /* Pure White */
            --main-bg: #F8F9FA; /* Light Grayish White */
            --card-bg: #FFFFFF; /* Pure White */
            --text-dark: #2D3748; /* Dark Gray */
            --text-medium: #4A5568; /* Medium Gray */
            --text-light: #718096; /* Light Gray */
            --accent-orange: #FF8C00; /* Darker Orange */
            --accent-dark-orange: #E67E22; /* Burnt Orange */
            --accent-red: #E53E3E; /* Red for logout */
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 6px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 15px 30px rgba(0, 0, 0, 0.15);
            --border-radius-lg: 16px;
            --border-radius-md: 10px;
            --border-radius-xl: 24px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--main-bg);
            margin: 0;
            padding: 0;
            color: var(--text-medium);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* --- Global Reset & Utilities --- */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Montserrat', sans-serif;
            color: var(--text-dark);
            margin-top: 0; /* Reset default margin */
            margin-bottom: 0; /* Reset default margin */
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* --- Header --- */
        .header {
            background: var(--header-bg);
            padding: 20px 40px;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom-left-radius: var(--border-radius-md);
            border-bottom-right-radius: var(--border-radius-md);
        }

        .header span {
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.5px;
            font-size: 1.2em; /* Slightly larger */
            color: var(--text-dark);
        }

        .header .logout-btn {
            background: linear-gradient(45deg, #FF6F61 0%, #E04B3F 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 0.9em; /* Slightly larger */
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 111, 97, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            border: none; /* Remove default button border */
        }

        .header .logout-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 15px rgba(255, 111, 97, 0.6);
            filter: brightness(1.1); /* Subtle brightness increase */
        }

        /* --- Main Container --- */
        .container {
            display: flex;
            min-height: calc(100vh - 80px); /* Adjusted for header */
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            padding: 30px 0;
            box-shadow: var(--shadow-lg); /* Stronger shadow */
            color: var(--text-medium);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 80px; /* Starts after the header */
            height: calc(100vh - 80px);
            overflow-y: auto;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            border-top-right-radius: var(--border-radius-xl); /* Rounded top-right */
            border-bottom-right-radius: var(--border-radius-xl); /* Rounded bottom-right */
        }

        .sidebar h2 {
            text-align: center;
            color: var(--accent-orange);
            margin-bottom: 40px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1); /* Slightly more prominent */
            padding-bottom: 25px;
            font-size: 1.1em; /* Larger font size */
            letter-spacing: 1.5px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
        }

        .sidebar ul {
            list-style: none;
            padding: 0 25px;
            flex-grow: 1;
        }

        .sidebar ul li {
            margin-bottom: 12px;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-dark);
            text-decoration: none;
            padding: 16px 20px;
            border-radius: var(--border-radius-md);
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95em; /* Slightly larger */
            position: relative;
            overflow: hidden;
            z-index: 1; /* Ensure content is above ::before */
        }

        .sidebar ul li a svg { /* Targeting Lucide SVG directly */
            font-size: 1.3em;
            color: var(--accent-orange);
            transition: color 0.3s ease;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 123, 0, 0.15);
            color: var(--accent-dark-orange);
            transform: translateX(8px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar ul li a.active {
            font-weight: 700;
            background: var(--primary-gradient);
            box-shadow: 0 8px 25px rgba(255, 123, 0, 0.5); /* Stronger shadow */
            color: white;
        }

        .sidebar ul li a.active svg { /* Targeting Lucide SVG directly */
            color: white;
        }

        /* "Wave" effect on sidebar links on hover */
        .sidebar ul li a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1); /* Lighter, more subtle wave */
            transition: all 0.4s ease-in-out;
            transform: skewX(-20deg);
            z-index: -1; /* Behind content */
        }

        .sidebar ul li a:hover::before {
            left: 100%;
        }


        /* --- Main Content --- */
        .main-content {
            flex-grow: 1;
            padding: 40px;
            background-color: var(--card-bg); /* Pure white background */
            margin: 25px;
            border-radius: var(--border-radius-xl); /* Larger radius */
            box-shadow: var(--shadow-lg); /* Stronger shadow */
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        /* Subtle background pattern for main content */
        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://www.transparenttextures.com/patterns/clean-gray-paper.png');
            opacity: 0.03; /* Even lighter */
            z-index: -1;
        }

        .section-title {
            font-size: 2.2em; /* Larger font size */
            color: var(--text-dark);
            margin-bottom: 35px;
            border-bottom: 4px solid var(--accent-orange); /* Thicker, vibrant border */
            padding-bottom: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px; /* Increased letter spacing */
            font-family: 'Montserrat', sans-serif;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.05); /* More subtle shadow */
        }

        /* --- Dashboard Cards --- */
        .dashboard-cards {
            display: grid;
            /* Force 4 columns on larger screens */
            grid-template-columns: repeat(4, 1fr);
            gap: 15px; /* Reduced gap between cards */
            margin-bottom: 50px;
        }

        .card {
            background: var(--card-bg);
            padding: 15px; /* Significantly reduced padding */
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm); /* Lighter initial shadow */
            text-align: center;
            border-bottom: 8px solid;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            z-index: 1; /* Ensure content is above ::before */
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0.08; /* Slightly more visible */
            filter: grayscale(100%) blur(1px); /* Subtle blur added */
            z-index: -1;
            transition: all 0.5s ease;
            transform: scale(1.05);
        }

        .card:hover {
            transform: translateY(-8px) scale(1.01); /* Slightly less pronounced lift */
            box-shadow: var(--shadow-md); /* Stronger shadow on hover */
        }

        .card:hover::before {
            opacity: 0.15; /* More visible on hover */
            transform: scale(1);
            filter: grayscale(80%) blur(0px); /* Less grayscale, no blur on hover */
        }

        .card h3 {
            color: var(--text-dark);
            margin-top: 0;
            font-size: 0.8em; /* Further reduced title size for compactness */
            font-weight: 700;
            margin-bottom: 8px; /* Reduced margin */
            position: relative;
            z-index: 2;
            letter-spacing: 0.5px; /* Reduced letter spacing for compactness */
            text-transform: uppercase;
        }

        .card p {
            font-size: 2.5em; /* Further reduced font size for compactness */
            font-weight: 800;
            color: var(--accent-dark-orange);
            margin: 0;
            position: relative;
            z-index: 2;
            line-height: 1;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.08); /* Slightly less strong text shadow */
        }

        .card .icon-bg {
            position: absolute;
            top: 5px; /* Adjusted position */
            right: 10px; /* Adjusted position */
            font-size: 2.5em; /* Reduced icon size */
            color: rgba(0, 0, 0, 0.04); /* Even lighter, more subtle */
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .card:hover .icon-bg {
            transform: rotate(15deg) scale(1.1); /* More rotation and scale */
        }

        /* Specific card colors and background images */
        .card.users { border-color: #3B82F6; } /* Blue */
        .card.users::before {
            background-image: url('https://images.unsplash.com/photo-1517486804561-12502ec3b499?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcxMzJ8MHwxfHNlYXJjaHw1fHx1c2VyJTIwcHJvZmlsZSUyMGJhY2tncm91bmR8ZW58MHx8fHwxNzIwOTgwNTQwfDA&ixlib=rb-4.0.3&q=80&w=1080');
        }

        .card.events { border-color: #A855F7; } /* Purple */
        .card.events::before {
            background-image: url('https://images.unsplash.com/photo-1514525253164-ffc749007f7a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1NTA5MzR8MHwxfHNlYXJjaHw3fHxldmVudCUyMGJhY2tncm91bmR8ZW58MHx8fHwxNzIwOTgwNTgwfDA&ixlib=rb-4.0.3&q=80&w=1080');
        }

        .card.tickets { border-color: #22C55E; } /* Green */
        .card.tickets::before {
            background-image: url('https://images.unsplash.com/photo-1582236371191-23d3856e8976?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcxMzJ8MHwxfHNlYXJjaHwzfHx0aWNrZXRzJTIwYmFja2dyb3VuZHxlbnwwfHx8fDE3MjA5ODA1OTZ8MA&ixlib=rb-4.0.3&q=80&w=1080');
        }

        .card.revenue { border-color: #F97316; } /* Orange */
        .card.revenue::before {
            background-image: url('https://images.unsplash.com/photo-1549925232-a5e1e5e0d4d2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1MDcxMzJ8MHwxfHNlYXJjaHwzfHxyZXZlbnVlJTIwYmFja2dyb3VuZHxlbnwwfHx8fDE3MjA5ODA1ODN8MA&ixlib=rb-4.0.3&q=80&w=1080');
        }

        /* --- Quick Actions --- */
        .quick-actions {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Slightly wider buttons */
            gap: 25px;
        }

        .quick-actions a {
            background: var(--primary-gradient);
            color: white;
            padding: 18px 30px; /* More padding */
            border-radius: 30px;
            font-size: 1em; /* Larger font size */
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 123, 0, 0.4);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: center;
            border: 2px solid transparent;
        }

        .quick-actions a:hover {
            background: white;
            color: var(--accent-orange);
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 20px rgba(255, 123, 0, 0.6);
            border-color: var(--accent-orange);
        }

        .quick-actions a svg { /* Targeting Lucide SVG directly */
            font-size: 1.3em; /* Larger icon */
            color: white;
            transition: color 0.3s ease;
        }

        .quick-actions a:hover svg { /* Targeting Lucide SVG directly */
            color: var(--accent-orange);
        }

        /* --- Responsive Design --- */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                padding-bottom: 0;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
                border-radius: 0; /* Remove specific border radius for full width */
            }

            .sidebar h2 {
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 0 10px;
            }

            .sidebar ul li {
                margin: 5px 10px;
            }

            .sidebar ul li a {
                padding: 10px 15px;
                gap: 8px;
                font-size: 0.9em;
            }

            .sidebar ul li a.active::before {
                display: none;
            }

            .main-content {
                margin: 20px 15px;
                padding: 25px;
                border-radius: var(--border-radius-lg); /* Smaller radius for mobile */
            }

            .dashboard-cards {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 20px;
            }

            .card .icon-bg {
                font-size: 3em;
            }

            .section-title {
                font-size: 1.8em; /* Adjusted for smaller screens */
            }
        }

        @media (max-width: 768px) { /* Tablet breakpoint */
            .header {
                padding: 15px 20px;
            }
            .header span {
                font-size: 1em;
            }
            .header .logout-btn {
                padding: 10px 20px;
                font-size: 0.8em;
            }

            .main-content {
                margin: 15px;
                padding: 20px;
            }

            .dashboard-cards {
                grid-template-columns: 1fr; /* Stack cards on smaller tablets */
                gap: 15px;
            }

            .card h3 {
                font-size: 1em;
            }

            .card p {
                font-size: 3em;
            }

            .quick-actions {
                grid-template-columns: 1fr; /* Stack quick actions */
                gap: 15px;
            }

            .quick-actions a {
                padding: 15px 20px;
                font-size: 0.9em;
            }
        }

        @media (max-width: 480px) { /* Mobile breakpoint */
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 15px;
            }
            .header .logout-btn {
                margin-top: 10px;
                width: 100%;
                justify-content: center;
            }
            .main-content {
                margin: 10px;
                padding: 15px;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .sidebar ul {
                flex-direction: column;
                align-items: center;
                padding: 0 5px;
            }

            .sidebar ul li {
                width: 95%;
                text-align: center;
                margin-bottom: 8px;
            }

            .sidebar ul li a {
                justify-content: center;
                font-size: 0.85em;
                padding: 12px 15px;
            }

            .section-title {
                font-size: 1.5em;
                text-align: center;
                margin-bottom: 20px;
            }

            .card h3 {
                font-size: 0.9em;
            }

            .card p {
                font-size: 2.5em;
            }

            .quick-actions a {
                font-size: 0.85em;
                padding: 12px 15px;
            }
        }
    </style>
</head>

<body>
    <header class="header animate_animated animate_fadeInDown">
        <span>Tableau de Bord Administrateur</span>
        <a href="../authentification.php?logout=1" class="logout-btn animate_animated animatepulse animate_infinite">
            <i data-lucide="log-out" class="w-5 h-5"></i> Déconnexion
        </a>
    </header>

    <div class="container">
        <aside class="sidebar animate_animated animate_fadeInLeft">
            <h2>Navigation Admin</h2>
            <ul>
                <li><a href="composants/menu_administrateur.php" class="active"><i data-lucide="layout-dashboard" class="w-5 h-5"></i> Tableau de
                        Bord</a></li>
                <li><a href="composants/gerer_utilisateur.php"><i data-lucide="users" class="w-5 h-5"></i> Gérer les Utilisateurs</a></li>
                <li><a href="composants/admin_gerer_evenement.php"><i data-lucide="calendar" class="w-5 h-5"></i> Gérer les Événements</a>
                </li>
                <li><a href="composants/gerer_ticket_admin.php"><i data-lucide="ticket" class="w-5 h-5"></i> Gérer les Tickets</a></li>
                <li><a href="composants/gerer_categorie_evenement.php"><i data-lucide="tags" class="w-5 h-5"></i> Gérer les Catégories</a></li>
                <li><a href="composants/rapport_statistique.php"><i data-lucide="bar-chart-3" class="w-5 h-5"></i> Rapports et Stats</a></li>
                <li><a href="composants/admin_parametres_site.php"><i data-lucide="settings" class="w-5 h-5"></i> Paramètres du Site</a></li>
            </ul>
        </aside>

        <main class="main-content animate_animated animate_fadeInRight">
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
                <a href="composants/admin_modifier_motdepasse.php" class="animate_animated animatefadeInUp animate_delay-0-2s">
                    <i data-lucide="key" class="w-5 h-5"></i> Modifier mon mot de passe
                </a>
                <a href="composants/messagerie.php" class="animate_animated animatefadeInUp animate_delay-0-3s">
                    <i data-lucide="mail" class="w-5 h-5"></i> Paramètres Email
                </a>
            </div>

        </main>
    </div>

    <script>
        // Initialize Lucide icons after the DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>

</html>
