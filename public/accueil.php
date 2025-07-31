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
                    e.Salle AS salle
                  FROM evenement e
                  LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
                  LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
                  WHERE e.statut_approbation = 'approuve'
                  GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, e.Salle
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
                        e.Salle AS salle
                     FROM evenement e
                     LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
                     LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
                     WHERE e.statut_approbation = 'approuve'
                     AND e.Id_Evenement NOT IN ($upcoming_ids_str)
                     GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, e.Salle
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
    <!-- Section de recherche -->
    <div class="mobile-search-section">
        <form action="?page=recherche" method="get" class="search-container">
            <input type="hidden" name="page" value="recherche">
            <input type="text" name="query" placeholder="Rechercher des événements"
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
    <!-- Section Événements à venir -->
    <section class="events-section">
        <!-- Titre modifié comme demandé -->
        <h2 class="section-title">Événements à venir</h2>
        <div class="horizontal-scroll-container" id="upcoming-events-carousel" data-section-carousel>
            <?php if (empty($upcoming_events)): ?>
                <div class="event-card event-card-horizontal">
                    <div class="event-card-empty">
                        <h3 class="event-card-title">Aucun événement à venir</h3>
                        <p class="event-card-desc">Il n'y a rien pour l'instant. Revenez bientôt pour découvrir nos
                            prochains événements.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_events as $event): ?>
                    <div class="event-card event-card-horizontal">
                        <div class="event-card-image-wrapper aspect-video">
                            <div class="event-card-carousel" data-carousel>
                                <?php
                                // Récupérer toutes les images pour cet événement
                                $event_images_query = "SELECT Lien FROM imageevenement WHERE Id_Evenement = ?";
                                $stmt_images = $conn->prepare($event_images_query);
                                $stmt_images->bind_param("i", $event['Id_Evenement']);
                                $stmt_images->execute();
                                $image_result = $stmt_images->get_result();
                                $all_images = $image_result->fetch_all(MYSQLI_ASSOC);
                                $image_count = count($all_images);
                                $stmt_images->close();

                                // S'il n'y a aucune image, on peut mettre une image par défaut (optionnel)
                                if ($image_count === 0) {
                                    echo '<div class="event-card-image"><img src="assets/images/default-event.jpg" alt="Image par défaut"/></div>';
                                } else {
                                    // Afficher toutes les images récupérées
                                    foreach ($all_images as $img) {
                                        echo '<div class="event-card-image">';
                                        echo '<img src="' . htmlspecialchars($img['Lien']) . '" alt="' . htmlspecialchars($event['Titre']) . '"/>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                            <?php if ($image_count > 1): ?>
                                <button class="carousel-arrow prev">&lt;</button>
                                <button class="carousel-arrow next">&gt;</button>
                            <?php endif; ?>
                        </div>
                        <a href="?page=details&info-event=<?php echo $event['Id_Evenement']; ?>">
                            <div>
                                <h3 class="event-card-title"><?php echo htmlspecialchars($event['Titre']); ?></h3>
                                <p class="event-card-desc">
                                    <?php
                                    $desc = htmlspecialchars($event['Description']);
                                    echo (strlen($desc) > 100) ? substr($desc, 0, 97) . '...' : $desc;
                                    ?>
                                </p>
                                <p class="event-card-meta" style="color: var(--text-primary)">
                                    <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span>
                                    |
                                    <span class="event-location"><?php echo htmlspecialchars($event['salle']); ?></span>
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
        <?php if (empty($recommended_events)): ?>
            <div class="event-card">
                <div class="event-card-empty">
                    <h3 class="event-card-title">Aucun événement recommandé</h3>
                    <p class="event-card-desc">Il n'y a rien pour l'instant. Consultez plus tard pour des
                        recommandations.</p>
                </div>
            </div>
        <?php else: ?>
        <div class="events-grid" id="recommended-grid" data-section-carousel>
            <?php foreach ($recommended_events as $event): ?>
                <div class="event-card">
                    <div class="event-card-image-wrapper aspect-square">
                        <div class="event-card-carousel" data-carousel>
                            <?php
                            // Récupérer toutes les images pour cet événement
                            $event_images_query = "SELECT Lien FROM imageevenement WHERE Id_Evenement = ?";
                            $stmt_images = $conn->prepare($event_images_query);
                            $stmt_images->bind_param("i", $event['Id_Evenement']);
                            $stmt_images->execute();
                            $image_result = $stmt_images->get_result();
                            $image_count = $image_result->num_rows;

                            // Ajouter les images supplémentaires au carrousel

                            while ($img = $image_result->fetch_assoc()) {
                                // Éviter de dupliquer la première image
                                echo '<div class="event-card-image">';
                                echo '<img src="' . htmlspecialchars($img['Lien']) . '" alt="' . htmlspecialchars($event['Titre']) . '"/>';
                                echo '</div>';
                            }
                            $stmt_images->close();
                            ?>
                        </div>
                        <?php if ($image_count > 1): ?>
                            <button class="carousel-arrow prev">&lt;</button>
                            <button class="carousel-arrow next">&gt;</button>
                        <?php endif; ?>
                    </div>

                    <a href="?page=details&info-event=<?php echo $event['Id_Evenement']; ?>">
                        <div>
                            <h3 class="event-card-title"><?php echo htmlspecialchars($event['Titre']); ?></h3>
                            <p class="event-card-desc">
                                <?php
                                $desc = htmlspecialchars($event['Description']);
                                echo (strlen($desc) > 100) ? substr($desc, 0, 97) . '...' : $desc;
                                ?>
                            </p>
                            <p class="event-card-meta">
                                <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span>
                                |
                                <span class="event-location"><?php echo htmlspecialchars($event['salle']); ?></span>
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
