<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID du ticket manquant.");
}

// Récupération des données du ticket
$stmt = $conn->prepare("SELECT * FROM ticketevenement WHERE Id_TicketEvenement = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();

if (!$ticket) {
    die("Ticket introuvable.");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $prix = $_POST['prix'] ?? 0;
    $disponible = $_POST['nombre_disponible'] ?? 0;
    $vendu = $_POST['nombre_vendu'] ?? 0;
    $id_evenement = $_POST['id_evenement'] ?? null;

    $update = $conn->prepare("UPDATE ticketevenement SET Titre=?, Description=?, Prix=?, NombreDisponible=?, Id_Evenement=? WHERE Id_TicketEvenement=?");
    $update->bind_param("ssdiii", $titre, $description, $prix, $disponible, $id_evenement, $id);
    if ($update->execute()) {
        header("Location: gerer_ticket.php");
        exit();
    } else {
        echo "❌ Erreur lors de la mise à jour.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Modifier Ticket</title>
</head>

<body>
    <h2>📝 Modifier le ticket</h2>
    <form method="POST">
        <label>Titre :</label><br>
        <input type="text" name="titre" value="<?= htmlspecialchars($ticket['Titre']) ?>" required><br><br>

        <label>Description :</label><br>
        <textarea name="description" required><?= htmlspecialchars($ticket['Description']) ?></textarea><br><br>

        <label>Prix :</label><br>
        <input type="number" name="prix" step="0.01" value="<?= $ticket['Prix'] ?>" required><br><br>

        <label>Nombre disponible :</label><br>
        <input type="number" name="nombre_disponible" value="<?= $ticket['NombreDisponible'] ?>" required><br><br>

        <label>ID Événement :</label><br>
        <input type="number" name="id_evenement" value="<?= $ticket['Id_Evenement'] ?>" required><br><br>

        <button type="submit">✅ Enregistrer</button>
    </form>
</body>

</html>