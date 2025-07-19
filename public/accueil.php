<?php
// Inclure le fichier de connexion à la base de données
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/config/base.php';
}

// --- Événements à venir ---
// Récupérer les 6 derniers événements créés.
// Correction de la requête pour être compatible avec sql_mode=only_full_group_by
$upcoming_query = "SELECT 
                    e.Id_Evenement, e.Titre, e.Description, 
                    MIN(i.Lien) AS image_lien, -- Utilisation de MIN() pour obtenir une seule image
                    c.Libelle AS categorie, 
                    v.Libelle AS ville
                  FROM evenement e
                  LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
                  LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
                  LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
                  WHERE e.statut_approbation = 'approuve'
                  GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, v.Libelle
                  ORDER BY e.Id_Evenement DESC
                  LIMIT 6";

$upcoming_result = $conn->query($upcoming_query);
$upcoming_events = [];
if ($upcoming_result && $upcoming_result->num_rows > 0) {
    while ($row = $upcoming_result->fetch_assoc()) {
        $upcoming_events[] = $row;
    }
}

// Récupérer les IDs des événements "à venir" pour les exclure des recommandations
$upcoming_ids = [];
foreach ($upcoming_events as $event) {
    $upcoming_ids[] = $event['Id_Evenement'];
}
// S'il n'y a pas d'événements, on met '0' pour éviter une erreur SQL
$upcoming_ids_str = !empty($upcoming_ids) ? implode(',', $upcoming_ids) : '0';


// --- Recommandations ---
// Récupérer 12 événements aléatoires qui ne sont pas dans la première liste.
// Correction de la requête pour être compatible avec sql_mode=only_full_group_by
$recommended_query = "SELECT 
                        e.Id_Evenement, e.Titre, e.Description,
                        MIN(i.Lien) AS image_lien, -- Utilisation de MIN() pour obtenir une seule image
                        c.Libelle AS categorie, 
                        v.Libelle AS ville
                     FROM evenement e
                     LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
                     LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
                     LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
                     WHERE e.statut_approbation = 'approuve'
                     AND e.Id_Evenement NOT IN ($upcoming_ids_str)
                     GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, v.Libelle
                     ORDER BY RAND()
                     LIMIT 12";

$recommended_result = $conn->query($recommended_query);
$recommended_events = [];
if ($recommended_result && $recommended_result->num_rows > 0) {
    while ($row = $recommended_result->fetch_assoc()) {
        $recommended_events[] = $row;
    }
}
?>

