<?php
require_once 'base.php';

// Vérification de l'ID de l'événement
if (!isset($_GET['id'])) {
    header('Location: liste_evenement.php?msg=error&text=Événement+introuvable');
    exit;
}

$id_evenement = intval($_GET['id']);

// Récupération de l'événement + statut d'approbation
$stmt = $conn->prepare("SELECT Titre, statut_approbation FROM evenement WHERE Id_Evenement = ?");
$stmt->bind_param("i", $id_evenement);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: liste_evenement.php?msg=error&text=Aucun+événement+trouvé');
    exit;
}

$evenement = $result->fetch_assoc();

// Bloquer l'accès si l'événement n'est pas approuvé
if ($evenement['statut_approbation'] !== 'approuve') {
    echo 'Les tickets pour cet événement ne sont pas disponibles car il est "' . htmlspecialchars($evenement['statut_approbation']) . '".';
    exit;
}

// Récupération des tickets associés
$stmt = $conn->prepare("SELECT * FROM ticketevenement WHERE Id_Evenement = ?");
$stmt->bind_param("i", $id_evenement);
$stmt->execute();
$ticket_result = $stmt->get_result();

$tickets = [];
while ($row = $ticket_result->fetch_assoc()) {
    $tickets[] = $row;
}

// Récupération des images de l'événement
$stmt = $conn->prepare("SELECT Lien FROM imageevenement WHERE Id_Evenement = ?");
$stmt->bind_param("i", $id_evenement);
$stmt->execute();
$image_result = $stmt->get_result();

$images = [];
while ($img = $image_result->fetch_assoc()) {
    $images[] = $img['Lien'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tickets - <?= htmlspecialchars($evenement['Titre']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f2f4f8;
            margin: 0; padding: 0;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }

        /* Styles pour le carrousel d'images */
        .image-carousel {
            position: relative;
            width: 100%;
            padding-top: 50%;
            overflow: hidden;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
        }
        .carousel-image.active {
            display: block;
        }
        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 1.2em;
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

        /* Styles pour la grille de tickets */
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .ticket-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .ticket-card h2 {
            margin: 0;
            font-size: 1.3em;
            color: #34495e;
        }
        .ticket-card p {
            margin: 10px 0;
            color: #555;
        }
        .price {
            font-weight: bold;
            color: #28a745;
            font-size: 1.2em;
            margin-top: 15px;
        }
        .buy-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .buy-button:hover {
            background-color: #218838;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 35px;
        }
        .back-link a {
            text-decoration: none;
            color: #007bff;
            padding: 10px 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-link a:hover {
            background-color: #e9ecef;
            text-decoration: underline;
        }
        .no-ticket {
            text-align: center;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">
   <h1>Tickets pour : <?= htmlspecialchars($evenement['Titre'] ?? 'Titre non disponible') ?></h1>

    <!-- Carrousel d'images de l'événement -->
    <?php if (!empty($images)): ?>
    <div class="image-carousel" data-event-id="<?= $id_evenement ?>">
        <?php foreach ($images as $index => $imageLien): ?>
            <img src="<?= htmlspecialchars($imageLien) ?>" alt="Image de l'événement" 
                class="carousel-image <?= ($index === 0) ? 'active' : '' ?>">
        <?php endforeach; ?>
        <?php if (count($images) > 1): ?>
            <button class="carousel-button prev">&lt;</button>
            <button class="carousel-button next">&gt;</button>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <div class="image-carousel">
            <img src="image/Didib.jpg" alt="Image par défaut" class="carousel-image active">
        </div>
    <?php endif; ?>

    <!-- Grille de tickets -->
    <?php if (!empty($tickets)): ?>
        <div class="ticket-grid">
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card">
                    <h2><?= htmlspecialchars($ticket['Titre'] ?? 'Titre non défini') ?></h2>
                    <p class="description"><?= htmlspecialchars($ticket['Description'] ?? 'Aucune description') ?></p>
                    <p>Nombre disponible: <?= htmlspecialchars($ticket['NombreDisponible'] ?? '0') ?></p>
                    <p class="price">
                        Prix :
                        <?= isset($ticket['Prix']) ? number_format((float)$ticket['Prix'], 0, ',', ' ') . ' FCFA' : 'Non indiqué' ?>
                    </p>
                    <a href="acheter_tickets.php?id=<?= $ticket['Id_TicketEvenement'] ?>" class="buy-button">Acheter</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-ticket">
            <p>Aucun ticket disponible pour cet événement.</p>
        </div>
    <?php endif; ?>

    <div class="back-link">
        <a href="liste_evenement.php">← Retour à la liste des événements</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const carousel = document.querySelector('.image-carousel');
        if (carousel) {
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
        }
    });
</script>
</body>
</html>
