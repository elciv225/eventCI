<?php
// Ajout de catégorie
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajouter"])) {
    $libelle = $_POST["libelle"] ?? '';
    if (!empty($libelle)) {
        $stmt = $conn->prepare("INSERT INTO categorieevenement (Libelle) VALUES (?)");
        $stmt->bind_param("s", $libelle);
        $stmt->execute();
    }
}

// Récupération des catégories
$resultat = $conn->query("SELECT * FROM categorieevenement ORDER BY Libelle ASC");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Catégories Événement</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        h2 { text-align: center; color: #007bff; }
        form { margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; }
        input[type="text"] { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 15px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { width: 100%; background: #fff; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .actions a { margin-right: 10px; color: #007bff; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>📁 Gestion des Catégories d'Événement</h2>
    <div style="text-align: center; margin-bottom: 20px;">
    <a href="menu_admin.php">
        <button style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; cursor: pointer;">
            ⬅️ Retour au menu admin
        </button>
    </a>
</div>

    <form method="POST">
        <label><strong>➕ Ajouter une catégorie :</strong></label><br>
        <input type="text" name="libelle" placeholder="Nom de la catégorie" required>
        <button type="submit" name="ajouter">Ajouter</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Libellé</th>
            <th>Actions</th>
        </tr>
        <?php while ($cat = $resultat->fetch_assoc()) : ?>
        <tr>
            <td><?= $cat["Id_CategorieEvenement"] ?></td>
            <td><?= htmlspecialchars($cat["Libelle"]) ?></td>
            <td class="actions">
                <a href="modifier_categorie_evenement.php?id=<?= $cat["Id_CategorieEvenement"] ?>">✏️ Modifier</a>
                <a href="supprimer_categorie_evenement.php?id=<?= $cat["Id_CategorieEvenement"] ?>" onclick="return confirm('Supprimer cette catégorie ?')">🗑️ Supprimer</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>