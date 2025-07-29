<?php
namespace App\Controllers;

abstract class Controller {
    /**
     * Données à passer à la vue
     * @var array
     */
    protected $viewData = [];
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Initialisation commune à tous les contrôleurs
    }
    
    /**
     * Ajoute des données à passer à la vue
     * 
     * @param string $key Clé
     * @param mixed $value Valeur
     * @return $this Pour chaînage
     */
    protected function addViewData($key, $value) {
        $this->viewData[$key] = $value;
        return $this;
    }
    
    /**
     * Récupère toutes les données à passer à la vue
     * 
     * @return array Données pour la vue
     */
    protected function getViewData() {
        return $this->viewData;
    }
    
    /**
     * Rend une vue avec les données
     * 
     * @param string $view Chemin de la vue (relatif au dossier views)
     * @param array $data Données supplémentaires à passer à la vue
     * @return string Contenu HTML de la vue
     */
    protected function render($view, array $data = []) {
        // Fusionner les données supplémentaires avec les données de la vue
        $viewData = array_merge($this->viewData, $data);
        
        // Extraire les variables pour les rendre disponibles dans la vue
        extract($viewData);
        
        // Démarrer la mise en tampon de sortie
        ob_start();
        
        // Inclure la vue
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            throw new \Exception("Vue non trouvée: $viewPath");
        }
        
        // Récupérer le contenu de la vue et nettoyer la mémoire tampon
        return ob_get_clean();
    }
    
    /**
     * Redirige vers une URL
     * 
     * @param string $url URL de redirection
     * @return void
     */
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    /**
     * Définit un message de succès en session
     * 
     * @param string $message Message de succès
     * @return $this Pour chaînage
     */
    protected function setSuccessMessage($message) {
        $_SESSION['success_message'] = $message;
        return $this;
    }
    
    /**
     * Définit un message d'erreur en session
     * 
     * @param string $message Message d'erreur
     * @return $this Pour chaînage
     */
    protected function setErrorMessage($message) {
        $_SESSION['error_message'] = $message;
        return $this;
    }
    
    /**
     * Définit des erreurs de formulaire en session
     * 
     * @param array $errors Tableau d'erreurs
     * @return $this Pour chaînage
     */
    protected function setFormErrors(array $errors) {
        $_SESSION['form_errors'] = $errors;
        return $this;
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     * 
     * @param bool $redirect Rediriger vers la page de connexion si non connecté
     * @return bool True si connecté, false sinon
     */
    protected function isAuthenticated($redirect = false) {
        $isAuth = isset($_SESSION['utilisateur']) && !empty($_SESSION['utilisateur']['id']);
        
        if (!$isAuth && $redirect) {
            $this->setErrorMessage('Vous devez être connecté pour accéder à cette page.');
            $this->redirect('authentification.php');
        }
        
        return $isAuth;
    }
    
    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     * 
     * @param string $role Rôle requis
     * @param bool $redirect Rediriger si l'utilisateur n'a pas le rôle
     * @return bool True si l'utilisateur a le rôle, false sinon
     */
    protected function hasRole($role, $redirect = false) {
        $hasRole = $this->isAuthenticated() && 
                  isset($_SESSION['utilisateur']['type']) && 
                  $_SESSION['utilisateur']['type'] === $role;
        
        if (!$hasRole && $redirect) {
            $this->setErrorMessage('Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
            $this->redirect('index.php');
        }
        
        return $hasRole;
    }
    
    /**
     * Récupère et nettoie une valeur de $_POST
     * 
     * @param string $key Clé dans $_POST
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur nettoyée
     */
    protected function getPostParam($key, $default = null) {
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }
    
    /**
     * Récupère et nettoie une valeur de $_GET
     * 
     * @param string $key Clé dans $_GET
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur nettoyée
     */
    protected function getQueryParam($key, $default = null) {
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }
    
    /**
     * Nettoie une valeur
     * 
     * @param mixed $value Valeur à nettoyer
     * @return mixed Valeur nettoyée
     */
    protected function sanitize($value) {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}