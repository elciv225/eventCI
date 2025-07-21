<?php
$id = $_GET["id"] ?? null;
if (!$id) {
    die("ID de catégorie manquant.");
}

// Récupérer la catégorie existante
$stmt = $conn->prepare("SELECT Libelle FROM categorieevenement WHERE Id_CategorieEvenement = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$categorie = $result->fetch_assoc();
$stmt->close();

if (!$categorie) {
    die("Catégorie introuvable.");
}

// Mise à jour du libellé
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $libelle = $_POST["libelle"] ?? '';
    if (!empty($libelle)) {
        $update = $conn->prepare("UPDATE categorieevenement SET Libelle = ? WHERE Id_CategorieEvenement = ?");
        $update->bind_param("si", $libelle, $id);
        if ($update->execute()) {
            header("Location: gerer_categorie_evenement.php");
            exit();
        } else {
            echo "❌ Erreur de mise à jour.";
        }
        $update->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Modifier Catégorie</title>
</head>
<body>
    <h2>✏️ Modifier la Catégorie</h2>
    <form method="POST">
        <label>Libellé :</label><br>
        <input type="text" name="libelle" value="<?= htmlspecialchars($categorie['Libelle']) ?>" required><br><br>
        <button type="submit">✅ Enregistrer</button>
    </form>
</body>
</html>