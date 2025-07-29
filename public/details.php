<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}

// Récupérer l'ID de l'événement depuis l'URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['info-event']) ? intval($_GET['info-event']) : 0);

if ($event_id <= 0) {
    // Rediriger vers la page d'accueil si aucun ID valide n'est fourni
    header('Location: ?page=accueil');
    exit;
}

// Récupérer les données de l'événement depuis la base de données
$event_query = "SELECT 
                e.Id_Evenement, e.Titre, e.Description, e.Adresse, e.DateDebut, e.DateFin,
                e.Latitude, e.Longitude,
                c.Libelle AS categorie, 
                e.Adresse AS ville,
                u.Id_Utilisateur, u.Nom, u.Prenom, u.Photo
              FROM evenement e
              LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
              LEFT JOIN creer cr ON e.Id_Evenement = cr.Id_Evenement
              LEFT JOIN utilisateur u ON cr.Id_Utilisateur = u.Id_Utilisateur
              WHERE e.Id_Evenement = ?";

$stmt = $conn->prepare($event_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Rediriger vers la page d'accueil si l'événement n'existe pas
    header('Location: ?page=accueil');
    exit;
}

$event = $result->fetch_assoc();

// Récupérer les images de l'événement
$images_query = "SELECT Id_ImageEvenement, Titre, Description, Lien 
                FROM imageevenement 
                WHERE Id_Evenement = ?";

