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

// Construire la requête SQL de base
$sql_base = "SELECT 
                e.Id_Evenement, e.Titre, e.Description, e.DateDebut, e.DateFin,
                MIN(i.Lien) AS image_lien,
                c.Libelle AS categorie, 
                v.Libelle AS ville
             FROM evenement e
             LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
             LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
             LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
             WHERE e.statut_approbation = 'approuve'";

// Ajouter les conditions de recherche
$params = [];
$types = "";

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

// Filtre par ville
if ($city_id > 0) {
    $sql_base .= " AND e.Id_Ville = ?";
    $params[] = $city_id;
    $types .= "i";
}

// Finaliser la requête
$sql_base .= " GROUP BY e.Id_Evenement, e.Titre, e.Description, e.DateDebut, e.DateFin, c.Libelle, v.Libelle
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

// Villes
$cities_query = "SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle";
$cities_result = $conn->query($cities_query);
$cities = [];
if ($cities_result && $cities_result->num_rows > 0) {
    while ($city = $cities_result->fetch_assoc()) {
        $cities[] = $city;
    }
}

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

        <!-- Filtres actifs et possibilité de les modifier -->
        <div class="active-filters">
            <div class="filter-section">
                <!-- Section Date -->
                <div class="filter-group">
                    <span class="filter-group-title">Date:</span>
                    <div class="filter-buttons">
                        <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=today" class="filter-link <?php echo $date_filter === 'today' ? 'active' : ''; ?>">Aujourd'hui</a>
                        <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=week" class="filter-link <?php echo $date_filter === 'week' ? 'active' : ''; ?>">Cette semaine</a>
                        <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=month" class="filter-link <?php echo $date_filter === 'month' ? 'active' : ''; ?>">Ce mois-ci</a>
                        <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?><?php echo $city_id > 0 ? '&city='.$city_id : ''; ?>&date=next_month" class="filter-link <?php echo $date_filter === 'next_month' ? 'active' : ''; ?>">Dans un mois</a>
                    </div>
                </div>

                <!-- Section Catégorie (afficher seulement quelques catégories) -->
                <div class="filter-group">
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

                <!-- Section Ville (afficher seulement quelques villes) -->
                <div class="filter-group">
                    <span class="filter-group-title">Ville:</span>
                    <div class="filter-buttons">
                        <?php 
                        $displayed_cities = array_slice($cities, 0, 5); // Limiter à 5 villes
                        foreach ($displayed_cities as $city): 
                        ?>
                            <a href="?page=recherche<?php echo !empty($query) ? '&query='.urlencode($query) : ''; ?><?php echo !empty($date_filter) ? '&date='.$date_filter : ''; ?><?php echo $category_id > 0 ? '&category='.$category_id : ''; ?>&city=<?php echo $city['Id_Ville']; ?>" class="filter-link <?php echo $city_id == $city['Id_Ville'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($city['Libelle']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Formulaire de recherche -->
            <form action="?page=recherche" method="get" class="search-container">
                <input type="hidden" name="page" value="recherche">
                <?php if (!empty($date_filter)): ?>
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                <?php endif; ?>
                <?php if ($category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                <?php if ($city_id > 0): ?>
                    <input type="hidden" name="city" value="<?php echo $city_id; ?>">
                <?php endif; ?>
                <div class="search-input-wrapper">
                    <input type="text" name="query" value="<?php echo htmlspecialchars($query); ?>" placeholder="Rechercher des événements" class="search-input">
                    <button type="submit" class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                            <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Résultats de recherche -->
        <div class="search-results">
            <?php if (empty($events)): ?>
                <div class="no-results">
                    <h3>Aucun événement trouvé</h3>
                    <p>Essayez de modifier vos critères de recherche.</p>
                    <a href="?page=accueil" class="see-more-link">Retour à l'accueil</a>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <div class="event-card-image-wrapper aspect-square">
                                <div class="event-card-carousel" data-carousel>
                                    <div class="event-card-image">
                                        <img src="../<?php echo !empty($event['image_lien']) ? htmlspecialchars($event['image_lien']) : 'assets/images/default-event.jpg'; ?>" alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                    </div>
                                </div>
                                <button class="carousel-arrow prev">&lt;</button>
                                <button class="carousel-arrow next">&gt;</button>
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
