<?php
// On s'assure que la session est démarrée et que la connexion à la bdd est faite.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn)) {
    // Assurez-vous que le chemin vers votre fichier de configuration est correct
    require_once __DIR__ . '/../config/base.php';
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    // Rediriger vers la page d'authentification si non connecté
    header('Location: authentification.php');
    exit;
}

$user_id = $_SESSION['utilisateur']['id'];
$user = $_SESSION['utilisateur'];


// --- LOGIQUE POUR RÉCUPÉRER LES DONNÉES ---
// Récupération des statistiques réelles depuis la base de données

// Récupérer le nombre total d'événements créés par l'utilisateur
$stmt_total = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM evenement e 
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement 
    WHERE c.Id_Utilisateur = ?
");
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_events = $result_total->fetch_assoc()['total'] ?? 0;
$stmt_total->close();

// Récupérer le nombre de tickets vendus pour les événements de l'utilisateur
$stmt_tickets = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM achat ta 
    JOIN ticketevenement te ON ta.Id_TicketEvenement = te.Id_TicketEvenement 
    JOIN evenement e ON te.Id_Evenement = e.Id_Evenement 
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement 
    WHERE c.Id_Utilisateur = ?
");
$stmt_tickets->bind_param("i", $user_id);
$stmt_tickets->execute();
$result_tickets = $stmt_tickets->get_result();
$tickets_sold = $result_tickets->fetch_assoc()['total'] ?? 0;
$stmt_tickets->close();

// Récupérer le nombre d'événements actifs (date de fin > maintenant)
$stmt_active = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM evenement e 
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement 
    WHERE c.Id_Utilisateur = ? 
    AND e.DateFin > NOW() 
    AND e.statut_approbation = 'approuve'
");
$stmt_active->bind_param("i", $user_id);
$stmt_active->execute();
$result_active = $stmt_active->get_result();
$active_events_count = $result_active->fetch_assoc()['total'] ?? 0;
$stmt_active->close();

// Récupérer le nombre d'événements en attente d'approbation
$stmt_review = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM evenement e 
    JOIN creer c ON e.Id_Evenement = c.Id_Evenement 
    WHERE c.Id_Utilisateur = ? 
    AND e.statut_approbation = 'en_attente'
");
$stmt_review->bind_param("i", $user_id);
$stmt_review->execute();
$result_review = $stmt_review->get_result();
$events_in_review = $result_review->fetch_assoc()['total'] ?? 0;
$stmt_review->close();

// Stocker les statistiques dans un tableau
$stats = [
    'total_events' => $total_events,
    'tickets_sold' => $tickets_sold,
    'active_events' => $active_events_count,
    'events_in_review' => $events_in_review,
];

// Déterminer quel onglet est actif (par défaut: active)
$active_tab = $_GET['tab'] ?? 'active';

