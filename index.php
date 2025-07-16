<?php
session_start();

// On va récuperer tous les évènement
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evently - Accueil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/accueil.css">
    <link rel="stylesheet" href="assets/css/creation-evenement.css">
</head>
<body data-theme="dark">

<div class="page-wrapper">
    <!-- Header -->
    <header>
        <div class="header-left">
            <div class="logo-container">
                <svg class="logo-svg" viewBox="0 0 48 48" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M24 18.4228L42 11.475V34.3663C42 34.7796 41.7457 35.1504 41.3601 35.2992L24 42V18.4228Z"></path>
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M24 8.18819L33.4123 11.574L24 15.2071L14.5877 11.574L24 8.18819ZM9 15.8487L21 20.4805V37.6263L9 32.9945V15.8487ZM27 37.6263V20.4805L39 15.8487V32.9945L27 37.6263ZM25.354 2.29885C24.4788 1.98402 23.5212 1.98402 22.646 2.29885L4.98454 8.65208C3.7939 9.08038 3 10.2097 3 11.475V34.3663C3 36.0196 4.01719 37.5026 5.55962 38.098L22.9197 44.7987C23.6149 45.0671 24.3851 45.0671 25.0803 44.7987L42.4404 38.098C43.9828 37.5026 45 36.0196 45 34.3663V11.475C45 10.2097 44.2061 9.08038 43.0155 8.65208L25.354 2.29885Z"></path>
                </svg>
                <h2>Evently</h2>
            </div>
            <nav class="header-nav">
                <a href="#">Explorer</a>
                <a href="?page=creation-evenement">Créer</a>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                     viewBox="0 0 256 256" id="theme-icon">
                    <path d="M233.54,142.23a8,8,0,0,0-8-2,88.08,88.08,0,0,1-109.8-109.8,8,8,0,0,0-10-10,104.84,104.84,0,0,0-52.91,37A104,104,0,0,0,136,224a103.09,103.09,0,0,0,62.52-20.88,104.84,104.84,0,0,0,37-52.91A8,8,0,0,0,233.54,142.23ZM188.9,190.34A88,88,0,0,1,65.66,67.11a89,89,0,0,1,31.4-26A106,106,0,0,0,96,56,104.11,104.11,0,0,0,200,160a106,106,0,0,0,14.92-1.06A89,89,0,0,1,188.9,190.34Z"></path>
                </svg>
            </button>
            <div class="profile-pic"
                 style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCzg14wleDglhrpOM32buaCFnSBoEvEJyU3BYLkrsf2JxZlT6ohRZEcKu32iyCBZrbkT8-phgtNUDVk7Tw44tldQFCJOBwsdAcH3XYm_JQDztSURWJv60VA4YYeRmhuhxz8lV_HWsKqshQe7Atm5b4DtZVTUC1AJDKe8YZLm_RM3S51R0MVVbYSopsjksHdciQaU0udO_kqmibaYxffLEMfGTsDU7k2DpxzHPflxQCr5QQ4xiNsEDO8lbl_ak5fV-SsPJo6ISJv0opO");'></div>
        </div>
    </header>

    <!-- Main Content -->
    <?php // switch avec les gets
    $page = $_GET['page'] ?? 'accueil';
    switch ($page) {
        case '':
            include 'public/accueil.php';
            break;
        case 'accueil':
            include 'public/accueil.php';
            break;
        case 'creation-evenement':
            include 'public/creation-evenement.php';
            break;
        default:
            header('Location: 404.php');
    }
    ?>

</div>

<script src="assets/js/accueil.js"></script>
</body>
</html>