$stmt = $conn->prepare($images_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$images_result = $stmt->get_result();

$images = [];
while ($image = $images_result->fetch_assoc()) {
    $images[] = $image;
}

// Récupérer les tickets disponibles pour cet événement
$tickets_query = "SELECT Id_TicketEvenement, Titre, Description, Prix, NombreDisponible 
                 FROM ticketevenement 
                 WHERE Id_Evenement = ?";

$stmt = $conn->prepare($tickets_query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$tickets_result = $stmt->get_result();

$tickets = [];
while ($ticket = $tickets_result->fetch_assoc()) {
    $tickets[] = $ticket;
}

// Formater les dates
$date_debut = new DateTime($event['DateDebut']);
$date_fin = new DateTime($event['DateFin']);
$format_date = 'd/m/Y à H:i';
$date_debut_formatted = $date_debut->format($format_date);
$date_fin_formatted = $date_fin->format($format_date);

// Vérifier si l'utilisateur est connecté
$user_logged_in = isset($_SESSION['utilisateur']) && !empty($_SESSION['utilisateur']['id']);
$user_id = $user_logged_in ? $_SESSION['utilisateur']['id'] : 0;
?>

<main class="page-container">
    <section class="event-details-section">
        <div class="event-header">
            <h1 class="event-title"><?php echo htmlspecialchars($event['Titre']); ?></h1>
            <div class="event-meta">
                <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span> |
                <span class="event-location"><?php echo htmlspecialchars($event['ville']); ?></span>
            </div>
        </div>
        <!-- Galerie d'images -->
        <div class="event-gallery">
            <?php if (empty($images)): ?>
                <div class="event-image-wrapper">
                    <img src="../assets/images/default-event.jpg" alt="<?php echo htmlspecialchars($event['Titre']); ?>"
                         class="event-image">
                </div>
            <?php else: ?>
                <div class="carousel-container">
                    <div class="event-carousel" id="event-carousel">
                        <?php foreach ($images as $image): ?>
                            <div class="event-image-wrapper">
                                <img src="../<?php echo htmlspecialchars($image['Lien']); ?>"
                                     alt="<?php echo htmlspecialchars($image['Titre']); ?>" class="event-image">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-arrow prev" id="carousel-prev" aria-label="Image précédente">&lt;</button>
                        <button class="carousel-arrow next" id="carousel-next" aria-label="Image suivante">&gt;</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="event-content">
            <div class="event-info">
                <div class="event-dates">
                    <div class="event-date-item">
                        <span class="date-label">Début:</span>
                        <span class="date-value"><?php echo $date_debut_formatted; ?></span>
                    </div>
                    <div class="event-date-item">
                        <span class="date-label">Fin:</span>
                        <span class="date-value"><?php echo $date_fin_formatted; ?></span>
                    </div>
                </div>

                <div class="event-address">
                    <h3>Adresse</h3>
                    <p><?php echo htmlspecialchars($event['Adresse']); ?></p>
                </div>

                <?php if (!empty($event['Latitude']) && !empty($event['Longitude'])): ?>
                    <div class="event-map-container">
                        <h3>Localisation</h3>
                        <div id="map" class="event-map" data-lat="<?php echo htmlspecialchars($event['Latitude']); ?>" data-lng="<?php echo htmlspecialchars($event['Longitude']); ?>"></div>
                        <div class="map-actions">
                            <button id="get-directions" class="btn-secondary">Obtenir l'itinéraire</button>
                        </div>
                        <div id="directions-container" class="directions-container" style="display: none;">
                            <h4>Itinéraire</h4>
                            <div id="directions-info"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="event-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($event['Description'])); ?></p>
                </div>
            </div>

            <div class="event-sidebar">
                <!-- Informations sur le créateur -->
                <div class="event-creator">
                    <h3>Organisateur</h3>
                    <div class="creator-info">
                        <div class="creator-pic">
                            <?php if (!empty($event['Photo'])): ?>
                                <img src="../<?php echo htmlspecialchars($event['Photo']); ?>"
                                     alt="Photo de l'organisateur">
                            <?php else: ?>
                                <div class="creator-initials">
                                    <?php
                                    $initials = '';
                                    if (!empty($event['Prenom'])) $initials .= strtoupper(substr($event['Prenom'], 0, 1));
                                    if (!empty($event['Nom'])) $initials .= strtoupper(substr($event['Nom'], 0, 1));
                                    echo htmlspecialchars($initials);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="creator-name">
                            <?php echo htmlspecialchars($event['Prenom'] . ' ' . $event['Nom']); ?>
                        </div>
                    </div>
                </div>

                <!-- Tickets disponibles -->
                <div class="event-tickets">
                    <h3>Tickets disponibles</h3>
                    <?php if (empty($tickets)): ?>
                        <p class="no-tickets">Aucun ticket disponible pour cet événement.</p>
                    <?php else: ?>
                        <div class="tickets-list">
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="ticket-item">
                                    <div class="ticket-info">
                                        <h4 class="ticket-title"><?php echo htmlspecialchars($ticket['Titre']); ?></h4>
                                        <p class="ticket-desc"><?php echo htmlspecialchars($ticket['Description']); ?></p>
                                        <div class="ticket-price"><?php echo number_format($ticket['Prix'], 2, ',', ' '); ?>
                                            €
                                        </div>
                                        <div class="ticket-availability"><?php echo $ticket['NombreDisponible']; ?>
                                            disponibles
                                        </div>
                                    </div>
                                    <div class="ticket-actions">
                                        <a href="?page=commande&ticket=<?php echo $ticket['Id_TicketEvenement']; ?>"
                                           class="btn-primary">Commander</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Gestion du carousel d'images
        const carousel = document.getElementById('event-carousel');
        const prevBtn = document.getElementById('carousel-prev');
        const nextBtn = document.getElementById('carousel-next');

        if (carousel && prevBtn && nextBtn) {
            let currentSlide = 0;
            const slides = carousel.querySelectorAll('.event-image-wrapper');
            const slideCount = slides.length;
            let isTransitioning = false;
            let autoplayInterval = null;

            // Configuration initiale du carousel
            carousel.style.display = 'flex';
            carousel.style.width = `${slideCount * 100}%`;

            slides.forEach((slide, index) => {
                slide.style.width = `${100 / slideCount}%`;
                slide.style.flexShrink = '0';

                const img = slide.querySelector('img');
                if (img) {
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';

                    // Précharger les images pour une transition plus fluide
                    if (index === 0 || index === 1) {
                        img.loading = 'eager';
                    } else {
                        img.loading = 'lazy';
                    }
                }
            });

            // Créer les indicateurs (optionnel)
            if (slideCount > 1) {
                const indicatorsContainer = document.createElement('div');
                indicatorsContainer.className = 'carousel-indicators';

                for (let i = 0; i < slideCount; i++) {
                    const indicator = document.createElement('button');
                    indicator.className = 'carousel-indicator';
                    if (i === 0) indicator.classList.add('active');
                    indicator.addEventListener('click', () => showSlide(i));
                    indicatorsContainer.appendChild(indicator);
                }

                carousel.parentElement.appendChild(indicatorsContainer);
            }

            // Fonction pour afficher un slide spécifique avec animation fluide
            function showSlide(index, direction = null) {
                if (isTransitioning || index === currentSlide) return;

                isTransitioning = true;

                // Gérer les limites
                if (index < 0) index = slideCount - 1;
                if (index >= slideCount) index = 0;

                // Mettre à jour les indicateurs
                const indicators = carousel.parentElement.querySelectorAll('.carousel-indicator');
                indicators.forEach((indicator, i) => {
                    indicator.classList.toggle('active', i === index);
                });

                // Animation fluide
                requestAnimationFrame(() => {
                    carousel.style.transform = `translateX(-${index * (100 / slideCount)}%)`;
                    currentSlide = index;

                    // Réactiver les contrôles après la transition
                    setTimeout(() => {
                        isTransitioning = false;
                    }, 3000); // Correspond à la durée de la transition CSS
                });
            }

            // Fonction pour le défilement automatique
            function startAutoplay() {
                if (slideCount <= 1) return;

                autoplayInterval = setInterval(() => {
                    if (!isTransitioning) {
                        showSlide(currentSlide + 1);
                    }
                }, 4000); // Changer d'image toutes les 4 secondes
            }

            function stopAutoplay() {
                if (autoplayInterval) {
                    clearInterval(autoplayInterval);
                    autoplayInterval = null;
                }
            }

            // Gestion des événements pour les boutons
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                stopAutoplay();
                showSlide(currentSlide - 1);
                // Redémarrer l'autoplay après interaction
                setTimeout(startAutoplay, 2000);
            });

            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                stopAutoplay();
                showSlide(currentSlide + 1);
                // Redémarrer l'autoplay après interaction
                setTimeout(startAutoplay, 2000);
            });

            // Gestion du survol pour arrêter/reprendre l'autoplay
            carousel.parentElement.addEventListener('mouseenter', stopAutoplay);
            carousel.parentElement.addEventListener('mouseleave', startAutoplay);

            // Support tactile pour mobile
            let touchStartX = 0;
            let touchEndX = 0;

            carousel.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                stopAutoplay();
            });

            carousel.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
                setTimeout(startAutoplay, 2000);
            });

            function handleSwipe() {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        // Swipe vers la gauche - image suivante
                        showSlide(currentSlide + 1);
                    } else {
                        // Swipe vers la droite - image précédente
                        showSlide(currentSlide - 1);
                    }
                }
            }

            // Support du clavier
            document.addEventListener('keydown', (e) => {
                if (carousel.parentElement.matches(':hover')) {
                    if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        stopAutoplay();
                        showSlide(currentSlide - 1);
                        setTimeout(startAutoplay, 2000);
                    } else if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        stopAutoplay();
                        showSlide(currentSlide + 1);
                        setTimeout(startAutoplay, 2000);
                    }
                }
            });

            // Démarrer l'autoplay si il y a plusieurs images
            if (slideCount > 1) {
                startAutoplay();
            }

            // Pause de l'autoplay quand l'onglet n'est pas visible
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopAutoplay();
                } else if (slideCount > 1) {
                    startAutoplay();
                }
            });
        }
    });

    // Le code de la carte est maintenant géré par mapbox.js
    // qui détecte automatiquement la page et configure la carte en conséquence
    <?php if (!empty($event['Latitude']) && !empty($event['Longitude'])): ?>
    // Les coordonnées sont passées via les attributs data-lat et data-lng
    <?php endif; ?>
</script>
