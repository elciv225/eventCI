<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID utilisateur manquant.");
}

$stmt = $conn->prepare("DELETE FROM utilisateur WHERE Id_Utilisateur = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: gerer_utilisateur.php");
exit();