// Récupération des événements actifs
$active_events = [];
$stmt_active_events = $conn->prepare("
    SELECT 
        e.Id_Evenement, e.Titre, e.Description, 
        MIN(i.Lien) AS image, 
        c.Libelle AS categorie, 
        e.Salle AS salle,
        e.DateDebut, e.DateFin
    FROM evenement e
    JOIN creer cr ON e.Id_Evenement = cr.Id_Evenement
    LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
    LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
    WHERE cr.Id_Utilisateur = ?
    AND e.DateFin > NOW()
    AND e.statut_approbation = 'approuve'
    GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, e.Salle, e.DateDebut, e.DateFin
    ORDER BY e.DateDebut ASC
");
$stmt_active_events->bind_param("i", $user_id);
$stmt_active_events->execute();
$result_active_events = $stmt_active_events->get_result();
while ($row = $result_active_events->fetch_assoc()) {
    $active_events[] = $row;
}
$stmt_active_events->close();

// Récupération des événements passés
$past_events = [];
$stmt_past_events = $conn->prepare("
    SELECT 
        e.Id_Evenement, e.Titre, e.Description, 
        MIN(i.Lien) AS image, 
        c.Libelle AS categorie, 
        e.Salle AS salle,
        e.DateDebut, e.DateFin
    FROM evenement e
    JOIN creer cr ON e.Id_Evenement = cr.Id_Evenement
    LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
    LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
    WHERE cr.Id_Utilisateur = ?
    AND e.DateFin <= NOW()
    GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, e.Salle, e.DateDebut, e.DateFin
    ORDER BY e.DateFin DESC
");
$stmt_past_events->bind_param("i", $user_id);
$stmt_past_events->execute();
$result_past_events = $stmt_past_events->get_result();
while ($row = $result_past_events->fetch_assoc()) {
    $past_events[] = $row;
}
$stmt_past_events->close();

// Date d'inscription (à récupérer de la BDD, ex: '2021-01-15')
$join_date = $user['date_creation'] ?? '2021-01-15';
$join_year = date('Y', strtotime($join_date));


// Récupération des événements en attente d'approbation
$events_in_review_list = [];
$stmt_review_events = $conn->prepare("
    SELECT 
        e.Id_Evenement, e.Titre, e.Description, 
        MIN(i.Lien) AS image, 
        c.Libelle AS categorie, 
        e.Salle AS salle,
        e.DateDebut, e.DateFin
    FROM evenement e
    JOIN creer cr ON e.Id_Evenement = cr.Id_Evenement
    LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
    LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
    WHERE cr.Id_Utilisateur = ?
    AND e.statut_approbation = 'en_attente'
    GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, e.Salle, e.DateDebut, e.DateFin
    ORDER BY e.DateDebut ASC
");
$stmt_review_events->bind_param("i", $user_id);
$stmt_review_events->execute();
$result_review_events = $stmt_review_events->get_result();
while ($row = $result_review_events->fetch_assoc()) {
    $events_in_review_list[] = $row;
}
$stmt_review_events->close();

// Récupération des données d'un événement pour modification si un ID est fourni
$event_to_edit = null;
if (isset($_GET['edit-event']) && !empty($_GET['edit-event'])) {
    $event_id = (int)$_GET['edit-event'];

    // Vérifier que l'événement appartient à l'utilisateur
    $stmt_check = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM evenement e 
        JOIN creer c ON e.Id_Evenement = c.Id_Evenement 
        WHERE e.Id_Evenement = ? AND c.Id_Utilisateur = ?
    ");
    $stmt_check->bind_param("ii", $event_id, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $count = $result_check->fetch_assoc()['count'] ?? 0;
    $stmt_check->close();

    if ($count > 0) {
        // Récupérer les données de l'événement
        $stmt_event = $conn->prepare("
            SELECT 
                e.Id_Evenement, e.Titre, e.Description, e.Adresse, 
                e.DateDebut, e.DateFin, e.Salle, e.Id_CategorieEvenement,
                e.Salle as salle_nom,
                c.Libelle as categorie_nom
            FROM evenement e
            LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
            WHERE e.Id_Evenement = ?
        ");
        $stmt_event->bind_param("i", $event_id);
        $stmt_event->execute();
        $result_event = $stmt_event->get_result();
        $event_to_edit = $result_event->fetch_assoc();
        $stmt_event->close();

        if ($event_to_edit) {
            // Récupérer les images de l'événement
            $stmt_images = $conn->prepare("
                SELECT Id_ImageEvenement, Titre, Description, Lien
                FROM imageevenement
                WHERE Id_Evenement = ?
            ");
            $stmt_images->bind_param("i", $event_id);
            $stmt_images->execute();
            $result_images = $stmt_images->get_result();
            $images = [];
            while ($row = $result_images->fetch_assoc()) {
                $images[] = $row;
            }
            $stmt_images->close();

            // Ajouter les images à l'événement
            $event_to_edit['images'] = $images;
        }
    }
}
?>

<main class="profile-page-container">
    <section class="profile-header-section">
        <div class="profile-pic-large">
            <?php if (!empty($user['photo'])): ?>
            <img src="<?php echo htmlspecialchars($user['photo']); ?>" alt="Photo de profil" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="profile-initials-large" style="display: none;">
                <?php else: ?>
                <div class="profile-initials-large">
                    <?php endif; ?>
                    <?php
                    $initials = '';
                    if (!empty($user['prenom'])) $initials .= strtoupper(substr($user['prenom'], 0, 1));
                    if (!empty($user['nom'])) $initials .= strtoupper(substr($user['nom'], 0, 1));
                    echo htmlspecialchars($initials);
                    ?>
                </div>
            </div>
            <h2 class="profile-name"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h2>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            <p class="profile-joined">Inscrit en <?php echo $join_year; ?></p>
            <button id="edit-profile-btn" class="btn-primary">Modifier le profil</button>
    </section>

    <section class="profile-stats-section">
        <div class="stat-card">
            <span class="stat-number"><?php echo $stats['total_events']; ?></span>
            <span class="stat-label">Événements créés</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $stats['tickets_sold']; ?></span>
            <span class="stat-label">Tickets vendus</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $stats['active_events']; ?></span>
            <span class="stat-label">Événements actifs</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $stats['events_in_review']; ?></span>
            <span class="stat-label">En attente</span>
        </div>
    </section>

    <section class="profile-events-section">
        <div class="tabs">
            <a href="?page=mon-profil&tab=active" class="tab-button <?php echo $active_tab === 'active' ? 'active' : ''; ?>">Événements Actifs</a>
            <a href="?page=mon-profil&tab=en_attente" class="tab-button <?php echo $active_tab === 'en_attente' ? 'active' : ''; ?>">Événements en Attente</a>
            <a href="?page=mon-profil&tab=past" class="tab-button <?php echo $active_tab === 'past' ? 'active' : ''; ?>">Événements Passés</a>
            <a href="?page=mon-profil&tab=notifications" class="tab-button <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">Notifications</a>
        </div>

        <div class="tab-content-container">
            <?php if ($active_tab === 'notifications'): ?>
                <div id="notifications-tab" class="tab-pane active">
                    <?php
                    // Marquer les notifications comme lues
                    $update_notif = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
                    $update_notif->bind_param("i", $user_id);
                    $update_notif->execute();
                    $update_notif->close();

                    // Récupérer les notifications
                    $stmt_notif = $conn->prepare("SELECT message, created_at, related_link FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt_notif->bind_param("i", $user_id);
                    $stmt_notif->execute();
                    $notifications = $stmt_notif->get_result();
                    ?>
                    <div class="notifications-list">
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while ($notif = $notifications->fetch_assoc()): ?>
                                <div class="notification-item">
                                    <a href="<?php echo htmlspecialchars($notif['related_link']); ?>" class="notification-link">
                                        <p class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <span class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></span>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Vous n'avez aucune notification.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif($active_tab === 'active'): ?>
                <div id="active-tab" class="tab-pane active">
                    <?php if (!empty($active_events)): ?>
                        <div class="events-grid">
                            <?php foreach ($active_events as $event): ?>
                                <div class="event-card">
                                    <div class="event-card-image-wrapper aspect-square">
                                        <div class="event-card-carousel" data-carousel>
                                            <div class="event-card-image">
                                                <img src="../<?php echo !empty($event['image']) ? htmlspecialchars($event['image']) : 'assets/images/default-event.jpg'; ?>"
                                                     alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                            </div>
                                        </div>
                                        <button class="carousel-arrow prev">&lt;</button>
                                        <button class="carousel-arrow next">&gt;</button>
                                        <div class="event-card-actions">
                                            <a href="?page=mon-profil&tab=active&edit-event=<?php echo $event['Id_Evenement']; ?>" class="edit-event-btn" title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M224,76.68a28,28,0,0,0-8.22-19.46L194.57,36a28,28,0,0,0-39.34,0L36,155.25V208H88.75L208,88.75A28,28,0,0,0,224,76.68ZM184.91,68.34,196,79.43l-11.43,11.43-11.09-11.09ZM52,192V169.37l73.09-73.09,22.63,22.63L74.63,192Z"></path></svg>
                                            </a>
                                            <form method="POST" action="public/traitement_evenement.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.');" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_evenement">
                                                <input type="hidden" name="event_id" value="<?php echo $event['Id_Evenement']; ?>">
                                                <button type="submit" class="delete-event-btn" title="Supprimer">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"></path></svg>
                                                </button>
                                            </form>
                                        </div>
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
                                            <p class="event-card-meta" style="color: var(--text-primary)">
                                                <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span> |
                                                <span class="event-location"><?php echo htmlspecialchars($event['salle']); ?></span>
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Vous n'avez aucun événement actif pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($active_tab === 'en_attente'): ?>
                <div id="en-attente-tab" class="tab-pane active">
                    <?php if (!empty($events_in_review_list)): ?>
                        <div class="events-grid">
                            <?php foreach ($events_in_review_list as $event): ?>
                                <div class="event-card">
                                    <div class="event-card-image-wrapper aspect-square">
                                        <div class="event-card-carousel" data-carousel>
                                            <div class="event-card-image">
                                                <img src="../<?php echo !empty($event['image']) ? htmlspecialchars($event['image']) : 'assets/images/default-event.jpg'; ?>"
                                                     alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                            </div>
                                        </div>
                                        <button class="carousel-arrow prev">&lt;</button>
                                        <button class="carousel-arrow next">&gt;</button>
                                        <div class="event-status-badge pending">En attente d'approbation</div>
                                        <div class="event-card-actions">
                                            <a href="?page=mon-profil&tab=en_attente&edit-event=<?php echo $event['Id_Evenement']; ?>" class="edit-event-btn" title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M224,76.68a28,28,0,0,0-8.22-19.46L194.57,36a28,28,0,0,0-39.34,0L36,155.25V208H88.75L208,88.75A28,28,0,0,0,224,76.68ZM184.91,68.34,196,79.43l-11.43,11.43-11.09-11.09ZM52,192V169.37l73.09-73.09,22.63,22.63L74.63,192Z"></path></svg>
                                            </a>
                                            <form method="POST" action="public/traitement_evenement.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.');" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_evenement">
                                                <input type="hidden" name="event_id" value="<?php echo $event['Id_Evenement']; ?>">
                                                <button type="submit" class="delete-event-btn" title="Supprimer">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="event-card-title"><?php echo htmlspecialchars($event['Titre']); ?></h3>
                                        <p class="event-card-desc">
                                            <?php
                                            $desc = htmlspecialchars($event['Description']);
                                            echo (strlen($desc) > 100) ? substr($desc, 0, 97) . '...' : $desc;
                                            ?>
                                        </p>
                                        <p class="event-card-meta" style="color: var(--text-primary)">
                                            <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span> |
                                            <span class="event-location"><?php echo htmlspecialchars($event['salle']); ?></span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Vous n'avez aucun événement en attente d'approbation.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($active_tab === 'past'): ?>
                <div id="past-tab" class="tab-pane active">
                    <?php if (!empty($past_events)): ?>
                        <div class="events-grid">
                            <?php foreach ($past_events as $event): ?>
                                <div class="event-card">
                                    <div class="event-card-image-wrapper aspect-square">
                                        <div class="event-card-carousel" data-carousel>
                                            <div class="event-card-image">
                                                <img src="../<?php echo !empty($event['image']) ? htmlspecialchars($event['image']) : 'assets/images/default-event.jpg'; ?>"
                                                     alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                            </div>
                                        </div>
                                        <button class="carousel-arrow prev">&lt;</button>
                                        <button class="carousel-arrow next">&gt;</button>
                                        <div class="event-card-actions">
                                            <a href="?page=mon-profil&tab=past&edit-event=<?php echo $event['Id_Evenement']; ?>" class="edit-event-btn" title="Modifier">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M224,76.68a28,28,0,0,0-8.22-19.46L194.57,36a28,28,0,0,0-39.34,0L36,155.25V208H88.75L208,88.75A28,28,0,0,0,224,76.68ZM184.91,68.34,196,79.43l-11.43,11.43-11.09-11.09ZM52,192V169.37l73.09-73.09,22.63,22.63L74.63,192Z"></path></svg>
                                            </a>
                                            <form method="POST" action="public/traitement_evenement.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.');" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_evenement">
                                                <input type="hidden" name="event_id" value="<?php echo $event['Id_Evenement']; ?>">
                                                <button type="submit" class="delete-event-btn" title="Supprimer">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"></path></svg>
                                                </button>
                                            </form>
                                        </div>
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
                                            <p class="event-card-meta" style="color: var(--text-primary)">
                                                <span class="event-category"><?php echo htmlspecialchars($event['categorie']); ?></span> |
                                                <span class="event-location"><?php echo htmlspecialchars($event['salle']); ?></span>
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Vous n'avez aucun événement passé.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Modal pour modifier le profil (gardé de l'ancien code) -->
<div class="modal" id="edit-profile-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier mon profil</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto; max-height: calc(80vh - 60px);">
            <form id="edit-profile-form" method="post" action="traitement_profil.php" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($user['date_naissance'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="photo-uploader" for="photo" id="photoUploader">
                            <span class="photo-uploader-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor"
                                     viewBox="0 0 16 16"><path
                                            d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14a1 1 0 0 1 1 1v6zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2z"/><path
                                            d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zm0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM3 6.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/></svg>
                            </span>
                            <div class="photo-preview" id="photo-preview">
                                <?php if (!empty($user['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['photo']); ?>" alt="Prévisualisation de la photo de profil">
                                <?php else: ?>
                                    <div class="photo-placeholder">Ajouter une photo</div>
                                <?php endif; ?>
                            </div>
                            <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Laissez vide pour ne pas changer">
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirmer le mot de passe</label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirmer le nouveau mot de passe">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="modifier_profil" class="btn-primary">Enregistrer</button>
                    <button type="button" class="btn-secondary modal-close">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour modifier un événement -->
<div class="modal" id="edit-event-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier l'événement</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto; max-height: calc(80vh - 60px);">
            <form id="edit-event-form" method="post" action="traitement_evenement.php" enctype="multipart/form-data">
                <input type="hidden" id="event-id" name="event_id" value="">

                <div class="modal-tabs">
                    <button type="button" class="modal-tab active" data-tab="event-details">Événement</button>
                    <button type="button" class="modal-tab" data-tab="ticket-management">Tickets</button>
                </div>

                <div id="event-details" class="modal-tab-content active">
                    <div class="form-group">
                        <label for="event-title">Titre de l'événement</label>
                        <input type="text" id="event-title" name="titre" value="" required>
                    </div>

                    <div class="form-group">
                        <label for="event-description">Description</label>
                        <textarea id="event-description" name="description" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="event-location">Adresse</label>
                        <input type="text" id="event-location" name="adresse" value="" required>
                    </div>

                    <div class="form-group">
                        <label for="event-date-debut">Date et heure de début</label>
                        <input type="datetime-local" id="event-date-debut" name="dateDebut" value="" required>
                    </div>

                    <div class="form-group">
                        <label for="event-date-fin">Date et heure de fin</label>
                        <input type="datetime-local" id="event-date-fin" name="dateFin" value="" required>
                    </div>

                    <div class="form-group">
                        <label for="event-salle">Salle</label>
                        <input type="text" id="event-salle" name="salle" required>
                    </div>

                    <div class="form-group">
                        <label for="event-categorie">Catégorie</label>
                        <select id="event-categorie" name="idCategorieEvenement" required>
                            <option value="">Sélectionnez une catégorie</option>
                            <?php
                            // Récupérer les catégories depuis la base de données
                            $categories_query = "SELECT Id_CategorieEvenement, Libelle FROM categorieevenement ORDER BY Libelle";
                            $categories_result = $conn->query($categories_query);
                            if ($categories_result && $categories_result->num_rows > 0) {
                                while ($categorie = $categories_result->fetch_assoc()) {
                                    echo '<option value="' . $categorie['Id_CategorieEvenement'] . '">' . htmlspecialchars($categorie['Libelle']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Images de l'événement</label>
                        <div class="image-uploader" id="imageUploader">
                            <div class="image-uploader-icon" id="imageUploaderIcon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor"
                                     viewBox="0 0 256 256">
                                    <path d="M208,128a80,80,0,1,1-80-80,80.09,80.09,0,0,1,80,80Z" opacity="0.2"></path>
                                    <path d="M240,128a112,112,0,1,1-112-112,112,112,0,0,1,112,112Zm-48-48a24,24,0,1,0-24-24,24,24,0,0,0,24,24Zm-41.18,90.48-33.34-27.78a8,8,0,0,0-11,0l-56,46.66A8,8,0,0,0,56,200H200a8,8,0,0,0,5.18-14.48Z"></path>
                                </svg>
                            </div>
                            <div class="image-preview-container">
                                <div id="new-images-preview" class="image-preview"></div>
                            </div>
                            <p class="image-uploader-text">Utilisez des images de haute qualité (JPG, PNG, GIF)</p>
                            <input type="file" id="event-image" name="images[]" accept="image/jpeg,image/png,image/gif" multiple
                                   style="display: none;">
                            <button type="button" class="btn btn-secondary" id="uploadButton">Télécharger</button>
                        </div>
                        <div id="current-images" class="current-images-container">
                            <!-- Les images actuelles seront affichées ici via JavaScript -->
                        </div>
                    </div>
                </div>

                <div id="ticket-management" class="modal-tab-content">
                    <div class="form-group">
                        <input id="ticket-name" type="text" placeholder=" " class="form-input"/>
                        <label class="form-label" for="ticket-name">Nom du ticket</label>
                        <!-- Champ caché pour stocker les tickets -->
                        <input type="hidden" id="tickets-data" name="tickets"/>
                    </div>
                    <div class="form-group-row">
                        <div class="form-group">
                            <input id="ticket-quantity" type="number" placeholder=" " class="form-input"/>
                            <label class="form-label" for="ticket-quantity">Quantité</label>
                        </div>
                        <div class="form-group">
                            <div class="price-input-container">
                                <input id="ticket-price" type="text" placeholder=" " class="form-input"/>
                                <label class="form-label" for="ticket-price">Prix</label>
                                <div class="checkbox-container inside-input">
                                    <input type="checkbox" id="free-ticket" class="form-checkbox"/>
                                    <label for="free-ticket" class="checkbox-label">Gratuit</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <input id="ticket-description" type="text" placeholder=" " class="form-input"/>
                        <label class="form-label" for="ticket-description">Description du ticket</label>
                    </div>
                    <div class="form-actions-multiple">
                        <button id="add-ticket-btn" type="button" class="btn btn-secondary">Ajouter un autre ticket</button>
                    </div>
                    <!-- Container for multiple ticket previews -->
                    <div id="all-tickets-container" class="all-tickets-container">
                        <h3 class="preview-title">Tickets ajoutés</h3>
                        <div id="all-tickets-preview" class="all-tickets-preview">
                            <div class="tickets-preview-empty">
                                Aucun ticket ajouté
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="modifier_evenement" class="btn-primary">Enregistrer les modifications</button>
                    <button type="button" class="btn-secondary modal-close">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du modal de profil
        const profileModal = document.getElementById('edit-profile-modal');
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const closeBtns = document.querySelectorAll('.modal-close');

        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', function() {
                profileModal.style.display = 'block';
            });
        }

        // Gestion du modal d'édition d'événement
        const eventModal = document.getElementById('edit-event-modal');

        // Ajouter des gestionnaires d'événements pour les boutons d'édition d'événement
        document.querySelectorAll('.edit-event-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Empêcher le comportement par défaut (navigation)
                e.preventDefault();

                // Récupérer l'ID de l'événement depuis l'URL du lien
                const href = this.getAttribute('href');
                const eventId = new URLSearchParams(href.substring(href.indexOf('?'))).get('edit-event');

                // Rediriger vers la même URL pour charger les données de l'événement
                window.location.href = href;
            });
        });

        <?php if ($event_to_edit): ?>
        // Si un événement à éditer est fourni par PHP, afficher le modal automatiquement
        // Afficher le modal
        eventModal.style.display = 'block';

        // Ajouter un champ caché pour l'onglet actif
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'active';

        // Vérifier si le champ existe déjà
        let activeTabInput = document.getElementById('active-tab-input');
        if (!activeTabInput) {
            activeTabInput = document.createElement('input');
            activeTabInput.type = 'hidden';
            activeTabInput.id = 'active-tab-input';
            activeTabInput.name = 'active_tab';
            document.getElementById('edit-event-form').appendChild(activeTabInput);
        }
        activeTabInput.value = activeTab;

        // Remplir le formulaire avec les données de l'événement
        document.getElementById('event-id').value = <?php echo json_encode($event_to_edit['Id_Evenement']); ?>;
        document.getElementById('event-title').value = <?php echo json_encode($event_to_edit['Titre']); ?>;
        document.getElementById('event-description').value = <?php echo json_encode($event_to_edit['Description']); ?>;
        document.getElementById('event-location').value = <?php echo json_encode($event_to_edit['Adresse']); ?>;

        // Formater les dates pour l'input datetime-local
        const dateDebut = new Date(<?php echo json_encode($event_to_edit['DateDebut']); ?>);
        const dateFin = new Date(<?php echo json_encode($event_to_edit['DateFin']); ?>);

        const formatDate = (date) => {
            const pad = (num) => num.toString().padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        };

        document.getElementById('event-date-debut').value = formatDate(dateDebut);
        document.getElementById('event-date-fin').value = formatDate(dateFin);

        // Sélectionner la ville et la catégorie
        document.getElementById('event-ville').value = <?php echo json_encode($event_to_edit['Id_Ville']); ?>;
        document.getElementById('event-categorie').value = <?php echo json_encode($event_to_edit['Id_CategorieEvenement']); ?>;

        // Afficher les images actuelles
        const imagesContainer = document.getElementById('current-images');
        imagesContainer.innerHTML = '';

        <?php if (!empty($event_to_edit['images'])): ?>
        let imgElement
            <?php foreach ($event_to_edit['images'] as $img): ?>
                imgElement = document.createElement('div');
                imgElement.className = 'current-image';
                imgElement.innerHTML = `
                    <img src="../<?php echo htmlspecialchars($img['Lien']); ?>" alt="<?php echo htmlspecialchars($img['Titre'] ?? ''); ?>">
                    <button type="button" class="remove-image" data-image-id="<?php echo $img['Id_ImageEvenement']; ?>">×</button>
                `;
                imagesContainer.appendChild(imgElement);
            <?php endforeach; ?>

            // Ajouter des gestionnaires d'événements pour les boutons de suppression d'image
            document.querySelectorAll('.remove-image').forEach(btn => {
                btn.addEventListener('click', function() {
                    const imageId = this.getAttribute('data-image-id');
                    this.parentElement.remove();

                    // Ajouter un champ caché pour indiquer que cette image doit être supprimée
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'remove_images[]';
                    hiddenInput.value = imageId;
                    document.getElementById('edit-event-form').appendChild(hiddenInput);
                });
            });
        <?php else: ?>
            imagesContainer.innerHTML = '<p>Aucune image disponible</p>';
        <?php endif; ?>

        // Récupérer les tickets existants pour cet événement
        fetch('get_event_tickets.php?id=<?php echo $event_to_edit['Id_Evenement']; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tickets && data.tickets.length > 0) {
                    // Réinitialiser le tableau de tickets
                    tickets = [];

                    // Ajouter chaque ticket au tableau
                    data.tickets.forEach(ticket => {
                        tickets.push({
                            name: ticket.Titre,
                            description: ticket.Description,
                            price: ticket.Prix > 0 ? ticket.Prix : 'Gratuit',
                            quantity: ticket.NombreDisponible
                        });
                    });

                    // Mettre à jour l'affichage et le champ caché
                    updateTicketsData();
                    updateTicketsPreview();
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des tickets:', error);
            });
        <?php endif; ?>

        // Fermer les modals
        closeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                profileModal.style.display = 'none';
                eventModal.style.display = 'none';
            });
        });

        // Fermer les modals en cliquant à l'extérieur
        window.addEventListener('click', function(event) {
            if (event.target === profileModal) {
                profileModal.style.display = 'none';
            }
            if (event.target === eventModal) {
                eventModal.style.display = 'none';
            }
        });
    });

    // Prévisualisation des images pour le profil
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photo-preview');
    const photoUploader = document.getElementById('photoUploader');

    if (photoInput && photoPreview && photoUploader) {
        // Cliquer sur le photoUploader déclenche le input file
        photoUploader.addEventListener('click', function(e) {
            if (e.target !== photoInput) { // Éviter de déclencher deux fois si on clique sur l'input
                photoInput.click();
            }
        });

        photoInput.addEventListener('change', function() {
            // Vider la prévisualisation actuelle
            photoPreview.innerHTML = '';

            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Prévisualisation de la photo de profil';
                    photoPreview.appendChild(img);
                }

                reader.readAsDataURL(this.files[0]);
            } else {
                photoPreview.innerHTML = '<div class="photo-placeholder">Ajouter une photo</div>';
            }
        });
    }

    // Gestion des onglets dans le modal d'édition d'événement
    const modalTabs = document.querySelectorAll('.modal-tab');
    const modalTabContents = document.querySelectorAll('.modal-tab-content');

    if (modalTabs.length > 0) {
        modalTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Retirer la classe active de tous les onglets
                modalTabs.forEach(t => t.classList.remove('active'));

                // Ajouter la classe active à l'onglet cliqué
                this.classList.add('active');

                // Masquer tous les contenus d'onglets
                modalTabContents.forEach(content => content.classList.remove('active'));

                // Afficher le contenu correspondant à l'onglet cliqué
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    }

    // Prévisualisation des images pour les événements
    const eventImageInput = document.getElementById('event-image');
    const newImagesPreview = document.getElementById('new-images-preview');
    const imageUploader = document.getElementById('imageUploader');
    const uploadButton = document.getElementById('uploadButton');

    if (eventImageInput && newImagesPreview && imageUploader && uploadButton) {
        // Ajouter un message si aucune nouvelle image n'est sélectionnée
        if (newImagesPreview.innerHTML.trim() === '') {
            newImagesPreview.innerHTML = '<p>Sélectionnez des images à ajouter</p>';
        }

        // Cliquer sur le bouton déclenche l'input file
        uploadButton.addEventListener('click', function() {
            eventImageInput.click();
        });

        // Cliquer sur l'uploader déclenche également l'input file
        imageUploader.addEventListener('click', function(e) {
            if (e.target !== eventImageInput && e.target !== uploadButton && !e.target.closest('button')) {
                eventImageInput.click();
            }
        });

        eventImageInput.addEventListener('change', function() {
            // Vider la prévisualisation des nouvelles images
            newImagesPreview.innerHTML = '';

            if (this.files && this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Prévisualisation de l\'image ' + (i + 1);

                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-preview';
                        removeBtn.innerHTML = '×';
                        removeBtn.setAttribute('type', 'button');
                        removeBtn.addEventListener('click', function(e) {
                            e.stopPropagation(); // Empêcher le déclenchement du click sur l'uploader
                            previewItem.remove();

                            // Si toutes les prévisualisations sont supprimées, afficher le message
                            if (newImagesPreview.querySelectorAll('.preview-item').length === 0) {
                                newImagesPreview.innerHTML = '<p>Sélectionnez des images à ajouter</p>';
                            }
                        });

                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        newImagesPreview.appendChild(previewItem);
                    }

                    reader.readAsDataURL(file);
                }
            } else {
                newImagesPreview.innerHTML = '<p>Sélectionnez des images à ajouter</p>';
            }
        });
    }

    // Gestion des tickets
    const addTicketBtn = document.getElementById('add-ticket-btn');
    const ticketsPreview = document.getElementById('all-tickets-preview');
    const ticketsData = document.getElementById('tickets-data');
    const freeTicketCheckbox = document.getElementById('free-ticket');
    const ticketPriceInput = document.getElementById('ticket-price');

    // Tableau pour stocker les tickets
    let tickets = [];

    // Fonction pour mettre à jour le champ caché avec les données des tickets
    function updateTicketsData() {
        ticketsData.value = JSON.stringify(tickets);
    }

    // Fonction pour mettre à jour l'affichage des tickets
    function updateTicketsPreview() {
        if (tickets.length === 0) {
            ticketsPreview.innerHTML = '<div class="tickets-preview-empty">Aucun ticket ajouté</div>';
            return;
        }

        ticketsPreview.innerHTML = '';
        tickets.forEach((ticket, index) => {
            const ticketElement = document.createElement('div');
            ticketElement.className = 'ticket-preview';
            ticketElement.innerHTML = `
                <div class="ticket-preview-header">
                    <h4>${ticket.name}</h4>
                    <div class="ticket-preview-actions">
                        <button type="button" class="edit-ticket" data-index="${index}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                <path d="M224,76.68a28,28,0,0,0-8.22-19.46L194.57,36a28,28,0,0,0-39.34,0L36,155.25V208H88.75L208,88.75A28,28,0,0,0,224,76.68ZM184.91,68.34,196,79.43l-11.43,11.43-11.09-11.09ZM52,192V169.37l73.09-73.09,22.63,22.63L74.63,192Z"></path>
                            </svg>
                        </button>
                        <button type="button" class="delete-ticket" data-index="${index}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256">
                                <path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="ticket-preview-details">
                    <p>${ticket.description || 'Aucune description'}</p>
                    <div class="ticket-preview-info">
                        <span class="ticket-price">${ticket.price === 'Gratuit' ? 'Gratuit' : ticket.price + ' €'}</span>
                        <span class="ticket-quantity">${ticket.quantity} disponibles</span>
                    </div>
                </div>
            `;
            ticketsPreview.appendChild(ticketElement);
        });

        // Ajouter des gestionnaires d'événements pour les boutons
        document.querySelectorAll('.delete-ticket').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                tickets.splice(index, 1);
                updateTicketsData();
                updateTicketsPreview();
            });
        });

        document.querySelectorAll('.edit-ticket').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                const ticket = tickets[index];

                // Remplir les champs avec les données du ticket
                document.getElementById('ticket-name').value = ticket.name;
                document.getElementById('ticket-description').value = ticket.description || '';

                if (ticket.price === 'Gratuit') {
                    freeTicketCheckbox.checked = true;
                    ticketPriceInput.value = '';
                    ticketPriceInput.disabled = true;
                } else {
                    freeTicketCheckbox.checked = false;
                    ticketPriceInput.value = ticket.price;
                    ticketPriceInput.disabled = false;
                }

                document.getElementById('ticket-quantity').value = ticket.quantity;

                // Supprimer le ticket de la liste
                tickets.splice(index, 1);
                updateTicketsData();
                updateTicketsPreview();
            });
        });
    }

    // Gestion du checkbox "Gratuit"
    if (freeTicketCheckbox && ticketPriceInput) {
        freeTicketCheckbox.addEventListener('change', function() {
            if (this.checked) {
                ticketPriceInput.value = '';
                ticketPriceInput.disabled = true;
            } else {
                ticketPriceInput.disabled = false;
            }
        });
    }

    if (addTicketBtn) {
        addTicketBtn.addEventListener('click', function() {
            // Récupérer les valeurs des champs
            const ticketName = document.getElementById('ticket-name').value;
            const ticketDescription = document.getElementById('ticket-description').value;
            let ticketPrice = freeTicketCheckbox.checked ? 'Gratuit' : document.getElementById('ticket-price').value;
            const ticketQuantity = document.getElementById('ticket-quantity').value;

            // Validation simple
            if (!ticketName) {
                alert('Veuillez entrer un nom pour le ticket.');
                return;
            }

            if (!ticketQuantity || ticketQuantity <= 0) {
                alert('Veuillez entrer une quantité valide.');
                return;
            }

            if (!freeTicketCheckbox.checked && (!ticketPrice || ticketPrice <= 0)) {
                alert('Veuillez entrer un prix valide ou cocher "Gratuit".');
                return;
            }

            // Ajouter le ticket au tableau
            tickets.push({
                name: ticketName,
                description: ticketDescription,
                price: ticketPrice,
                quantity: ticketQuantity
            });

            // Mettre à jour le champ caché et l'affichage
            updateTicketsData();
            updateTicketsPreview();

            // Réinitialiser les champs
            document.getElementById('ticket-name').value = '';
            document.getElementById('ticket-description').value = '';
            document.getElementById('ticket-price').value = '';
            document.getElementById('ticket-quantity').value = '';
            freeTicketCheckbox.checked = false;
            ticketPriceInput.disabled = false;
        });
    }
</script>

<style>
.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    padding: 10px;
}
.notification-item {
    background-color: var(--card-bg);
    border-left: 4px solid var(--accent-blue);
    padding: 20px;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
    transition: background-color 0.3s, transform 0.2s;
}
.notification-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}
.notification-link {
    text-decoration: none;
    color: inherit;
    display: block;
}
.notification-message {
    margin: 0 0 8px 0;
    color: var(--text-dark);
    font-weight: 500;
}
.notification-date {
    font-size: 0.85em;
    color: var(--text-medium);
}
</style>
