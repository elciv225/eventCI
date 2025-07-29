<h1 class="section-title"><i class="fas fa-user-plus"></i> Ajouter un Utilisateur</h1>

<div class="admin-container">
    <form method="POST" action="admin/traitement_utilisateur.php" class="admin-form">
        <input type="hidden" name="action" value="store">
        
        <div class="form-group">
            <label for="nom">Nom:</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($nom ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="prenom">Prénom:</label>
            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($prenom ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="telephone">Téléphone:</label>
            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($telephone ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="type">Type d'utilisateur:</label>
            <select id="type" name="type" required>
                <option value="utilisateur" <?= ($type ?? '') === 'utilisateur' ? 'selected' : ''; ?>>Utilisateur</option>
                <option value="admin" <?= ($type ?? '') === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="motdepasse">Mot de passe:</label>
            <input type="password" id="motdepasse" name="motdepasse" required>
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