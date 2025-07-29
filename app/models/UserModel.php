<?php
namespace App\Models;

class UserModel extends Model {
    protected $table = 'utilisateur';
    protected $primaryKey = 'Id_Utilisateur';
    
    /**
     * Recherche des utilisateurs par nom ou email
     * 
     * @param string $search Terme de recherche
     * @return array Liste des utilisateurs correspondants
     */
    public function searchUsers($search) {
        if (empty($search)) {
            return $this->getAll();
        }
        
        return $this->search([
            'Nom' => $search,
            'Email' => $search
        ]);
    }
    
    /**
     * Récupère tous les utilisateurs avec pagination
     * 
     * @param int $page Numéro de page
     * @param int $perPage Nombre d'éléments par page
     * @return array Liste des utilisateurs pour la page demandée
     */
    public function getAllPaginated($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT Id_Utilisateur, Nom, Prenom, Email, Telephone, Type_utilisateur 
                FROM {$this->table} 
                ORDER BY Id_Utilisateur 
                LIMIT ?, ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $offset, $perPage);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    /**
     * Compte le nombre total d'utilisateurs
     * 
     * @return int Nombre total d'utilisateurs
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc()['count'];
        }
        
        return 0;
    }
    
    /**
     * Vérifie si un email existe déjà (sauf pour l'utilisateur spécifié)
     * 
     * @param string $email Email à vérifier
     * @param int $excludeId ID de l'utilisateur à exclure (optionnel)
     * @return bool True si l'email existe déjà
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE Email = ?";
        $params = [$email];
        $types = 's';
        
        if ($excludeId !== null) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
            $types .= 'i';
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc()['count'] > 0;
        }
        
        return false;
    }
    
    /**
     * Crée un nouvel utilisateur
     * 
     * @param array $data Données de l'utilisateur
     * @return int|bool ID de l'utilisateur créé ou false en cas d'échec
     */
    public function createUser(array $data) {
        // Hasher le mot de passe si présent
        if (isset($data['MotDePasse']) && !empty($data['MotDePasse'])) {
            $data['MotDePasse'] = password_hash($data['MotDePasse'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }
    
    /**
     * Met à jour un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public function updateUser($id, array $data) {
        // Hasher le mot de passe si présent et non vide
        if (isset($data['MotDePasse']) && !empty($data['MotDePasse'])) {
            $data['MotDePasse'] = password_hash($data['MotDePasse'], PASSWORD_DEFAULT);
        } elseif (isset($data['MotDePasse']) && empty($data['MotDePasse'])) {
            // Si le mot de passe est vide, ne pas le mettre à jour
            unset($data['MotDePasse']);
        }
        
        return $this->update($id, $data);
    }
}