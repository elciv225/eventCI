<h1 class="section-title"><i class="fas fa-user-edit"></i> Modifier un Utilisateur</h1>

<div class="admin-container">
    <form method="POST" action="admin/traitement_utilisateur.php" class="admin-form">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= htmlspecialchars($user['Id_Utilisateur']); ?>">
        
        <div class="form-group">
            <label for="nom">Nom:</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['Nom']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="prenom">Prénom:</label>
            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['Prenom']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['Email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="telephone">Téléphone:</label>
            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($user['Telephone']); ?>">
        </div>
        
        <div class="form-group">
            <label for="type">Type d'utilisateur:</label>
            <select id="type" name="type" required>
                <option value="utilisateur" <?= $user['Type_utilisateur'] === 'utilisateur' ? 'selected' : ''; ?>>Utilisateur</option>
                <option value="admin" <?= $user['Type_utilisateur'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="motdepasse">Mot de passe (laisser vide pour ne pas modifier):</label>
            <input type="password" id="motdepasse" name="motdepasse">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <a href="?page=gerer_utilisateur" class="btn-secondary">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>