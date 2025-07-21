 <?php
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID utilisateur manquant.");
}

// Récupération des données actuelles
$stmt = $conn->prepare("SELECT Nom, Prenom, Email, Telephone, Type_utilisateur FROM utilisateur WHERE Id_Utilisateur = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Mise à jour des données
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $type = $_POST['type_utilisateur'] ?? '';

    $update = $conn->prepare("UPDATE utilisateur SET Nom=?, Prenom=?, Email=?, Telephone=?, Type_utilisateur=? WHERE Id_Utilisateur=?");
    $update->bind_param("sssssi", $nom, $prenom, $email, $telephone, $type, $id);

    if ($update->execute()) {
        header("Location: gerer_utilisateur.php");
        exit();
    } else {
        echo "Erreur de mise à jour.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Modifier Utilisateur</title></head>
<body>
    <h2>📝 Modifier l'utilisateur</h2>
    <form method="POST">
        <label>Nom :</label><input type="text" name="nom" value="<?= htmlspecialchars($user['Nom']) ?>" required><br><br>
        <label>Prénom :</label><input type="text" name="prenom" value="<?= htmlspecialchars($user['Prenom']) ?>" required><br><br>
        <label>Email :</label><input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required><br><br>
        <label>Téléphone :</label><input type="text" name="telephone" value="<?= htmlspecialchars($user['Telephone']) ?>" required><br><br>
        <label>Type :</label>
        <select name="type_utilisateur" required>
            <option value="client" <?= $user['Type_utilisateur'] === 'client' ? 'selected' : '' ?>>Client</option>
            <option value="admin" <?= $user['Type_utilisateur'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select><br><br>
        <button type="submit">✅ Enregistrer</button>
    </form>
</body>
</html>