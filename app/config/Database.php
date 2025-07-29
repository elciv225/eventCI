<?php
namespace App\Config;

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        // Paramètres de connexion à la base de données
        $servername = "localhost";
        $username = "elciv";
        $password = "elciv";
        $port = 3306;
        $dbname = "gestiondebillet";
        
        // Créer une connexion sans spécifier la base de données
        $this->conn = new \mysqli($servername, $username, $password);
        
        // Vérifier la connexion
        if ($this->conn->connect_error) {
            die("Échec de la connexion au serveur MySQL : " . $this->conn->connect_error);
        }
        
        // Vérifier si la base de données existe
        $result = $this->conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
        
        if ($result->num_rows == 0) {
            // La base de données n'existe pas, on la crée
            if ($this->conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci") === TRUE) {
                echo "Base de données '$dbname' créée avec succès.<br>";
            } else {
                die("Erreur lors de la création de la base de données : " . $this->conn->error);
            }
        }
        
        // Sélectionner la base de données
        $this->conn->select_db($dbname);
        
        // Définir le jeu de caractères pour la connexion (important pour les accents)
        $this->conn->set_charset("utf8mb4");
        
        // Vérifier si les tables existent déjà
        $result = $this->conn->query("SHOW TABLES");
        if ($result->num_rows == 0) {
            // Aucune table n'existe, on initialise la base de données avec le script SQL
            $sql_file = file_get_contents(__DIR__ . '/../../config/gestiondebillet.sql');
            
            if ($sql_file) {
                // Exécuter le script SQL
                if ($this->conn->multi_query($sql_file)) {
                    echo "Base de données initialisée avec succès.<br>";
                    // Vider les résultats pour permettre d'autres requêtes
                    do {
                        if ($result = $this->conn->store_result()) {
                            $result->free();
                        }
                    } while ($this->conn->more_results() && $this->conn->next_result());
                } else {
                    echo "Erreur lors de l'initialisation de la base de données : " . $this->conn->error;
                }
            } else {
                echo "Fichier SQL d'initialisation non trouvé.";
            }
        }
    }
    
    // Singleton pattern pour s'assurer qu'une seule instance de connexion existe
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Obtenir la connexion
    public function getConnection() {
        return $this->conn;
    }
    
    // Fermer la connexion
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
    
    // Empêcher le clonage de l'objet
    private function __clone() {}
    
    // Empêcher la désérialisation de l'objet
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}