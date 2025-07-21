<?php


// Traitement d'une validation ou d'un rejet
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if (in_array($action, ['approuve', 'rejete'])) {
        $stmt = $conn->prepare("UPDATE evenement SET statut_approbation = ? WHERE Id_Evenement = ?");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Récupération des événements en attente
$sql = "SELECT Id_Evenement, Titre, Description, Adresse, DateDebut, DateFin FROM evenement WHERE statut_approbation = 'en_attente'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Validation des Événements - IvoireEvent</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- Variables CSS (Couleurs Orange & Blanc) --- */
        :root {
            --primary-orange: #FF8C00;
            /* Orange vif */
            --primary-orange-dark: #E67A00;
            /* Orange plus foncé pour le hover */
            --text-dark: #333333;
            /* Texte principal foncé */
            --text-light: #FFFFFF;
            /* Texte blanc sur fonds foncés */
            --bg-light: #FDFDFD;
            /* Fond très clair, presque blanc */
            --bg-white: #FFFFFF;
            /* Fond blanc pur */
            --border-light: #E0E0E0;
            /* Bordures claires */
            --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.12);
            --border-radius-lg: 12px;
            --border-radius-md: 8px;
            --border-radius-sm: 6px;
        }

        /* --- Global Styles --- */
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            padding: 20px 10px;
            color: var(--text-dark);
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: auto;
            font-size: 0.95rem;
            background-image: url('image/nainoa-shizuru-NcdG9mK3PBY-unsplash.jpg');
            background-size: cover;
            background-position: center;
        }

        /* --- Page Title --- */
        h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-orange);
            text-align: center;
            margin-bottom: 40px;
            font-size: 1em;
            font-weight: 700;
            letter-spacing: 0.5px;
            position: relative;
            padding-bottom: 15px;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary-orange);
            border-radius: 2px;
        }

        /* --- Event Card --- */
        .event {
            background: var(--bg-white);
            padding: 30px;
            /* Augmenté le padding pour plus d'espace */
            border-left: 8px solid var(--primary-orange);
            /* Bordure gauche orange plus épaisse */
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-medium);
            /* Ombre plus prononcée */
            margin-bottom: 30px;
            max-width: 800px;
            /* Plus large pour mieux présenter les infos */
            width: 100%;
            /* Assure qu'il prend toute la largeur disponible sur mobile */
            box-sizing: border-box;
            /* Inclure padding et border dans la largeur */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .event:hover {
            transform: translateY(-5px);
            /* Léger soulèvement au survol */
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            /* Ombre intensifiée */
        }

        .event h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1em;
            color: var(--text-dark);
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-light);
            /* Séparateur sous le titre */
            padding-bottom: 10px;
        }

        .event p {
            margin-bottom: 10px;
            font-size: 0.5em;
            color: #555;
        }

        .event p strong {
            color: var(--text-dark);
        }

        /* --- Buttons --- */
        .event form {
            margin-top: 25px;
            /* Plus d'espace au-dessus des boutons */
            display: flex;
            /* Utilisation de flexbox pour l'alignement des boutons */
            flex-wrap: wrap;
            /* Permet aux boutons de passer à la ligne */
            gap: 15px;
            /* Espacement entre les boutons */
            align-items: center;
        }

        .event button {
            padding: 12px 22px;
            /* Padding augmenté */
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            display: inline-flex;
            /* Pour centrer l'icône et le texte si utilisés */
            align-items: center;
            justify-content: center;
            gap: 8px;
            /* Espace entre l'icône et le texte */
        }

        .event button:hover {
            transform: translateY(-2px);
            /* Léger soulèvement au survol */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            /* Légère ombre au survol */
        }

        .accept {
            background-color: #28a745;
            /* Vert pour l'acceptation */
            color: white;
        }

        .accept:hover {
            background-color: #218838;
        }

        .reject {
            background-color: #dc3545;
            /* Rouge pour le rejet */
            color: white;
        }

        .reject:hover {
            background-color: #c82333;
        }

        .comment {
            background-color: var(--primary-orange);
            /* Orange pour la remarque */
            color: var(--text-light);
        }

        .comment:hover {
            background-color: var(--primary-orange-dark);
        }

        /* --- Comment Box --- */
        .comment-box {
            display: none;
            /* Masqué par défaut */
            margin-top: 20px;
            /* Plus d'espace */
            width: 100%;
            /* Prend toute la largeur disponible */
        }

        textarea {
            width: 100%;
            padding: 12px;
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--border-light);
            font-family: 'Inter', sans-serif;
            resize: vertical;
            min-height: 100px;
            /* Hauteur minimale */
            font-size: 1em;
            box-sizing: border-box;
            /* Important pour que le padding n'augmente pas la largeur */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        textarea:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
            /* Halo orange au focus */
            outline: none;
        }

        .send-remark {
            background-color: #007bff;
            /* Bleu pour l'envoi de remarque, contraste bien avec l'orange */
            color: white;
            align-self: flex-end;
            /* Aligner à droite si flex-direction est row */
        }

        .send-remark:hover {
            background-color: #0056b3;
        }

        /* --- No Events Message --- */
        .no-events-message {
            text-align: center;
            padding: 30px;
            background-color: var(--bg-white);
            border: 1px dashed var(--border-light);
            border-radius: var(--border-radius-md);
            color: #666;
            font-size: 0.7em;
            max-width: 600px;
            margin: 50px auto;
            box-shadow: var(--shadow-light);
        }

        /* --- Responsive Adjustments --- */
        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }

            h2 {
                font-size: 2em;
                margin-bottom: 30px;
            }

            .event {
                padding: 20px;
            }

            .event h3 {
                font-size: 1.5em;
            }

            .event button {
                width: 100%;
                /* Boutons prennent toute la largeur sur mobile */
                margin-right: 0;
                margin-bottom: 10px;
                /* Espacement vertical */
            }

            .event form {
                flex-direction: column;
                /* Empile les boutons */
                gap: 10px;
            }

            .send-remark {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 1.8em;
            }

            .event {
                padding: 15px;
            }

            .event h3 {
                font-size: 1.3em;
            }

            .event p {
                font-size: 0.95em;
            }

            .event button {
                font-size: 0.9em;
                padding: 10px 15px;
            }
        }
    </style>
