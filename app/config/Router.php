<?php
namespace App\Config;

class Router {
    /**
     * Routes définies (URL => [controller, action])
     * @var array
     */
    private $routes = [];
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Initialisation des routes
    }
    
    /**
     * Ajoute une route
     * 
     * @param string $url URL de la route
     * @param string $controller Nom du contrôleur
     * @param string $action Nom de l'action
     * @return $this Pour chaînage
     */
    public function addRoute($url, $controller, $action) {
        $this->routes[$url] = [
            'controller' => $controller,
            'action' => $action
        ];
        
        return $this;
    }
    
    /**
     * Exécute la route correspondant à l'URL demandée
     * 
     * @param string $url URL demandée
     * @param array $params Paramètres supplémentaires
     * @return mixed Résultat de l'action du contrôleur
     * @throws \Exception Si la route n'existe pas
     */
    public function dispatch($url, array $params = []) {
        if (!isset($this->routes[$url])) {
            throw new \Exception("Route non trouvée: $url");
        }
        
        $route = $this->routes[$url];
        $controllerName = $route['controller'];
        $actionName = $route['action'];
        
        // Préfixer le nom du contrôleur avec le namespace
        $controllerClass = "\\App\\Controllers\\$controllerName";
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Contrôleur non trouvé: $controllerClass");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $actionName)) {
            throw new \Exception("Action non trouvée: $actionName dans $controllerClass");
        }
        
        // Appeler l'action du contrôleur avec les paramètres
        return call_user_func_array([$controller, $actionName], $params);
    }
}