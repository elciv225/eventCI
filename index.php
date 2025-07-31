<?php
// Démarrer la mise en tampon de sortie. TOUT le contenu sera stocké dans une mémoire tampon.
ob_start();

session_start();

require 'config/base.php';

// Variables pour les messages
$success_message = '';
$error_message = '';

// Récupérer les messages de session s'ils existent
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Récupérer la page courante
$page = $_GET['page'] ?? 'accueil';

// Récupérer les informations de l'utilisateur connecté
$user_info = null;
if (isset($_SESSION['utilisateur']) && !empty($_SESSION['utilisateur']['id'])) {
    $user_info = [
        'Nom' => $_SESSION['utilisateur']['nom'] ?? '',
        'Prenom' => $_SESSION['utilisateur']['prenom'] ?? '',
        'Photo' => $_SESSION['utilisateur']['photo'] ?? ''
    ];
}

// ---- DÉBUT DE LA LOGIQUE DE ROUTAGE ----
// Le contenu de la page est généré et stocké dans la mémoire tampon ici.
// Si une page incluse (comme creation-evenement.php) appelle header(), cela fonctionnera
// car la mémoire tampon n'a pas encore été envoyée au navigateur.

// On inclut le contenu de la page demandée.
switch ($page) {
    case '':
    case 'accueil':
        include 'public/accueil.php';
        break;
    case 'creation-evenement':
        include 'public/creation-evenement.php';
        break;
    case 'recherche':
        include 'public/recherche.php';
        break;
    case 'mon-profil':
        include 'public/profil.php';
        break;
    case 'details':
        include 'public/details.php';
        break;
    case 'mes-ticket':
        include 'public/panier.php';
        break;
    case 'commande':
        include 'public/commande.php';
        break;
    case 'ticket':
        include 'public/ticket.php';
        break;
    case 'confirmation':
        include 'public/confirmation.php';
        break;
    default:
        ob_end_clean();
        header('Location: 404.php');
        exit;
}

// ---- FIN DE LA LOGIQUE DE ROUTAGE ----

// On récupère le contenu de la page depuis la mémoire tampon et on la vide.
$page_content = ob_get_clean();

// Maintenant que toute la logique est terminée, on peut commencer à envoyer le HTML.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0,maximum-scale=1,user-scalable=no">
    <title>Evently - <?php echo ucfirst($page); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.13.0/mapbox-gl.css" rel="stylesheet">
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.13.0/mapbox-gl.js"></script>
    <link rel="stylesheet" href="assets/css/accueil.css">
    <link rel="stylesheet" href="assets/css/creation-evenement.css">
    <link rel="stylesheet" href="assets/css/popup.css">
    <link rel="stylesheet" href="assets/css/profil.css">
    <link rel="stylesheet" href="assets/css/details.css">
    <link rel="stylesheet" href="assets/css/panier.css">
    <link rel="stylesheet" href="assets/css/ticket.css">
    <link rel="stylesheet" href="assets/css/confirmation.css">
    <link rel="stylesheet" href="assets/css/mapbox.css">
    <link rel="stylesheet" href="assets/css/recherche.css">
    <link rel="icon" href="assets/images/logo.png">
