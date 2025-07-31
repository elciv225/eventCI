<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}

// Récupérer les paramètres de recherche
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$city_id = isset($_GET['city']) ? intval($_GET['city']) : 0;
$distance_filter = isset($_GET['distance']) ? intval($_GET['distance']) : 100; // Distance en km, par défaut 100km

// Coordonnées de référence (Abidjan par défaut)
$ref_lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 5.3364;
$ref_lng = isset($_GET['lng']) ? floatval($_GET['lng']) : -4.0267;

// Indique si les coordonnées sont celles de l'utilisateur ou par défaut
$using_user_location = isset($_GET['lat']) && isset($_GET['lng']);

// Construire la requête SQL de base
$sql_base = "SELECT 
                e.Id_Evenement, e.Titre, e.Description, e.DateDebut, e.DateFin, e.Adresse,
                e.Latitude, e.Longitude,
                MIN(i.Lien) AS image_lien,
                c.Libelle AS categorie, 
                e.Salle AS ville,
                CASE 
                    WHEN e.Latitude IS NOT NULL AND e.Longitude IS NOT NULL THEN
                        6371 * 2 * ASIN(SQRT(
                            POWER(SIN((RADIANS(?) - RADIANS(e.Latitude)) / 2), 2) +
                            COS(RADIANS(?)) * COS(RADIANS(e.Latitude)) *
                            POWER(SIN((RADIANS(?) - RADIANS(e.Longitude)) / 2), 2)
                        ))
                    ELSE NULL
                END AS distance
             FROM evenement e
             LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
             LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
             WHERE e.statut_approbation = 'approuve'";

// Ajouter les conditions de recherche
$params = [$ref_lat, $ref_lat, $ref_lng]; // Paramètres pour le calcul de distance
$types = "ddd"; // Types pour les paramètres de distance (double)

// Filtre de distance (seulement pour les événements avec coordonnées)
$sql_base .= " AND (e.Latitude IS NULL OR e.Longitude IS NULL OR (
    6371 * 2 * ASIN(SQRT(
        POWER(SIN((RADIANS(?) - RADIANS(e.Latitude)) / 2), 2) +
        COS(RADIANS(?)) * COS(RADIANS(e.Latitude)) *
        POWER(SIN((RADIANS(?) - RADIANS(e.Longitude)) / 2), 2)
    )) <= ?
))";
$params[] = $ref_lat;
$params[] = $ref_lat;
$params[] = $ref_lng;
$params[] = $distance_filter;
$types .= "dddi";

