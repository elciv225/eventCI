<?php
namespace App\Models;

use App\Config\Database;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Récupère tous les enregistrements de la table
     * 
     * @param string $orderBy Champ pour le tri
     * @param string $order Direction du tri (ASC ou DESC)
     * @return array Tableau d'enregistrements
     */
    public function getAll($orderBy = null, $order = 'ASC') {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        
        $result = $this->db->query($sql);
        $items = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        return $items;
    }
    
    /**
     * Récupère un enregistrement par son ID
     * 
     * @param int $id ID de l'enregistrement
     * @return array|null Enregistrement trouvé ou null
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Recherche des enregistrements selon des critères
     * 
     * @param array $criteria Critères de recherche (champ => valeur)
     * @param string $operator Opérateur de comparaison (= par défaut)
     * @return array Enregistrements trouvés
     */
    public function findBy(array $criteria, $operator = '=') {
        $conditions = [];
        $values = [];
        $types = '';
        
        foreach ($criteria as $field => $value) {
            $conditions[] = "{$field} {$operator} ?";
            $values[] = $value;
            
            // Déterminer le type de paramètre
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 's'; // Par défaut
            }
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->db->prepare($sql);
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        return $items;
    }
    
    /**
     * Recherche avec opérateur LIKE
     * 
     * @param array $criteria Critères de recherche (champ => valeur)
     * @return array Enregistrements trouvés
     */
    public function search(array $criteria) {
        $conditions = [];
        $values = [];
        $types = '';
        
        foreach ($criteria as $field => $value) {
            $conditions[] = "{$field} LIKE ?";
            $values[] = "%{$value}%";
            $types .= 's';
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $conditions);
        $stmt = $this->db->prepare($sql);
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        return $items;
    }
    
    /**
     * Crée un nouvel enregistrement
     * 
     * @param array $data Données à insérer
     * @return int|bool ID de l'enregistrement créé ou false en cas d'échec
     */
    public function create(array $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $values = array_values($data);
        $types = '';
        
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 's'; // Par défaut
            }
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        
        return false;
    }
    
    /**
     * Met à jour un enregistrement
     * 
     * @param int $id ID de l'enregistrement
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public function update($id, array $data) {
        $fields = [];
        $values = [];
        $types = '';
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
            
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 's'; // Par défaut
            }
        }
        
        // Ajouter l'ID à la fin des valeurs
        $values[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        
        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Supprime un enregistrement
     * 
     * @param int $id ID de l'enregistrement
     * @return bool Succès ou échec
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Exécute une requête SQL personnalisée
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres pour la requête préparée
     * @param string $types Types des paramètres (i: integer, d: double, s: string, b: blob)
     * @return \mysqli_result|bool Résultat de la requête
     */
    public function query($sql, array $params = [], $types = '') {
        if (empty($params)) {
            return $this->db->query($sql);
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        
        return $stmt->get_result();
    }
}