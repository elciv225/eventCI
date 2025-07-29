<h1 class="section-title"><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h1>

<div class="admin-container">
    <div class="search-box">
        <form method="GET" action="">
            <input type="hidden" name="page" value="gerer_utilisateur">
            <input type="text" name="recherche" placeholder="Rechercher par nom ou email" value="<?= htmlspecialchars($search); ?>">
            <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
        </form>
    </div>

    <?php if (!empty($users)) : ?>
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
                <?php foreach ($users as $user) : ?>
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
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p class="no-users-message">
            <i class="fas fa-info-circle"></i> Aucun utilisateur trouvé pour votre recherche.
        </p>
    <?php endif; ?>
    
    <div class="admin-actions">
        <a href="?page=ajouter_utilisateur" class="add-btn">
            <i class="fas fa-user-plus"></i> Ajouter un utilisateur
        </a>
    </div>
</div>