// Recherche par texte
if (!empty($query)) {
    $sql_base .= " AND (e.Titre LIKE ? OR e.Description LIKE ?)";
    $search_term = "%$query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// Filtre par date
if (!empty($date_filter)) {
    $today = date('Y-m-d');

    switch ($date_filter) {
        case 'today':
            $sql_base .= " AND DATE(e.DateDebut) = ?";
            $params[] = $today;
            $types .= "s";
            break;
        case 'week':
            $end_of_week = date('Y-m-d', strtotime('+7 days'));
            $sql_base .= " AND DATE(e.DateDebut) BETWEEN ? AND ?";
            $params[] = $today;
            $params[] = $end_of_week;
            $types .= "ss";
            break;
        case 'month':
            $end_of_month = date('Y-m-d', strtotime('+30 days'));
            $sql_base .= " AND DATE(e.DateDebut) BETWEEN ? AND ?";
            $params[] = $today;
            $params[] = $end_of_month;
            $types .= "ss";
            break;
        case 'next_month':
            $start_next_month = date('Y-m-d', strtotime('+30 days'));
            $end_next_month = date('Y-m-d', strtotime('+60 days'));
            $sql_base .= " AND DATE(e.DateDebut) BETWEEN ? AND ?";
            $params[] = $start_next_month;
            $params[] = $end_next_month;
            $types .= "ss";
            break;
    }
}

// Filtre par catégorie
if ($category_id > 0) {
    $sql_base .= " AND e.Id_CategorieEvenement = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Filtre par ville - désactivé car la table ville n'est plus utilisée
// if ($city_id > 0) {
//     $sql_base .= " AND e.Id_Ville = ?";
//     $params[] = $city_id;
//     $types .= "i";
// }

// Finaliser la requête
$sql_base .= " GROUP BY e.Id_Evenement, e.Titre, e.Description, e.DateDebut, e.DateFin, c.Libelle, e.Adresse
               ORDER BY e.DateDebut ASC";

// Exécuter la requête
$stmt = $conn->prepare($sql_base);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Récupérer les filtres pour l'affichage
// Catégories
$categories_query = "SELECT Id_CategorieEvenement, Libelle FROM categorieevenement ORDER BY Libelle";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while ($category = $categories_result->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Villes - désactivé car la table ville n'est plus utilisée
// $cities_query = "SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle";
// $cities_result = $conn->query($cities_query);
// $cities = [];
// if ($cities_result && $cities_result->num_rows > 0) {
//     while ($city = $cities_result->fetch_assoc()) {
//         $cities[] = $city;
//     }
// }
$cities = []; // Tableau vide pour éviter les erreurs

// Titre de la page en fonction des filtres
$page_title = "Résultats de recherche";
if (!empty($query)) {
    $page_title = "Recherche: " . htmlspecialchars($query);
} elseif (!empty($date_filter)) {
    $date_titles = [
        'today' => "Événements aujourd'hui",
        'week' => "Événements cette semaine",
        'month' => "Événements ce mois-ci",
        'next_month' => "Événements le mois prochain"
    ];
    $page_title = $date_titles[$date_filter] ?? $page_title;
} elseif ($category_id > 0) {
    foreach ($categories as $cat) {
        if ($cat['Id_CategorieEvenement'] == $category_id) {
            $page_title = "Catégorie: " . htmlspecialchars($cat['Libelle']);
            break;
        }
    }
} elseif ($city_id > 0) {
    foreach ($cities as $city) {
        if ($city['Id_Ville'] == $city_id) {
            $page_title = "Événements à " . htmlspecialchars($city['Libelle']);
            break;
        }
    }
}

?>

<main class="page-container">
    <section class="search-results-section">
        <h1 class="section-title"><?php echo $page_title; ?></h1>

        <!-- Section de recherche mobile (comme sur l'accueil) -->
        <div class="mobile-search-section">
            <form action="?page=recherche" method="get" class="search-container">
                <input type="hidden" name="page" value="recherche">
                <?php if (!empty($date_filter)): ?>
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                <?php endif; ?>
                <?php if ($category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                <input type="text" name="query" value="<?php echo htmlspecialchars($query); ?>" placeholder="Rechercher des événements"
                       class="search-input" style="height: 3rem; padding-left: 3rem;"/>
                <button type="submit" class="search-icon"
                        style="left: 1rem; background: none; border: none; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                         viewBox="0 0 256 256">
                        <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Filtres actifs et possibilité de les modifier -->
        <div class="active-filters">
            <!-- Bouton pour afficher/masquer les filtres -->
            <button id="toggle-filters" class="filter-toggle-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                    <path d="M200,128a8,8,0,0,1-8,8H64a8,8,0,0,1,0-16H192A8,8,0,0,1,200,128ZM64,72H192a8,8,0,0,0,0-16H64a8,8,0,0,0,0,16ZM192,184H64a8,8,0,0,0,0,16H192a8,8,0,0,0,0-16Z"></path>
                </svg>
                Filtres
                <?php if (!empty($date_filter) || $category_id > 0): ?>
                    <span class="filter-badge"><?php echo (!empty($date_filter) ? 1 : 0) + ($category_id > 0 ? 1 : 0); ?></span>
                <?php endif; ?>
            </button>

            <!-- Conteneur de filtres (masqué par défaut) -->
            <div id="filter-container" class="filter-container">
                <div class="filter-section">
                    <!-- Section Date -->
                    <div class="filter-group filter-card">
                        <span class="filter-group-title">Date:</span>
                        <div class="filter-buttons">
                            <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=today" class="filter-link <?php echo $date_filter === 'today' ? 'active' : ''; ?>">Aujourd'hui</a>
                            <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=week" class="filter-link <?php echo $date_filter === 'week' ? 'active' : ''; ?>">Cette semaine</a>
                            <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=month" class="filter-link <?php echo $date_filter === 'month' ? 'active' : ''; ?>">Ce mois-ci</a>
                            <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=next_month" class="filter-link <?php echo $date_filter === 'next_month' ? 'active' : ''; ?>">Dans un mois</a>
                        </div>
                    </div>

                    <!-- Section Catégorie (afficher seulement quelques catégories) -->
                    <div class="filter-group filter-card">
                        <span class="filter-group-title">Catégorie:</span>
                        <div class="filter-buttons">
                            <?php 
                            $displayed_categories = array_slice($categories, 0, 5); // Limiter à 5 catégories
                            foreach ($displayed_categories as $cat): 
                            ?>
                                <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo !empty($date_filter) ? '&date='.$date_filter : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&category=<?php echo $cat['Id_CategorieEvenement']; ?>" class="filter-link <?php echo $category_id == $cat['Id_CategorieEvenement'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($cat['Libelle']); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Section Ville désactivée car la table ville n'est plus utilisée -->
                    <!-- <div class="filter-group filter-card">
                        <span class="filter-group-title">Ville:</span>
                        <div class="filter-buttons">
                            <?php 
                            // $displayed_cities = array_slice($cities, 0, 5); // Limiter à 5 villes
                            // foreach ($displayed_cities as $city): 
                            ?>
                                <a href="?page=recherche<?php // echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php // echo !empty($date_filter) ? '&date='.$date_filter : ''; ?><?php // echo $category_id > 0 ? '&category='.$category_id : ''; ?>&city=<?php // echo $city['Id_Ville']; ?>" class="filter-link <?php // echo $city_id == $city['Id_Ville'] ? 'active' : ''; ?>"><?php // echo htmlspecialchars($city['Libelle']); ?></a>
                            <?php // endforeach; ?>
                        </div>
                    </div> -->
                </div>
            </div>

            <!-- Affichage des filtres actifs -->
            <div class="active-filter-tags">
                <?php if (!empty($date_filter)): ?>
                    <div class="filter-tag">
                        <?php 
                        $date_labels = [
                            'today' => "Aujourd'hui",
                            'week' => "Cette semaine",
                            'month' => "Ce mois-ci",
                            'next_month' => "Dans un mois"
                        ];
                        echo $date_labels[$date_filter] ?? "Date"; 
                        ?>
                        <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>" class="remove-filter">×</a>
                    </div>
                <?php endif; ?>

                <?php if ($category_id > 0): 
                    $category_name = "";
                    foreach ($categories as $cat) {
                        if ($cat['Id_CategorieEvenement'] == $category_id) {
                            $category_name = $cat['Libelle'];
                            break;
                        }
                    }
                ?>
                    <div class="filter-tag">
                        <?php echo htmlspecialchars($category_name); ?>
                        <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo !empty($date_filter) ? '&date='.$date_filter : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>" class="remove-filter">×</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Carte des événements -->
        <div class="map-container">
            <h2 class="section-subtitle">
                <?php if ($using_user_location): ?>
                    Événements à proximité de votre position (rayon de <?php echo $distance_filter; ?> km)
                <?php else: ?>
                    Événements à proximité d'Abidjan (rayon de <?php echo $distance_filter; ?> km)
                <?php endif; ?>
            </h2>
            <div id="search-map" class="search-map"></div>
            <?php if (!$using_user_location): ?>
                <div class="info-text">
                    <i>Nous n'avons pas pu déterminer votre position. Activez la géolocalisation pour voir les événements près de chez vous.</i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Résultats de recherche -->
        <div class="search-results">
            <?php if (empty($events)): ?>
                <div class="no-results">
                    <h3>Aucun événement trouvé</h3>
                    <p>Essayez de modifier vos critères de recherche.</p>
                    <a href="?page=accueil" class="back-button"><span class="arrow">←</span> Retour à l'accueil</a>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card" data-event-id="<?php echo $event['Id_Evenement']; ?>" 
                             <?php if (!empty($event['Latitude']) && !empty($event['Longitude'])): ?>
                             data-lat="<?php echo $event['Latitude']; ?>" 
                             data-lng="<?php echo $event['Longitude']; ?>"
                             <?php endif; ?>>
                            <div class="event-card-image-wrapper aspect-square">
                                <div class="event-card-carousel" data-carousel>
                                    <div class="event-card-image">
                                        <img src="../<?php echo !empty($event['image_lien']) ? htmlspecialchars($event['image_lien']) : 'assets/images/default-event.jpg'; ?>" alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                    </div>
                                </div>
                                <?php
                                // Récupérer toutes les images pour cet événement
                                $event_images_query = "SELECT COUNT(*) as image_count FROM imageevenement WHERE Id_Evenement = ?";
                                $stmt_images = $conn->prepare($event_images_query);
                                $stmt_images->bind_param("i", $event['Id_Evenement']);
                                $stmt_images->execute();
                                $image_count_result = $stmt_images->get_result();
                                $image_count = $image_count_result->fetch_assoc()['image_count'] ?? 0;
                                $stmt_images->close();

                                if ($image_count > 1): 
                                ?>
                                <button class="carousel-arrow prev">&lt;</button>
                                <button class="carousel-arrow next">&gt;</button>
                                <?php endif; ?>
                                <?php if (!empty($event['distance'])): ?>
                                <div class="event-distance"><?php echo round($event['distance'], 1); ?> km</div>
                                <?php endif; ?>
                            </div>
                            <a href="?page=details&id=<?php echo $event['Id_Evenement']; ?>">
                                <div>
                                    <h3 class="event-card-title"><?php echo htmlspecialchars($event['Titre']); ?></h3>
                                    <p class="event-card-date">
                                        <?php 
                                        $date_debut = new DateTime($event['DateDebut']);
                                        echo $date_debut->format('d/m/Y à H:i'); 
                                        ?>
                                    </p>
                                    <p class="event-card-desc">
                                        <?php
                                        $desc = htmlspecialchars($event['Description']);
                                        echo (strlen($desc) > 100) ? substr($desc, 0, 97) . '...' : $desc;
                                        ?>
                                    </p>
                                    <p class="event-card-meta">
                                        <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span>
                                        |
                                        <span class="event-location"><?php echo htmlspecialchars($event['ville']); ?></span>
                                    </p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la carte
    const mapContainer = document.getElementById('search-map');
    if (!mapContainer) return;

    // Coordonnées de référence
    const refLat = <?php echo $ref_lat; ?>;
    const refLng = <?php echo $ref_lng; ?>;
    const usingUserLocation = <?php echo $using_user_location ? 'true' : 'false'; ?>;
    const distanceFilter = <?php echo $distance_filter; ?>;

    // Initialiser la carte Mapbox
    mapboxgl.accessToken = 'pk.eyJ1IjoiZWxpZWwwNiIsImEiOiJjbWRqMjJsMHAwYmxuMmpzNW1xbmlldXA1In0.7S97Hn4TRZp-q6X3TW2UuQ';
    const map = new mapboxgl.Map({
        container: 'search-map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [refLng, refLat],
        zoom: 10
    });

    // Vérifier si la géolocalisation est disponible et si nous n'utilisons pas déjà la position de l'utilisateur
    // Vérifier également si nous avons déjà demandé la géolocalisation dans cette session
    const geolocRequested = sessionStorage.getItem('geolocRequested');

    if (navigator.geolocation && !usingUserLocation && !geolocRequested) {
        // Marquer que nous avons demandé la géolocalisation pour éviter de redemander
        sessionStorage.setItem('geolocRequested', 'true');

        // Demander la position de l'utilisateur
        navigator.geolocation.getCurrentPosition(
            // Succès
            function(position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                // Rediriger vers la même page avec les coordonnées de l'utilisateur
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('lat', userLat);
                currentUrl.searchParams.set('lng', userLng);
                window.location.href = currentUrl.toString();
            },
            // Erreur
            function(error) {
                console.log("Erreur de géolocalisation:", error.message);
                // Continuer avec les coordonnées par défaut
                addUserMarker(refLng, refLat, false);
            },
            // Options
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    } else {
        // Utiliser les coordonnées actuelles (soit par défaut, soit déjà celles de l'utilisateur)
        addUserMarker(refLng, refLat, usingUserLocation);
    }

    // Fonction pour ajouter le marqueur de l'utilisateur
    function addUserMarker(lng, lat, isUserLocation) {
        // Ajouter un marqueur plus discret pour la position
        new mapboxgl.Marker({
            color: isUserLocation ? 'rgba(0, 100, 255, 0.7)' : 'rgba(255, 0, 0, 0.5)',
            scale: 0.8
        })
        .setLngLat([lng, lat])
        .setPopup(
            new mapboxgl.Popup({ offset: 25 })
                .setHTML(`<div style="text-align: center;">${isUserLocation ? 'Votre position' : 'Position par défaut (Abidjan)'}</div>`)
        )
        .addTo(map);
    }

    // Ajouter un cercle pour le rayon de recherche
    map.on('load', function() {
        map.addSource('radius', {
            'type': 'geojson',
            'data': {
                'type': 'Feature',
                'geometry': {
                    'type': 'Point',
                    'coordinates': [refLng, refLat]
                },
                'properties': {
                    'radius': <?php echo $distance_filter; ?> * 1000 // Convertir en mètres
                }
            }
        });

        map.addLayer({
            'id': 'radius-circle',
            'type': 'circle',
            'source': 'radius',
            'paint': {
                'circle-radius': ['get', 'radius'],
                'circle-color': 'rgba(0, 100, 255, 0.1)',
                'circle-stroke-width': 2,
                'circle-stroke-color': 'rgba(0, 100, 255, 0.6)',
                'circle-pitch-alignment': 'map',
                'circle-stroke-opacity': 0.8
            }
        });

        // Ajuster la vue pour inclure le cercle
        const bounds = new mapboxgl.LngLatBounds();
        bounds.extend([refLng, refLat]);

        // Étendre les limites pour inclure le rayon
        const radiusInDegrees = <?php echo $distance_filter; ?> / 111; // Approximation: 1 degré ≈ 111 km
        bounds.extend([refLng + radiusInDegrees, refLat + radiusInDegrees]);
        bounds.extend([refLng - radiusInDegrees, refLat - radiusInDegrees]);

        map.fitBounds(bounds, {
            padding: 50
        });
    });

    // Collecter les événements avec coordonnées
    const eventCards = document.querySelectorAll('.event-card[data-lat][data-lng]');
    const events = [];

    eventCards.forEach(card => {
        const id = card.getAttribute('data-event-id');
        const lat = parseFloat(card.getAttribute('data-lat'));
        const lng = parseFloat(card.getAttribute('data-lng'));
        const title = card.querySelector('.event-card-title').textContent;
        const image = card.querySelector('.event-card-image img').getAttribute('src');

        if (!isNaN(lat) && !isNaN(lng)) {
            events.push({
                id: id,
                coordinates: [lng, lat],
                title: title,
                image: image,
                element: card
            });
        }
    });

    // Ajouter les marqueurs pour chaque événement
    events.forEach(event => {
        // Créer un élément personnalisé pour le marqueur
        const el = document.createElement('div');
        el.className = 'event-marker';
        el.style.backgroundImage = `url(${event.image})`;
        el.style.width = '30px';
        el.style.height = '30px';
        el.style.borderRadius = '50%';
        el.style.border = '2px solid #fff';
        el.style.boxShadow = '0 0 5px rgba(0,0,0,0.3)';
        el.style.cursor = 'pointer';
        el.style.backgroundSize = 'cover';
        el.style.backgroundPosition = 'center';

        // Ajouter le marqueur à la carte
        const marker = new mapboxgl.Marker(el)
            .setLngLat(event.coordinates)
            .setPopup(
                new mapboxgl.Popup({ offset: 25 })
                    .setHTML(`
                        <div style="max-width: 200px;">
                            <h3 style="margin: 0 0 5px 0; font-size: 14px;">${event.title}</h3>
                            <a href="?page=details&id=${event.id}" style="display: block; text-align: center; margin-top: 10px; color: #0066cc; text-decoration: none; font-size: 12px;">Voir les détails</a>
                        </div>
                    `)
            )
            .addTo(map);

        // Synchroniser le survol entre la carte et la liste
        el.addEventListener('mouseenter', () => {
            event.element.classList.add('highlight');
        });

        el.addEventListener('mouseleave', () => {
            event.element.classList.remove('highlight');
        });

        // Prevent default behavior on links inside event cards to avoid page reload
        const links = event.element.querySelectorAll('a');
        links.forEach(link => {
            // Store the original href
            const href = link.getAttribute('href');

            // Add click event listener to handle navigation manually
            link.addEventListener('click', (e) => {
                e.preventDefault();
                // Use history.pushState to change the URL without reloading the page
                window.location.href = href;
            });
        });

        // Simplified mouseenter event - only highlight the marker
        event.element.addEventListener('mouseenter', (e) => {
            marker.getElement().classList.add('highlight');
        });

        // Add double-click event to the image part of the event card
        const imageWrapper = event.element.querySelector('.event-card-image-wrapper');
        if (imageWrapper) {
            imageWrapper.addEventListener('dblclick', (e) => {
                // Prevent click from propagating to parent elements
                e.stopPropagation();

                // Don't interfere with carousel arrows
                if (e.target.classList.contains('carousel-arrow')) {
                    return;
                }

                // Show popup
                marker.togglePopup();

                // Smooth fly to the marker location with animation
                map.flyTo({
                    center: event.coordinates,
                    zoom: 14,
                    speed: 0.8, // Slower for smoother animation
                    curve: 1, // Smooth animation curve
                    essential: true // This animation is considered essential for the user experience
                });

                // Scroll the map into view if it's not already visible
                const mapRect = mapContainer.getBoundingClientRect();
                const isMapVisible = (
                    mapRect.top >= 0 &&
                    mapRect.left >= 0 &&
                    mapRect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    mapRect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );

                if (!isMapVisible) {
                    // Smooth scroll to the map
                    mapContainer.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center'
                    });
                }
            });
        }

        event.element.addEventListener('mouseleave', (e) => {
            marker.getElement().classList.remove('highlight');
        });
    });
});
</script>