</head>

<body>

    <h2>Événements en attente de validation</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($event = $result->fetch_assoc()): ?>
            <div class="event">
                <h3><?= htmlspecialchars($event['Titre']) ?></h3>
                <p><?= nl2br(htmlspecialchars($event['Description'])) ?></p>
                <p><strong>Adresse :</strong> <?= htmlspecialchars($event['Adresse']) ?></p>
                <p><strong>Du :</strong> <?= $event['DateDebut'] ?> <strong>au</strong> <?= $event['DateFin'] ?></p>

                <form method="POST" action="admin_gerer_evenement.php">
                    <input type="hidden" name="id" value="<?= $event['Id_Evenement'] ?>">
                    <button class="accept" name="action" value="approuve"><i class="fas fa-check-circle"></i> Approuver</button>
                    <button class="reject" name="action" value="rejete"><i class="fas fa-times-circle"></i> Rejeter</button>
                    <button type="button" onclick="toggleComment(this)" class="comment"><i class="fas fa-comment-dots"></i>
                        Envoyer une remarque</button>

                    <div class="comment-box">
                        <textarea name="remarque" rows="4" placeholder="Votre remarque à l’organisateur..."></textarea>
                        <button type="submit" name="action" value="remarque" class="send-remark"><i
                                class="fas fa-paper-plane"></i> Envoyer la remarque</button>
                    </div>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-events-message">Aucun événement en attente de validation pour le moment. Tout est à jour !</p>
    <?php endif; ?>

    <script>
        function toggleComment(btn) {
            const box = btn.closest("form").querySelector(".comment-box");
            box.style.display = box.style.display === 'none' ? 'flex' : 'none'; // Changed to 'flex' for better button alignment
        }
    </script>

</body>

</html>