<main class="page-container">
    <!-- Section de recherche (inchangée) -->
    <div class="mobile-search-section">
        <div class="search-container"><input type="text" placeholder="Rechercher des événements"
                                             class="search-input" style="height: 3rem; padding-left: 3rem;"/>
            <div class="search-icon" style="left: 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                     viewBox="0 0 256 256">
                    <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="filter-section">
        <!-- Section Date -->
        <div class="filter-group">
            <h3 class="filter-group-title">Date</h3>
            <div class="filter-buttons">
                <button class="filter-btn">Aujourd'hui</button>
                <button class="filter-btn">Cette semaine</button>
                <button class="filter-btn">Ce mois-ci</button>
                <button class="filter-btn">Dans un mois</button>
            </div>
        </div>

        <!-- Section Catégorie -->
        <div class="filter-group">
            <h3 class="filter-group-title">Catégorie</h3>
            <div class="filter-buttons">
                <?php
                // Récupérer les catégories depuis la base de données
                $categories_query = "SELECT Id_CategorieEvenement, Libelle FROM categorieevenement ORDER BY Libelle";
                $categories_result = $conn->query($categories_query);

                if ($categories_result && $categories_result->num_rows > 0) {
                    while ($category = $categories_result->fetch_assoc()) {
                        echo '<button class="filter-btn" data-category-id="' . $category['Id_CategorieEvenement'] . '">' . htmlspecialchars($category['Libelle']) . '</button>';
                    }
                } else {
                    echo '<button class="filter-btn">Aucune catégorie</button>';
                }
                ?>
            </div>
        </div>

        <!-- Section Ville -->
        <div class="filter-group">
            <h3 class="filter-group-title">Ville</h3>
            <div class="filter-buttons">
                <?php
                // Récupérer les villes depuis la base de données
                $cities_query = "SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle";
                $cities_result = $conn->query($cities_query);

                if ($cities_result && $cities_result->num_rows > 0) {
                    while ($city = $cities_result->fetch_assoc()) {
                        echo '<button class="filter-btn" data-city-id="' . $city['Id_Ville'] . '">' . htmlspecialchars($city['Libelle']) . '</button>';
                    }
                } else {
                    echo '<button class="filter-btn">Aucune ville</button>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Section Événements à venir -->
    <section class="events-section">
        <!-- Titre modifié comme demandé -->
        <h2 class="section-title">Événements à venir</h2>
        <div class="horizontal-scroll-container" id="upcoming-events-carousel" data-section-carousel>
            <button class="horizontal-scroll-nav prev">&lt;</button>
            <button class="horizontal-scroll-nav next">&gt;</button>

            <?php if (empty($upcoming_events)): ?>
                <div class="event-card event-card-horizontal">
                    <div class="event-card-empty">
                        <h3 class="event-card-title">Aucun événement à venir</h3>
                        <p class="event-card-desc">Il n'y a rien pour l'instant. Revenez bientôt pour découvrir nos prochains événements.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_events as $event): ?>
                    <div class="event-card event-card-horizontal">
                        <div class="event-card-image-wrapper aspect-video">
                            <div class="event-card-carousel" data-carousel>
                                <div class="event-card-image">
                                    <img src="../<?php echo !empty($event['image_lien']) ? htmlspecialchars($event['image_lien']) : 'assets/images/default-event.jpg'; ?>"
                                         alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                </div>
                            </div>
                            <!-- Les flèches de carrousel peuvent être utiles si vous avez plusieurs images par événement -->
                            <button class="carousel-arrow prev">&lt;</button>
                            <button class="carousel-arrow next">&gt;</button>
                        </div>
                        <a href="?info-event=<?php echo $event['Id_Evenement']; ?>">
                            <div>
                                <h3 class="event-card-title"><?php echo htmlspecialchars($event['Titre']); ?></h3>
                                <p class="event-card-desc">
                                    <?php
                                    $desc = htmlspecialchars($event['Description']);
                                    echo (strlen($desc) > 100) ? substr($desc, 0, 97) . '...' : $desc;
                                    ?>
                                </p>
                                <p class="event-card-meta">
                                    <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span> |
                                    <span class="event-location"><?php echo htmlspecialchars($event['ville']); ?></span>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Section Recommandé pour vous -->
    <section class="events-section">
        <h2 class="section-title">Recommandé pour vous</h2>
        <div class="events-grid" id="recommended-grid" data-section-carousel>
            <?php if (empty($recommended_events)): ?>
                <div class="event-card">
                    <div class="event-card-empty">
                        <h3 class="event-card-title">Aucun événement recommandé</h3>
                        <p class="event-card-desc">Il n'y a rien pour l'instant. Consultez plus tard pour des recommandations.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recommended_events as $event): ?>
                    <div class="event-card">
                        <div class="event-card-image-wrapper aspect-square">
                            <div class="event-card-carousel" data-carousel>
                                <div class="event-card-image">
                                    <img src="../<?php echo !empty($event['image_lien']) ? htmlspecialchars($event['image_lien']) : 'assets/images/default-event.jpg'; ?>"
                                         alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                </div>
                            </div>
                            <button class="carousel-arrow prev">&lt;</button>
                            <button class="carousel-arrow next">&gt;</button>
                        </div>
                        <a href="?info-event=<?php echo $event['Id_Evenement']; ?>">
                            <div>
                                <h3 class="event-card-title"><?php echo htmlspecialchars($event['Titre']); ?></h3>
                                <p class="event-card-desc">
                                    <?php
                                    $desc = htmlspecialchars($event['Description']);
                                    echo (strlen($desc) > 100) ? substr($desc, 0, 97) . '...' : $desc;
                                    ?>
                                </p>
                                <p class="event-card-meta">
                                    <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span> |
                                    <span class="event-location"><?php echo htmlspecialchars($event['ville']); ?></span>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="see-more-container" id="see-more-container">
            <a href="#" class="see-more-link" id="see-more-link">Voir plus</a>
        </div>
    </section>
</main>
