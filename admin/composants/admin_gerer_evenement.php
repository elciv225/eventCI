<?php
// Traitement d'une validation ou d'un rejet
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if (in_array($action, ['approuve', 'rejete'])) {
        $stmt = $conn->prepare("UPDATE evenement SET statut_approbation = ? WHERE Id_Evenement = ?");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Récupération des événements en attente
$sql = "SELECT Id_Evenement, Titre, Description, Adresse, DateDebut, DateFin FROM evenement WHERE statut_approbation = 'en_attente'";
$result = $conn->query($sql);
?>

<h1 class="section-title animate_animated animate_fadeIn">Événements en attente de validation</h1>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    /* Styles spécifiques pour la gestion des événements */
    .event {
        background: var(--card-bg);
        padding: 30px;
        border-left: 8px solid var(--accent-orange);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-md);
        margin-bottom: 30px;
        width: 100%;
        box-sizing: border-box;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .event:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .event h3 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.2em;
        color: var(--text-dark);
        margin-top: 0;
        margin-bottom: 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        padding-bottom: 10px;
    }

    .event p {
        margin-bottom: 10px;
        font-size: 0.95em;
        color: var(--text-medium);
    }

    .event p strong {
        color: var(--text-dark);
        font-weight: 600;
    }

    /* Buttons */
    .event form {
        margin-top: 25px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }

    .event button {
        padding: 12px 22px;
        border: none;
        border-radius: 30px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9em;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .event button:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }

    .accept {
        background-color: #28a745;
        color: white;
    }

    .accept:hover {
        background-color: #218838;
    }

    .reject {
        background-color: #dc3545;
        color: white;
    }

    .reject:hover {
        background-color: #c82333;
    }

    .comment {
        background-color: var(--accent-orange);
        color: white;
    }

    .comment:hover {
        background-color: var(--accent-dark-orange);
    }

    /* Comment Box */
    .comment-box {
        display: none;
        margin-top: 20px;
        width: 100%;
    }

    textarea {
        width: 100%;
        padding: 12px;
        border-radius: var(--border-radius-md);
        border: 1px solid var(--border-light, #E0E0E0);
        font-family: 'Inter', sans-serif;
        resize: vertical;
        min-height: 100px;
        font-size: 0.95em;
        box-sizing: border-box;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    textarea:focus {
        border-color: var(--accent-orange);
        box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
        outline: none;
    }

    .send-remark {
        background-color: #007bff;
        color: white;
        align-self: flex-end;
    }

    .send-remark:hover {
        background-color: #0056b3;
    }

    /* No Events Message */
    .no-events-message {
        text-align: center;
        padding: 30px;
        background-color: var(--card-bg);
        border: 1px dashed rgba(0, 0, 0, 0.1);
        border-radius: var(--border-radius-md);
        color: var(--text-medium);
        font-size: 1em;
        margin: 50px auto;
        box-shadow: var(--shadow-sm);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .event {
            padding: 20px;
        }

        .event button {
            width: 100%;
            margin-bottom: 10px;
        }

        .event form {
            flex-direction: column;
            gap: 10px;
        }

        .send-remark {
            width: 100%;
        }
    }
</style>

<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($event = $result->fetch_assoc()): ?>
        <div class="event">
            <h3><?= htmlspecialchars($event['Titre']) ?></h3>
            <p><?= nl2br(htmlspecialchars($event['Description'])) ?></p>
            <p><strong>Adresse :</strong> <?= htmlspecialchars($event['Adresse']) ?></p>
            <p><strong>Du :</strong> <?= $event['DateDebut'] ?> <strong>au</strong> <?= $event['DateFin'] ?></p>

            <form method="POST" action="index.php?page=admin_gerer_evenement">
                <input type="hidden" name="id" value="<?= $event['Id_Evenement'] ?>">
                <button class="accept" name="action" value="approuve"><i class="fas fa-check-circle"></i> Approuver</button>
                <button class="reject" name="action" value="rejete"><i class="fas fa-times-circle"></i> Rejeter</button>
                <button type="button" onclick="toggleComment(this)" class="comment"><i class="fas fa-comment-dots"></i>
                    Envoyer une remarque</button>

                <div class="comment-box">
                    <textarea name="remarque" rows="4" placeholder="Votre remarque à l'organisateur..."></textarea>
                    <button type="submit" name="action" value="remarque" class="send-remark"><i
                            class="fas fa-paper-plane"></i> Envoyer la remarque</button>
                </div>
            </form>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p class="no-events-message">Aucun événement en attente de validation pour le moment. Tout est à jour !</p>
<?php endif; ?>

<script>
    function toggleComment(btn) {
        const box = btn.closest("form").querySelector(".comment-box");
        box.style.display = box.style.display === 'none' ? 'flex' : 'none'; // Changed to 'flex' for better button alignment
    }
</script>
