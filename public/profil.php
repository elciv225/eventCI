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
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';

// Récupération des événements actifs
$active_events = [];
$stmt_active_events = $conn->prepare("
    SELECT 
        e.Id_Evenement, e.Titre, e.Description, 
        MIN(i.Lien) AS image, 
        c.Libelle AS categorie, 
        v.Libelle AS ville,
        e.DateDebut, e.DateFin
    FROM evenement e
    JOIN creer cr ON e.Id_Evenement = cr.Id_Evenement
    LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
    LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
    LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
    WHERE cr.Id_Utilisateur = ?
    AND e.DateFin > NOW()
    AND e.statut_approbation = 'approuve'
    GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, v.Libelle, e.DateDebut, e.DateFin
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
        v.Libelle AS ville,
        e.DateDebut, e.DateFin
    FROM evenement e
    JOIN creer cr ON e.Id_Evenement = cr.Id_Evenement
    LEFT JOIN imageevenement i ON e.Id_Evenement = i.Id_Evenement
    LEFT JOIN categorieevenement c ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
    LEFT JOIN ville v ON e.Id_Ville = v.Id_Ville
    WHERE cr.Id_Utilisateur = ?
    AND e.DateFin <= NOW()
    GROUP BY e.Id_Evenement, e.Titre, e.Description, c.Libelle, v.Libelle, e.DateDebut, e.DateFin
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
            <a href="?page=profil&tab=active" class="tab-button <?php echo $active_tab === 'active' ? 'active' : ''; ?>">Événements Actifs</a>
            <a href="?page=profil&tab=past" class="tab-button <?php echo $active_tab === 'past' ? 'active' : ''; ?>">Événements Passés</a>
        </div>
        <div class="tab-content-container">
            <div id="active-tab" class="tab-pane <?php echo $active_tab === 'active' ? 'active' : ''; ?>">
                <?php if (!empty($active_events)): ?>
                    <div class="events-grid">
                        <?php foreach ($active_events as $event): ?>
                            <div class="event-card">
                                <div class="event-card-image-wrapper aspect-square">
                                    <div class="event-card-carousel">
                                        <div class="event-card-image">
                                            <img src="../<?php echo !empty($event['image']) ? htmlspecialchars($event['image']) : 'assets/images/default-event.jpg'; ?>"
                                                 alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                        </div>
                                    </div>
                                    <button class="edit-event-btn" data-event-id="<?php echo $event['Id_Evenement']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                                            <path d="M224,76.68a28,28,0,0,0-8.22-19.46L194.57,36a28,28,0,0,0-39.34,0L36,155.25V208H88.75L208,88.75A28,28,0,0,0,224,76.68ZM184.91,68.34,196,79.43l-11.43,11.43-11.09-11.09ZM52,192V169.37l73.09-73.09,22.63,22.63L74.63,192Z"></path>
                                        </svg>
                                    </button>
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
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Vous n'avez aucun événement actif pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div id="past-tab" class="tab-pane <?php echo $active_tab === 'past' ? 'active' : ''; ?>">
                <?php if (!empty($past_events)): ?>
                    <div class="events-grid">
                        <?php foreach ($past_events as $event): ?>
                            <div class="event-card">
                                <div class="event-card-image-wrapper aspect-square">
                                    <div class="event-card-carousel">
                                        <div class="event-card-image">
                                            <img src="../<?php echo !empty($event['image']) ? htmlspecialchars($event['image']) : 'assets/images/default-event.jpg'; ?>"
                                                 alt="<?php echo htmlspecialchars($event['Titre']); ?>"/>
                                        </div>
                                    </div>
                                    <button class="edit-event-btn" data-event-id="<?php echo $event['Id_Evenement']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256">
                                            <path d="M224,76.68a28,28,0,0,0-8.22-19.46L194.57,36a28,28,0,0,0-39.34,0L36,155.25V208H88.75L208,88.75A28,28,0,0,0,224,76.68ZM184.91,68.34,196,79.43l-11.43,11.43-11.09-11.09ZM52,192V169.37l73.09-73.09,22.63,22.63L74.63,192Z"></path>
                                        </svg>
                                    </button>
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
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Vous n'avez aucun événement passé.</p>
                    </div>
                <?php endif; ?>
            </div>
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
        <div class="modal-body">
            <form id="edit-profile-form" method="post" action="traitement_profil.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="date_naissance">Date de naissance</label>
                    <input type="date" id="date_naissance" name="date_naissance" value="<?php echo htmlspecialchars($user['date_naissance'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="photo">Photo de profil</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Enregistrer</button>
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
                    <label for="event-ville">Ville</label>
                    <select id="event-ville" name="idVille" required>
                        <option value="">Sélectionnez une ville</option>
                        <?php
                        // Récupérer les villes depuis la base de données
                        $villes_query = "SELECT Id_Ville, Libelle FROM ville ORDER BY Libelle";
                        $villes_result = $conn->query($villes_query);
                        if ($villes_result && $villes_result->num_rows > 0) {
                            while ($ville = $villes_result->fetch_assoc()) {
                                echo '<option value="' . $ville['Id_Ville'] . '">' . htmlspecialchars($ville['Libelle']) . '</option>';
                            }
                        }
                        ?>
                    </select>
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
                    <label for="event-image">Ajouter des images</label>
                    <input type="file" id="event-image" name="images[]" accept="image/jpeg,image/png,image/gif" multiple>
                    <div id="current-images" class="current-images-container">
                        <!-- Les images actuelles seront affichées ici via JavaScript -->
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Enregistrer les modifications</button>
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
        const editEventBtns = document.querySelectorAll('.edit-event-btn');

        // Fonction pour charger les données d'un événement dans le modal
        function loadEventData(eventId) {
            // Afficher le modal pendant le chargement
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

            // Requête AJAX pour récupérer les données de l'événement
            fetch(`get_event_data.php?id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.event;

                        // Remplir le formulaire avec les données de l'événement
                        document.getElementById('event-id').value = event.Id_Evenement;
                        document.getElementById('event-title').value = event.Titre;
                        document.getElementById('event-description').value = event.Description;
                        document.getElementById('event-location').value = event.Adresse;

                        // Formater les dates pour l'input datetime-local
                        const dateDebut = new Date(event.DateDebut);
                        const dateFin = new Date(event.DateFin);

                        const formatDate = (date) => {
                            const pad = (num) => num.toString().padStart(2, '0');
                            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
                        };

                        document.getElementById('event-date-debut').value = formatDate(dateDebut);
                        document.getElementById('event-date-fin').value = formatDate(dateFin);

                        // Sélectionner la ville et la catégorie
                        document.getElementById('event-ville').value = event.Id_Ville;
                        document.getElementById('event-categorie').value = event.Id_CategorieEvenement;

                        // Afficher les images actuelles
                        const imagesContainer = document.getElementById('current-images');
                        imagesContainer.innerHTML = '';

                        if (event.images && event.images.length > 0) {
                            event.images.forEach(img => {
                                const imgElement = document.createElement('div');
                                imgElement.className = 'current-image';
                                imgElement.innerHTML = `
                                    <img src="../${img.Lien}" alt="${img.Titre}">
                                    <button type="button" class="remove-image" data-image-id="${img.Id_ImageEvenement}">×</button>
                                `;
                                imagesContainer.appendChild(imgElement);
                            });

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
                        } else {
                            imagesContainer.innerHTML = '<p>Aucune image disponible</p>';
                        }
                    } else {
                        alert('Erreur lors du chargement des données de l\'événement');
                        eventModal.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la récupération des données');
                    eventModal.style.display = 'none';
                });
        }

        // Ajouter des gestionnaires d'événements pour les boutons d'édition
        editEventBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const eventId = this.getAttribute('data-event-id');
                loadEventData(eventId);
            });
        });

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
</script>
