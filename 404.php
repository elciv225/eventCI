<?php
// 404.php - Page d'erreur 404
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - 404</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            text-align: center;
        }

        .error-container {
            max-width: 600px;
            padding: 36px 0;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: var(--text-highlight);
            margin: 0;
            line-height: 1;
        }
        
        .error-title {
            font-size: 32px;
            font-weight: 700;
            margin: 20px 0;
        }
        
        .error-message {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 30px;
        }
        
        .error-image {
            max-width: 300px;
            margin: 20px auto;
        }
        
        .home-link {
            display: inline-block;
            background-color: var(--text-highlight);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }
        
        .home-link:hover {
            background-color: #e65a28;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Oups ! Vous êtes perdu ?</h2>
        <p class="error-message">Il semble que vous ayez pris un mauvais virage dans le cyberespace. Même notre GPS numérique ne peut pas trouver cette page !</p>
        
        <div class="error-image">
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 15h8M9 9h.01M15 9h.01"></path>
            </svg>
        </div>
        
        <p class="error-message">Ne vous inquiétez pas, il arrive même aux meilleurs d'entre nous de se perdre parfois. Retournez à l'accueil pour retrouver votre chemin.</p>
        
        <a href="index.php" class="home-link">Retour à l'accueil</a>
    </div>
</body>
</html>