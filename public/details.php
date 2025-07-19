<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}

// Pour les besoins de la démo, utilisons des données statiques
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Données de l'événement (simulées)
$event = [
    'Id_Evenement' => $event_id,
    'Titre' => 'Concert de musique classique',
    'Description' => "Un magnifique concert de musique classique avec les plus grands compositeurs.\n\nAu programme : Mozart, Beethoven, Bach et bien d'autres surprises musicales.",
    'Adresse' => '123 Avenue de la Musique, 75001 Paris',
    'DateDebut' => '2023-12-15 19:30:00',
    'DateFin' => '2023-12-15 22:00:00',
    'categorie' => 'Concert-spectacle',
    'ville' => 'Paris',
    'Id_Utilisateur' => 1,
    'Nom' => 'Dupont',
    'Prenom' => 'Jean',
    'Photo' => ''
];

// Images de l'événement (simulées)
$images = [
    [
        'Id_ImageEvenement' => 1,
        'Titre' => 'Image principale',
        'Description' => 'Vue de la salle de concert',
        'Lien' => 'assets/images/default-event.jpg'
    ],
    [
        'Id_ImageEvenement' => 2,
        'Titre' => 'Image secondaire',
        'Description' => 'Les musiciens en répétition',
        'Lien' => 'assets/images/default-event.jpg'
    ]
];

// Tickets disponibles (simulés)
$tickets = [
    [
        'Id_TicketEvenement' => 1,
        'Titre' => 'Place Standard',
        'Description' => 'Accès à la salle principale',
        'Prix' => 25.00,
        'NombreDisponible' => 150
    ],
    [
        'Id_TicketEvenement' => 2,
        'Titre' => 'Place VIP',
        'Description' => 'Accès privilégié avec cocktail de bienvenue',
        'Prix' => 50.00,
        'NombreDisponible' => 30
    ],
    [
        'Id_TicketEvenement' => 3,
        'Titre' => 'Pack Famille',
        'Description' => '4 places à tarif réduit',
        'Prix' => 80.00,
        'NombreDisponible' => 20
    ]
];

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
                    <img src="../assets/images/default-event.jpg" alt="<?php echo htmlspecialchars($event['Titre']); ?>" class="event-image">
                </div>
            <?php else: ?>
                <div class="event-carousel" id="event-carousel">
                    <?php foreach ($images as $image): ?>
                        <div class="event-image-wrapper">
                            <img src="../<?php echo htmlspecialchars($image['Lien']); ?>" alt="<?php echo htmlspecialchars($image['Titre']); ?>" class="event-image">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <button class="carousel-arrow prev" id="carousel-prev">&lt;</button>
                    <button class="carousel-arrow next" id="carousel-next">&gt;</button>
                <?php endif; ?>
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
                                <img src="../<?php echo htmlspecialchars($event['Photo']); ?>" alt="Photo de l'organisateur">
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
                                        <div class="ticket-price"><?php echo number_format($ticket['Prix'], 2, ',', ' '); ?> €</div>
                                        <div class="ticket-availability"><?php echo $ticket['NombreDisponible']; ?> disponibles</div>
                                    </div>
                                    <div class="ticket-actions">
                                        <a href="?page=commande&ticket=<?php echo $ticket['Id_TicketEvenement']; ?>" class="btn-primary">Commander</a>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du carousel d'images
        const carousel = document.getElementById('event-carousel');
        const prevBtn = document.getElementById('carousel-prev');
        const nextBtn = document.getElementById('carousel-next');

        if (carousel && prevBtn && nextBtn) {
            let currentSlide = 0;
            const slides = carousel.querySelectorAll('.event-image-wrapper');
            const slideCount = slides.length;

            // Fonction pour afficher un slide spécifique
            function showSlide(index) {
                if (index < 0) index = slideCount - 1;
                if (index >= slideCount) index = 0;

                carousel.style.transform = `translateX(-${index * 100}%)`;
                currentSlide = index;
            }

            // Initialiser le carousel
            carousel.style.width = `${slideCount * 100}%`;
            slides.forEach(slide => {
                slide.style.width = `${100 / slideCount}%`;
            });

            // Ajouter les événements pour les boutons
            prevBtn.addEventListener('click', () => showSlide(currentSlide - 1));
            nextBtn.addEventListener('click', () => showSlide(currentSlide + 1));
        }
    });
</script>
