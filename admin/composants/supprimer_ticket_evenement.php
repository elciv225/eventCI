<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID du ticket manquant.");
}

$stmt = $conn->prepare("DELETE FROM ticketevenement WHERE Id_TicketEvenement = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: gerer_ticket.php");
exit();