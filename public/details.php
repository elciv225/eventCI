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

                <?php if (!empty($event['Latitude']) && !empty($event['Longitude'])): ?>
                <div class="event-map-container">
                    <h3>Localisation</h3>
                    <div id="event-map" class="event-map"></div>
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

    // Initialisation de la carte pour l'événement
    <?php if (!empty($event['Latitude']) && !empty($event['Longitude'])): ?>
    // Coordonnées de l'événement
    const eventLat = <?php echo $event['Latitude']; ?>;
    const eventLng = <?php echo $event['Longitude']; ?>;

    // Initialiser la carte Mapbox
    mapboxgl.accessToken = 'pk.eyJ1IjoiZWxpZWwwNiIsImEiOiJjbWRqMjJsMHAwYmxuMmpzNW1xbmlldXA1In0.7S97Hn4TRZp-q6X3TW2UuQ';
    const eventMap = new mapboxgl.Map({
        container: 'event-map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [eventLng, eventLat],
        zoom: 15
    });

    // Ajouter un marqueur pour l'événement
    const eventMarker = new mapboxgl.Marker({
        color: '#FF0000'
    })
    .setLngLat([eventLng, eventLat])
    .setPopup(
        new mapboxgl.Popup({ offset: 25 })
            .setHTML(`<h3>${<?php echo json_encode(htmlspecialchars($event['Titre'])); ?>}</h3>`)
    )
    .addTo(eventMap);

    // Ouvrir le popup par défaut
    eventMarker.togglePopup();

    // Fonction pour obtenir l'itinéraire
    document.getElementById('get-directions').addEventListener('click', function() {
        // Vérifier si la géolocalisation est disponible
        if (navigator.geolocation) {
            // Afficher un message de chargement
            document.getElementById('directions-container').style.display = 'block';
            document.getElementById('directions-info').innerHTML = 'Recherche de votre position...';

            navigator.geolocation.getCurrentPosition(
                // Succès
                function(position) {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;

                    // Afficher un message de chargement
                    document.getElementById('directions-info').innerHTML = 'Calcul de l\'itinéraire...';

                    // Obtenir l'itinéraire via l'API Mapbox Directions
                    getDirections(userLng, userLat, eventLng, eventLat);
                },
                // Erreur
                function(error) {
                    let errorMessage = 'Impossible de déterminer votre position.';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Vous avez refusé la demande de géolocalisation.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Les informations de localisation ne sont pas disponibles.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'La demande de géolocalisation a expiré.';
                            break;
                    }
                    document.getElementById('directions-info').innerHTML = `<div class="error">${errorMessage}</div>`;
                },
                // Options
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            document.getElementById('directions-container').style.display = 'block';
            document.getElementById('directions-info').innerHTML = '<div class="error">La géolocalisation n\'est pas prise en charge par votre navigateur.</div>';
        }
    });

    // Fonction pour obtenir et afficher l'itinéraire
    function getDirections(startLng, startLat, endLng, endLat) {
        const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${startLng},${startLat};${endLng},${endLat}?steps=true&geometries=geojson&access_token=${mapboxgl.accessToken}&language=fr`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.code === 'Ok') {
                    // Extraire les informations d'itinéraire
                    const route = data.routes[0];
                    const distance = (route.distance / 1000).toFixed(1); // km
                    const duration = Math.round(route.duration / 60); // minutes

                    // Afficher les informations d'itinéraire
                    let directionsHTML = `
                        <div class="directions-summary">
                            <div class="directions-distance"><strong>Distance:</strong> ${distance} km</div>
                            <div class="directions-duration"><strong>Durée estimée:</strong> ${duration} min</div>
                        </div>
                        <div class="directions-steps">
                            <h5>Étapes:</h5>
                            <ol>
                    `;

                    // Ajouter les étapes
                    route.legs[0].steps.forEach(step => {
                        directionsHTML += `<li>${step.maneuver.instruction}</li>`;
                    });

                    directionsHTML += `
                            </ol>
                        </div>
                    `;

                    document.getElementById('directions-info').innerHTML = directionsHTML;

                    // Ajouter l'itinéraire à la carte
                    if (eventMap.getSource('route')) {
                        eventMap.removeLayer('route');
                        eventMap.removeSource('route');
                    }

                    eventMap.addSource('route', {
                        'type': 'geojson',
                        'data': {
                            'type': 'Feature',
                            'properties': {},
                            'geometry': route.geometry
                        }
                    });

                    eventMap.addLayer({
                        'id': 'route',
                        'type': 'line',
                        'source': 'route',
                        'layout': {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        'paint': {
                            'line-color': '#3887be',
                            'line-width': 5,
                            'line-opacity': 0.75
                        }
                    });

                    // Ajouter un marqueur pour la position de l'utilisateur
                    new mapboxgl.Marker({
                        color: '#3887be'
                    })
                    .setLngLat([startLng, startLat])
                    .setPopup(
                        new mapboxgl.Popup({ offset: 25 })
                            .setHTML('<h3>Votre position</h3>')
                    )
                    .addTo(eventMap);

                    // Ajuster la vue pour inclure l'itinéraire complet
                    const bounds = new mapboxgl.LngLatBounds();
                    route.geometry.coordinates.forEach(coord => {
                        bounds.extend(coord);
                    });

                    eventMap.fitBounds(bounds, {
                        padding: 50
                    });
                } else {
                    document.getElementById('directions-info').innerHTML = '<div class="error">Impossible de calculer l\'itinéraire. Veuillez réessayer plus tard.</div>';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération de l\'itinéraire:', error);
                document.getElementById('directions-info').innerHTML = '<div class="error">Erreur lors de la récupération de l\'itinéraire. Veuillez réessayer plus tard.</div>';
            });
    }
    <?php endif; ?>
</script>
