<?php
// Recherche
$recherche = $_GET['recherche'] ?? '';
$stmt = null; // Initialise $stmt
if ($recherche) {
    $stmt = $conn->prepare("SELECT Id_Utilisateur, Nom, Prenom, Email, Telephone, Type_utilisateur FROM utilisateur WHERE Nom LIKE ? OR Email LIKE ?");
    $like = '%' . $recherche . '%';
    $stmt->bind_param("ss", $like, $like);
} else {
    // Il est bon de spécifier les colonnes que vous voulez, plutôt que d'utiliser SELECT *
    $stmt = $conn->prepare("SELECT Id_Utilisateur, Nom, Prenom, Email, Telephone, Type_utilisateur FROM utilisateur");
}

if ($stmt) { // Vérifie que la préparation a réussi
    $stmt->execute();
    $resultat = $stmt->get_result();
    $stmt->close(); // Ferme le statement après avoir obtenu le résultat
} else {
    // Gérer l'erreur si la préparation échoue
    die("Erreur de préparation de la requête : " . $conn->error);
}
?>

<h1 class="section-title"><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h1>

<div class="admin-container">
    <div class="search-box">
        <form method="GET" action="">
            <input type="hidden" name="page" value="gerer_utilisateur">
            <input type="text" name="recherche" placeholder="Rechercher par nom ou email" value="<?= htmlspecialchars($recherche); ?>">
            <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
        </form>
    </div>

    <?php if ($resultat->num_rows > 0) : ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $resultat->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($user['Id_Utilisateur']) ?></td>
                        <td><?= htmlspecialchars($user['Nom']) ?></td>
                        <td><?= htmlspecialchars($user['Prenom']) ?></td>
                        <td><?= htmlspecialchars($user['Email']) ?></td>
                        <td><?= htmlspecialchars($user['Telephone']) ?></td>
                        <td><?= htmlspecialchars($user['Type_utilisateur']) ?></td>
                        <td class="actions">
                            <a href="?page=modifier_utilisateur&id=<?= htmlspecialchars($user['Id_Utilisateur']) ?>" class="modify-btn">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="?page=supprimer_utilisateur&id=<?= htmlspecialchars($user['Id_Utilisateur']) ?>" class="delete-btn">
                                <i class="fas fa-trash-alt"></i> Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p class="no-users-message">
            <i class="fas fa-info-circle"></i> Aucun utilisateur trouvé pour votre recherche.
        </p>
    <?php endif; ?>
</div>
