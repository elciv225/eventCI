<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Ajouter le répertoire app au chemin d'inclusion
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../');

// Fonction d'autoloading personnalisée
spl_autoload_register(function ($class) {
    // Convertir le namespace en chemin de fichier
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    
    // Vérifier si le fichier existe dans le répertoire app
    $appFile = __DIR__ . '/../' . $file;
    if (file_exists($appFile)) {
        require_once $appFile;
        return true;
    }
    
    return false;
});

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id']) || $_SESSION['utilisateur']['type'] !== 'admin') {
    $_SESSION['error_message'] = 'Vous devez être connecté en tant qu\'administrateur pour effectuer cette action.';
    header('Location: index.php');
    exit;
}

// Récupérer l'action demandée
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Créer une instance du contrôleur utilisateur
$userController = new \App\Controllers\UserController();

// Exécuter l'action appropriée
try {
    switch ($action) {
        case 'store':
            // Créer un nouvel utilisateur
            $userController->store();
            break;
            
        case 'update':
            // Mettre à jour un utilisateur existant
            $userController->update();
            break;
            
        case 'delete':
            // Supprimer un utilisateur
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $userController->delete($id);
            break;
            
        default:
            // Action non reconnue
            $_SESSION['error_message'] = 'Action non reconnue.';
            header('Location: index.php?page=gerer_utilisateur');
            exit;
    }
} catch (Exception $e) {
    // Gérer les erreurs
    $_SESSION['error_message'] = 'Une erreur est survenue: ' . $e->getMessage();
    header('Location: index.php?page=gerer_utilisateur');
    exit;
}