</head>
<body data-theme="light">
<div class="page-wrapper">
    <!-- Header -->
    <header class="header-principale">
        <div class="header-left">
            <div class="logo-container">
                <svg class="logo-svg" viewBox="0 0 400 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <style>
                            .event-text { font-size: 50px; font-weight: bold; }
                            .ci-text { font-size: 50px; font-weight: bold; fill: #e56717; }
                            .event-contour { fill: none; stroke: #fff; stroke-width: 3; stroke-linejoin: round; }
                            .wave { fill: none; stroke: #e56717; stroke-width: 1; }
                        </style>
                    </defs>
                    <g>
                        <path class="wave" d="M 50,50 C 80,20 120,80 150,50 S 220,20 250,50 S 320,80 350,50" opacity="0.6"/>
                        <path class="wave" d="M 55,55 C 85,25 125,85 155,55 S 225,25 255,55 S 325,85 355,55" opacity="0.6"/>
                        <path class="wave" d="M 60,60 C 90,30 130,90 160,60 S 230,30 260,60 S 330,90 360,60" opacity="0.6"/>
                    </g>
                    <g>
                        <text class="event-text" x="20" y="70" fill="#333" stroke="#fff" stroke-width="3" stroke-linejoin="round">Events</text>
                        <text class="event-text" x="20" y="70" fill="#333">Events</text>
                        <text class="ci-text" x="240" y="70">CI</text>
                    </g>
                </svg>
            </div>
            <nav class="header-nav">
                <a href="?page=accueil" <?php echo ($page === 'accueil' || $page === '') ? 'class="active"' : ''; ?>>Accueil</a>
                <a href="?page=recherche" <?php echo ($page === 'recherche') ? 'class="active"' : ''; ?>>Explorer</a>
                <?php if ($user_info): ?>
                    <a href="?page=mes-ticket" <?php echo ($page === 'mes-ticket') ? 'class="active"' : ''; ?>>Mes
                        tickets</a>
                    <a href="?page=mon-profil" <?php echo ($page === 'mon-profil') ? 'class="active"' : ''; ?>>Mon
                        Profil</a>
                    <a href="?page=creation-evenement" <?php echo ($page === 'creation-evenement') ? 'class="active"' : ''; ?>>Créer</a>
                <?php else: ?>
                    <a href="authentification.php" class="login-button mobile">Connexion / Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="header-right">
            <div class="desktop-search search-container"><input type="text" placeholder="Rechercher..."
                                                                class="search-input"/>
                <div class="search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                         viewBox="0 0 256 256">
                        <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                    </svg>
                </div>
            </div>
            <button class="icon-btn" id="theme-toggle" title="Changer de thème (clair/sombre)">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"
                     id="theme-icon">
                    <path d="M233.54,142.23a8,8,0,0,0-8-2,88.08,88.08,0,0,1-109.8-109.8,8,8,0,0,0-10-10,104.84,104.84,0,0,0-52.91,37A104,104,0,0,0,136,224a103.09,103.09,0,0,0,62.52-20.88,104.84,104.84,0,0,0,37-52.91A8,8,0,0,0,233.54,142.23ZM188.9,190.34A88,88,0,0,1,65.66,67.11a89,89,0,0,1,31.4-26A106,106,0,0,0,96,56,104.11,104.11,0,0,0,200,160a106,106,0,0,0,14.92-1.06A89,89,0,0,1,188.9,190.34Z"></path>
                </svg>
            </button>
            <?php if ($user_info): ?>
                <div class="user-menu">
                    <?php if (!empty($user_info['Photo'])): ?>
                        <div class="profile-pic"
                             style='background-image: url("<?php echo htmlspecialchars($user_info['Photo']); ?>");'></div>
                    <?php else: ?>
                        <div class="profile-pic profile-initials">
                            <?php
                            $initials = '';
                            if (!empty($user_info['Prenom'])) $initials .= strtoupper(substr($user_info['Prenom'], 0, 1));
                            if (!empty($user_info['Nom'])) $initials .= strtoupper(substr($user_info['Nom'], 0, 1));
                            echo htmlspecialchars($initials);
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="user-dropdown">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($user_info['Prenom'] . ' ' . $user_info['Nom']); ?></span>
                        </div>
                        <nav class="user-nav">
                            <a href="?page=creation-evenement">Créer un événement</a>
                            <a href="?page=mes-ticket">Mes tickets</a>
                            <a href="?page=mon-profil">Mon profil</a>
                            <a href="authentification.php?logout=1">Déconnexion</a>
                        </nav>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-menu">
                    <a href="authentification.php" class="login-button">Connexion / Inscription</a>
                </div>
            <?php endif; ?>
            <button class="mobile-menu-toggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Page loader -->
    <div class="page-loader" id="page-loader"></div>

    <!-- Mobile menu overlay -->
    <div class="mobile-menu-overlay"></div>

    <!-- Main Content -->
    <?php
    // On affiche le contenu de la page qui a été capturé plus haut
    echo $page_content;
    ?>
</div>

<!-- Popup structure -->
<div class="popup-overlay">
    <div class="popup-container">
        <div class="popup-header">
            <div class="popup-icon"></div>
            <div class="popup-title"></div>
            <button class="popup-close" aria-label="Fermer">×</button>
        </div>
        <div class="popup-content"></div>
        <div class="popup-actions">
            <button class="popup-button popup-button-primary">OK</button>
        </div>
    </div>
</div>

<script src="assets/js/popup.js" defer></script>
<script src="assets/js/accueil.js" defer></script>
<script src="assets/js/creation-evenement.js" defer></script>
<script src="assets/js/mapbox.js" defer></script>
<script src="assets/js/recherche.js" defer></script>
<script>
    // Le code de débogage et les popups sont maintenant exécutés en toute sécurité
    // après le rendu de la page.
    console.log("Session data: ", <?php echo json_encode($_SESSION); ?>);
    console.log("Messages: ", <?php echo json_encode($success_message); ?>, <?php echo json_encode($error_message); ?>);
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (!empty($success_message)): ?>
        showSuccessPopup('<?php echo addslashes($success_message); ?>');
        <?php elseif (!empty($error_message)): ?>
        showErrorPopup('<?php echo addslashes($error_message); ?>');
        <?php endif; ?>

        <?php if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])): ?>
        showFormValidationErrors(<?php echo json_encode($_SESSION['form_errors']); ?>, 'Erreur de validation');
        <?php
        // Supprimer les erreurs de la session après les avoir affichées
        unset($_SESSION['form_errors']);
        ?>
        <?php endif; ?>
    });
</script>
</body>
</html>
