<?php
// Activer les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'base.php'; // Connexion BDD

$message = '';
$message_type = '';

// Vérifier si un ID a été passé
if (!isset($_GET['id'])) {
    header('Location: liste_tickets.php?msg=error&text=ID+du+ticket+manquant');
    exit;
}

$id_ticket = intval($_GET['id']);

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $prix = floatval($_POST['prix']);
    $disponible = intval($_POST['nombre_disponible']);
    $evenement_id = intval($_POST['id_evenement']);

    // Validation simple
    if ($titre && $description && $prix >= 0 && $disponible >= 0 && $evenement_id) {
        $stmt = $conn->prepare("UPDATE ticketevenement SET Titre = ?, Description = ?, Prix = ?, NombreDisponible = ?, Id_Evenement = ? WHERE Id_TicketEvenement = ?");
        $stmt->bind_param('ssdiii', $titre, $description, $prix, $disponible, $evenement_id, $id_ticket);
        if ($stmt->execute()) {
            header('Location: liste_tickets.php?msg=success&text=Ticket+modifié+avec+succès');
            exit;
        } else {
            $message = "Erreur lors de la modification.";
            $message_type = "error";
        }
    } else {
        $message = "Veuillez remplir tous les champs correctement.";
        $message_type = "error";
    }
}

// Récupérer les infos actuelles du ticket
$stmt = $conn->prepare("SELECT * FROM ticketevenement WHERE Id_TicketEvenement = ?");
$stmt->bind_param('i', $id_ticket);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: liste_tickets.php?msg=error&text=Ticket+inexistant');
    exit;
}

$ticket = $result->fetch_assoc();

// Récupérer les événements pour le menu déroulant
$evenements = $conn->query("SELECT Id_Evenement, Titre FROM evenement ORDER BY Titre ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Ticket</title>
</head>
<body>
    <h1>Modifier le Ticket</h1>

    <?php if ($message): ?>
        <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="titre">Titre :</label><br>
        <input type="text" name="titre" value="<?= htmlspecialchars($ticket['Titre']) ?>" required><br><br>

        <label for="description">Description :</label><br>
        <textarea name="description" rows="4" required><?= htmlspecialchars($ticket['Description']) ?></textarea><br><br>

        <label for="prix">Prix (€) :</label><br>
        <input type="number" name="prix" step="0.01" value="<?= $ticket['Prix'] ?>" required><br><br>

        <label for="nombre_disponible">Nombre Disponible :</label><br>
        <input type="number" name="nombre_disponible" value="<?= $ticket['NombreDisponible'] ?>" required><br><br>

        <label for="id_evenement">Événement associé :</label><br>
        <select name="id_evenement" required>
            <?php while ($evenement = $evenements->fetch_assoc()): ?>
                <option value="<?= $evenement['Id_Evenement'] ?>" <?= $evenement['Id_Evenement'] == $ticket['Id_Evenement'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($evenement['Titre']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Enregistrer les modifications</button>
        <a href="liste_tickets.php">Annuler</a>
    </form>
</body>
</html>