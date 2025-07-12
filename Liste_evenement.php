<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'base.php';

$evenements = [];
$message = '';

$sql = "SELECT
            e.Id_Evenement,
            e.Titre,
            e.Description,
            e.Adresse,
            e.DateDebut,
            e.DateFin,
            e.statut_approbation,
            v.Libelle AS NomVille,
            ce.Libelle AS NomCategorie
        FROM
            evenement e
        JOIN
            ville v ON e.Id_Ville = v.Id_Ville
        JOIN
            categorieevenement ce ON e.Id_CategorieEvenement = ce.Id_CategorieEvenement
        WHERE e.statut_approbation = 'approuve'
        ORDER BY e.DateDebut DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evenement = $row;
        $evenement['images'] = [];

        $sql_images = "SELECT Lien FROM imageevenement WHERE Id_Evenement = ?";
        if ($stmt_images = $conn->prepare($sql_images)) {
            $stmt_images->bind_param("i", $evenement['Id_Evenement']);
            $stmt_images->execute();
            $result_images = $stmt_images->get_result();
            while ($img = $result_images->fetch_assoc()) {
                $evenement['images'][] = $img['Lien'];
            }
            $stmt_images->close();
        }

        $evenements[] = $evenement;
    }
} else {
    $message = "Aucun événement approuvé trouvé.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Événements</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            /* Un gris clair plus doux */
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }

        .container {
            width: 90%;
            margin: 30px auto;
            /* Plus d'espace vertical */
            max-width: 1300px;
            /* Légèrement plus large */
            padding: 0 15px;
            /* Petits paddings sur les côtés */
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            /* Un bleu foncé élégant */
            margin-bottom: 40px;
            /* Plus d'espace en dessous */
            font-size: 2.5em;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05);
        }

        .add-button-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .add-button {
            display: inline-block;
            background-color: #28a745;
            /* Vert vif */
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
        }

        .add-button:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1em;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Styles pour la liste des événements (grille flexible) */
        .event-list {
            display: grid;
            /* Utilisation de CSS Grid pour une meilleure structure de grille */
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            /* 320px min, remplit l'espace */
            gap: 25px;
            /* Plus d'espace entre les cartes */
            justify-content: center;
            padding: 20px 0;
        }

        .event-card {
            background-color: #ffffff;
            border-radius: 12px;
            /* Bords plus arrondis */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            /* Ombre plus prononcée */
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            /* Pour positionner les éléments internes si besoin */
        }

        .event-card:hover {
            transform: translateY(-5px);
            /* Effet de survol subtil */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        /* Contenu de la carte (texte) */
        .card-content {
            padding: 15px 20px 20px;
            /* Plus de padding */
            flex-grow: 1;
            /* Permet au contenu de prendre l'espace disponible */
            display: flex;
            flex-direction: column;
        }

        .event-card h2 {
            font-size: 1.4em;
            /* Titre légèrement plus petit pour tenir */
            margin: 0 0 10px;
            color: #34495e;
            /* Couleur plus foncée */
            font-weight: 600;
            line-height: 1.3;
        }

        .event-card p {
            font-size: 0.95em;
            color: #555;
            margin-bottom: 8px;
        }

        .event-card p:last-of-type {
            margin-bottom: 15px;
            /* Plus d'espace avant les boutons */
        }

        /* Tronquer la description pour qu'elle ne prenne pas trop de place */
        .event-card p:nth-of-type(1) {
            /* La première <p> après le h2 est la description */
            min-height: 40px;
            /* Assure une hauteur minimale pour la description */
            max-height: 80px;
            /* Limite la hauteur pour éviter un trop long texte */
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            /* Limite à 3 lignes */
            -webkit-box-orient: vertical;
        }

        /* Boutons d'action */
        .card-actions {
            padding: 0 20px 15px;
            /* Padding ajusté pour les boutons */
            display: flex;
            flex-wrap: wrap;
            /* Permet aux boutons de passer à la ligne */
            justify-content: center;
            /* Centre les boutons */
            gap: 10px;
            /* Espacement entre les boutons */
            margin-top: auto;
            /* Pousse les actions en bas de la carte */
        }

        .action-button {
            flex: 1 1 auto;
            /* Les boutons s'étirent mais peuvent wrap */
            max-width: 48%;
            /* Deux boutons par ligne sur les petites tailles */
            padding: 10px 12px;
            border-radius: 5px;
            font-size: 0.9em;
            text-align: center;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 600px) {
            .action-button {
                max-width: 100%;
                /* Un bouton par ligne sur très petits écrans */
            }
        }

        .edit-button {
            background-color: #ffc107;
            /* Jaune */
            color: #333;
        }

        .edit-button:hover {
            background-color: #e0a800;
            transform: translateY(-1px);
        }

        .delete-button {
            background-color: #dc3545;
            /* Rouge */
            color: white;
        }

        .delete-button:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        .details-button {
            background-color: #007bff;
            /* Bleu */
            color: white;
        }

        .details-button:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        /* Styles du carrousel */
        .image-carousel {
            position: relative;
            width: 100%;
            padding-top: 60%;
            /* Ratio 5:3 pour l'image (hauteur 60% de la largeur) */
            overflow: hidden;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            /* Petite séparation */
        }

        .carousel-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            transition: opacity 0.6s ease-in-out;
            /* Transition plus douce */
        }

        .carousel-image.active {
            display: block;
        }

        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.6);
            /* Plus opaque */
            color: white;
            border: none;
            padding: 8px 12px;
            /* Plus petit */
            cursor: pointer;
            font-size: 1.2em;
            /* Plus petit */
            border-radius: 50%;
            line-height: 1;
            opacity: 0.8;
            transition: background-color 0.3s ease, opacity 0.3s ease;
            z-index: 10;
        }

        .carousel-button:hover {
            background-color: rgba(0, 0, 0, 0.8);
            opacity: 1;
        }

        .carousel-button.prev {
            left: 10px;
        }

        .carousel-button.next {
            right: 10px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                margin: 20px auto;
            }

            h1 {
                font-size: 2em;
            }

            .event-list {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 15px;
            }

            .event-card {
                width: auto;
                /* Permet aux cartes de s'adapter aux colonnes de la grille */
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Liste des Événements</h1>

        <div class="add-button-container">
            <a href="creer_evenement.php" class="add-button">Créer un nouvel événement</a>
        </div>

        <?php
        if (isset($_GET['msg'])) {
            if ($_GET['msg'] == 'created_success') {
                echo '<div class="message success">Événement créé avec succès !</div>';
            } elseif ($_GET['msg'] == 'deleted_success') {
                echo '<div class="message success">Événement supprimé avec succès !</div>';
            } elseif ($_GET['msg'] == 'deleted_error') {
                $errorMessage = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : "Une erreur est survenue lors de la suppression.";
                echo '<div class="message error">' . $errorMessage . '</div>';
            } elseif ($_GET['msg'] == 'modified_success') {
                echo '<div class="message success">Événement mis à jour avec succès !</div>';
            }
        } elseif (!empty($message) && empty($evenements)) {
            echo '<div class="message error">' . $message . '</div>';
        }
        ?>

        <?php if (!empty($evenements)): ?>
            <div class="event-list">
                <?php foreach ($evenements as $evenement): ?>
                    <div class="event-card">
                        <div class="image-carousel" data-event-id="<?= $evenement['Id_Evenement'] ?>">
                            <?php if (!empty($evenement['images'])): ?>
                                <?php foreach ($evenement['images'] as $index => $imageLien): ?>
                                    <img src="<?= htmlspecialchars($imageLien) ?>" alt="Image de l'événement"
                                        class="carousel-image <?= ($index === 0) ? 'active' : '' ?>">
                                <?php endforeach; ?>
                                <?php if (count($evenement['images']) > 1): ?>
                                    <button class="carousel-button prev">&lt;</button>
                                    <button class="carousel-button next">&gt;</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <img src="image/default.jpg" alt="Image par défaut" class="carousel-image active">
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <h2><?= htmlspecialchars($evenement['Titre']) ?></h2>
                            <p><?= htmlspecialchars(substr($evenement['Description'], 0, 100)) ?><?= (strlen($evenement['Description']) > 100) ? '...' : '' ?>
                            </p>
                            <p>Adresse : <?= htmlspecialchars($evenement['Adresse']) ?></p>
                            <p>Ville : <?= htmlspecialchars($evenement['NomVille']) ?></p>
                            <p>Catégorie : <?= htmlspecialchars($evenement['NomCategorie']) ?></p>
                            <p>Du <?= date('d/m/Y H:i', strtotime($evenement['DateDebut'])) ?>
                                au <?= date('d/m/Y H:i', strtotime($evenement['DateFin'])) ?></p>

                            <?php
                            $statut = $evenement['statut_approbation'];
                            $couleur = ($statut === 'approuve') ? '#2ecc71' : (($statut === 'rejete') ? '#e74c3c' : '#f39c12');
                            ?>
                            <p>
                                Statut :
                                <span
                                    style="padding: 5px 10px; border-radius: 5px; background-color: <?= $couleur ?>; color: white; font-weight: bold;">
                                    <?= ucfirst($statut) ?>
                                </span>
                            </p>

                            <div class="card-actions">
                                <a href="modifier_evenement.php?id=<?= $evenement['Id_Evenement'] ?>"
                                    class="action-button edit-button">Modifier</a>
                                <a href="supprimer_evenement.php?id=<?= $evenement['Id_Evenement'] ?>"
                                    class="action-button delete-button"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');">Supprimer</a>
                                <a href="tickets_par_evenement.php?id=<?= $evenement['Id_Evenement'] ?>"
                                    class="details-button">Voir les tickets</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const carousels = document.querySelectorAll('.image-carousel');

            carousels.forEach(carousel => {
                const images = carousel.querySelectorAll('.carousel-image');
                const prevButton = carousel.querySelector('.carousel-button.prev');
                const nextButton = carousel.querySelector('.carousel-button.next');
                let currentIndex = 0;

                if (images.length <= 1) {
                    if (prevButton) prevButton.style.display = 'none';
                    if (nextButton) nextButton.style.display = 'none';
                }

                function showImage(index) {
                    images.forEach((img, i) => {
                        img.classList.remove('active');
                        if (i === index) {
                            img.classList.add('active');
                        }
                    });
                }

                showImage(currentIndex);

                if (prevButton) {
                    prevButton.addEventListener('click', () => {
                        currentIndex = (currentIndex - 1 + images.length) % images.length;
                        showImage(currentIndex);
                    });
                }

                if (nextButton) {
                    nextButton.addEventListener('click', () => {
                        currentIndex = (currentIndex + 1) % images.length;
                        showImage(currentIndex);
                    });
                }
            });
        });
    </script>