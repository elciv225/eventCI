<?php
$servername = "localhost"; // Généralement "localhost"
$username = "root"; // Votre nom d'utilisateur de base de données
$password = ""; // 
$port = 3306; // Port par défaut pour MySQL, peut être omis si c'est le port par défaut
$dbname = "gestiondebillet";
// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données : " . $conn->connect_error);
}

// Définir le jeu de caractères pour la connexion (important pour les accents)
$conn->set_charset("utf8mb4");
?>