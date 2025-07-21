<?php
$id = $_GET["id"] ?? null;
if (!$id) {
    die("ID de catégorie manquant.");
}

// Suppression de la catégorie
$stmt = $conn->prepare("DELETE FROM categorieevenement WHERE Id_CategorieEvenement = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: gerer_categorie_evenement.php");
exit();