<?php
// Inclure le gestionnaire d'erreurs personnalisé
require_once 'error_handler.php';

// Paramètres de connexion à la base de données
$servername = "localhost";
$username = "elciv";
$password = "elciv";
$port = 3306;
$dbname = "gestiondebillet";

// Créer une connexion sans spécifier la base de données
$conn = new mysqli($servername, $username, $password);

// Vérifier la connexion
if ($conn->connect_error) {
    custom_die("Échec de la connexion au serveur MySQL : " . $conn->connect_error);
}

// Vérifier si la base de données existe
$result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");

if ($result->num_rows == 0) {
    // La base de données n'existe pas, on la crée
    if ($conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci") === TRUE) {
        echo "Base de données '$dbname' créée avec succès.<br>";
    } else {
        custom_die("Erreur lors de la création de la base de données : " . $conn->error);
    }
}

// Sélectionner la base de données
$conn->select_db($dbname);

// Définir le jeu de caractères pour la connexion (important pour les accents)
$conn->set_charset("utf8mb4");

// Vérifier si les tables existent déjà
$result = $conn->query("SHOW TABLES");
if ($result->num_rows == 0) {
    // Aucune table n'existe, on initialise la base de données avec le script SQL
    $sql_file = file_get_contents(__DIR__ . '/gestiondebillet.sql');

    if ($sql_file) {
        // Exécuter le script SQL
        if ($conn->multi_query($sql_file)) {
            echo "Base de données initialisée avec succès.<br>";
            // Vider les résultats pour permettre d'autres requêtes
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            display_warning("Erreur lors de l'initialisation de la base de données : " . $conn->error);
        }
    } else {
        display_warning("Fichier SQL d'initialisation non trouvé.");
    }
}
?>
