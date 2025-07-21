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

// Ferme la connexion MySQLi à la fin du script
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Utilisateurs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
       :root {
    --primary-orange: #FF8C00; /* Orange vif */
    --primary-gradient: linear-gradient(135deg, #FF8C00 0%, #FF6F00 100%); /* Dégradé d'orange */
    --secondary-light-gray: #F0F0F0; /* Gris très clair pour les fonds secondaires */
    --dark-heading: #333333; /* Gris très foncé pour les titres */
    --light-bg: #FDFDFD; /* Presque blanc pour le fond léger */
    --white-bg: #FFFFFF; /* Blanc pur */
    --border-color: #E6E6E6; /* Gris clair pour les bordures */
    --shadow-light: 0 4px 15px rgba(0,0,0,0.05);
    --shadow-medium: 0 8px 25px rgba(0,0,0,0.08);
    --border-radius-lg: 12px;
    --border-radius-md: 8px;
}

body {
    font-family: 'Inter', sans-serif;
    background-color: var(--light-bg); /* Utilisation du blanc cassé */
    margin: 0;
    padding: 30px;
    color: #333;
    line-height: 1.6;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    overflow-x: hidden;
}

.container {
    width: 100%;
    max-width: 1200px;
    background: var(--white-bg);
    padding: 30px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
    margin-bottom: 30px;
}

h2 {
    text-align: center;
    color: var(--dark-heading);
    margin-bottom: 30px;
    font-size: 2.5em;
    font-weight: 700;
    letter-spacing: 1px;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px; /* Espace entre l'icône et le texte */
}

h2 i {
    color: var(--primary-orange); /* Icône en orange */
    font-size: 0.9em;
}

.search-box {
    margin-bottom: 30px;
    text-align: center;
    background-color: var(--secondary-light-gray); /* Fond gris clair pour la boîte de recherche */
    padding: 20px;
    border-radius: var(--border-radius-md);
    box-shadow: inset 0 1px 5px rgba(0,0,0,0.03);
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.search-box form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
}

input[type="text"] {
    padding: 12px 15px;
    width: 350px;
    max-width: 100%; /* S'assure qu'il ne dépasse pas sur les petits écrans */
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-md);
    font-size: 1.05em;
    box-sizing: border-box;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
input[type="text"]:focus {
    border-color: var(--primary-orange);
    box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
    outline: none;
}

button[type="submit"] {
    padding: 12px 25px;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: var(--border-radius-md);
    font-size: 1.05em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(255, 140, 0, 0.2);
}
button[type="submit"]:hover {
    background: linear-gradient(135deg, #FF6F00 0%, #CC5900 100%); /* Orange plus foncé au survol */
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 6px 15px rgba(255, 140, 0, 0.3);
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background-color: var(--white-bg);
    border-radius: var(--border-radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-light);
}

th, td {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    text-align: left;
}

th {
    background: var(--primary-gradient); /* Dégradé d'orange pour l'en-tête */
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9em;
    letter-spacing: 0.5px;
}
th:first-child { border-top-left-radius: var(--border-radius-md); }
th:last-child { border-top-right-radius: var(--border-radius-md); }

tr:last-child td {
    border-bottom: none;
}

tr:nth-child(even) {
    background-color: var(--secondary-light-gray); /* Couleur alternée gris très clair */
}

tr:hover {
    background-color: rgba(255, 140, 0, 0.05); /* Survol des lignes, orange très clair */
    cursor: pointer;
    transform: scale(1.005); /* Léger zoom au survol */
    transition: all 0.2s ease;
}
tr:hover td {
    box-shadow: 0 2px 8px rgba(0,0,0,0.03); /* Petite ombre sur les cellules au survol */
    position: relative;
    z-index: 1; /* S'assurer que l'ombre est visible */
}


.actions a {
    margin-right: 15px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px; /* Ajout d'un petit padding pour l'esthétique */
    border-radius: 5px;
}
.actions a.modify-btn {
    color: #4CAF50; /* Vert pour modifier (peut être remplacé par de l'orange si souhaité) */
    background-color: rgba(76, 175, 80, 0.1);
}
.actions a.modify-btn:hover {
    color: #fff;
    background-color: #4CAF50;
    transform: translateY(-1px);
}

.actions a.delete-btn {
    color: #FF5722; /* Rouge-orange pour supprimer */
    background-color: rgba(255, 87, 34, 0.1);
}
.actions a.delete-btn:hover {
    color: #fff;
    background-color: #FF5722;
    transform: translateY(-1px);
}

/* Message si aucun utilisateur */
.no-users-message {
    text-align: center;
    padding: 20px;
    background-color: #FFF3E0; /* Orange très clair pour le message */
    border: 1px solid #FFCC80;
    border-radius: var(--border-radius-md);
    color: #E65100; /* Texte orange foncé */
    font-weight: 500;
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 1.1em;
}
.no-users-message i {
    color: #FF9800; /* Icône orange */
    font-size: 1.3em;
}

/* Retour au menu admin */
.back-to-admin {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
    padding: 12px 25px;
    background-color: var(--dark-heading); /* Utilisation du gris foncé pour la cohérence */
    color: white;
    border-radius: var(--border-radius-md);
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.back-to-admin:hover {
    background-color: #555555; /* Gris plus clair au survol */
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

/* Media Queries pour le Responsive Design */
@media (max-width: 900px) {
    body {
        padding: 20px 10px;
    }
    .container {
        padding: 20px 15px;
    }
    h2 {
        font-size: 2em;
    }
    table {
        font-size: 0.9em;
        display: block;
        overflow-x: auto; /* Permet le défilement horizontal sur petits écrans*/
        white-space: nowrap; /* Empêche le texte de se casser sur plusieurs lignes*/
    }
    th, td {
        padding: 12px 10px;
    }
    .search-box input[type="text"] {
        width: calc(100% - 20px);
        margin-bottom: 15px;
    }
    .search-box button {
        width: 100%;
    }
}

@media (max-width: 600px) {
    h2 {
        font-size: 1.8em;
        margin-bottom: 20px;
        flex-direction: column; /* Empile l'icône et le texte du titre */
        gap: 5px;
    }
    .search-box {
        padding: 15px;
    }
    input[type="text"], button[type="submit"] {
        font-size: 1em;
        padding: 10px 15px;
    }
    .actions a {
        margin-right: 8px;
        font-size: 0.85em;
        padding: 4px 6px;
    }
    td.actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-start; /* Alignement à gauche pour les actions empilées */
    }
}
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h2>

        <div class="search-box">
            <form method="GET" action="">
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
                                <a href="modifier_utilisateur.php?id=<?= htmlspecialchars($user['Id_Utilisateur']) ?>" class="modify-btn">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="supprimer_utilisateur.php?id=<?= htmlspecialchars($user['Id_Utilisateur']) ?>" class="delete-btn" onclick="return confirm('Confirmer la suppression de cet utilisateur ? Cette action est irréversible !')">
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

        <a href="menu_admin.php" class="back-to-admin">
            <i class="fas fa-arrow-left"></i> Retour au Menu Admin
        </a>
    </div>
</body>
</html>