<?php
namespace App\Controllers;

use App\Models\UserModel;

class UserController extends Controller {
    /**
     * @var UserModel
     */
    protected $userModel;
    
    /**
     * Constructeur
     */
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    /**
     * Affiche la liste des utilisateurs avec recherche
     * 
     * @return string Contenu HTML
     */
    public function index() {
        // Vérifier les droits d'accès (admin uniquement)
        $this->hasRole('admin', true);
        
        // Récupérer le terme de recherche
        $search = $this->getQueryParam('recherche', '');
        
        // Récupérer les utilisateurs
        $users = $this->userModel->searchUsers($search);
        
        // Passer les données à la vue
        $this->addViewData('users', $users);
        $this->addViewData('search', $search);
        
        // Rendre la vue
        return $this->render('admin/users/index');
    }
    
    /**
     * Affiche le formulaire de modification d'un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @return string Contenu HTML
     */
    public function edit($id = null) {
        // Vérifier les droits d'accès (admin uniquement)
        $this->hasRole('admin', true);
        
        // Si l'ID n'est pas fourni, le récupérer depuis la requête
        if ($id === null) {
            $id = (int)$this->getQueryParam('id');
        }
        
        // Récupérer l'utilisateur
        $user = $this->userModel->getById($id);
        
        // Vérifier que l'utilisateur existe
        if (!$user) {
            $this->setErrorMessage('Utilisateur non trouvé.');
            $this->redirect('admin/index.php?page=gerer_utilisateur');
        }
        
        // Passer les données à la vue
        $this->addViewData('user', $user);
        
        // Rendre la vue
        return $this->render('admin/users/edit');
    }
    
    /**
     * Traite la mise à jour d'un utilisateur
     * 
     * @return void
     */
    public function update() {
        // Vérifier les droits d'accès (admin uniquement)
        $this->hasRole('admin', true);
        
        // Vérifier que la requête est de type POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setErrorMessage('Méthode non autorisée.');
            $this->redirect('admin/index.php?page=gerer_utilisateur');
        }
        
        // Récupérer l'ID de l'utilisateur
        $id = (int)$this->getPostParam('id');
        
        // Récupérer les données du formulaire
        $nom = $this->getPostParam('nom');
        $prenom = $this->getPostParam('prenom');
        $email = $this->getPostParam('email');
        $telephone = $this->getPostParam('telephone');
        $type = $this->getPostParam('type');
        $motDePasse = $this->getPostParam('motdepasse');
        
        // Validation des données
        $errors = [];
        
        if (empty($nom)) {
            $errors[] = 'Le nom est requis.';
        }
        
        if (empty($prenom)) {
            $errors[] = 'Le prénom est requis.';
        }
        
        if (empty($email)) {
            $errors[] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email n\'est pas valide.';
        } elseif ($this->userModel->emailExists($email, $id)) {
            $errors[] = 'Cet email est déjà utilisé par un autre utilisateur.';
        }
        
        if (!empty($telephone) && !preg_match('/^[0-9]{10}$/', $telephone)) {
            $errors[] = 'Le numéro de téléphone doit contenir 10 chiffres.';
        }
        
        if (empty($type)) {
            $errors[] = 'Le type d\'utilisateur est requis.';
        }
        
        // Si des erreurs sont détectées, rediriger vers le formulaire
        if (!empty($errors)) {
            $this->setFormErrors($errors);
            $this->redirect("admin/index.php?page=modifier_utilisateur&id=$id");
        }
        
        // Préparer les données à mettre à jour
        $userData = [
            'Nom' => $nom,
            'Prenom' => $prenom,
            'Email' => $email,
            'Telephone' => $telephone,
            'Type_utilisateur' => $type
        ];
        
        // Ajouter le mot de passe s'il est fourni
        if (!empty($motDePasse)) {
            $userData['MotDePasse'] = $motDePasse;
        }
        
        // Mettre à jour l'utilisateur
        $success = $this->userModel->updateUser($id, $userData);
        
        if ($success) {
            $this->setSuccessMessage('Utilisateur mis à jour avec succès.');
        } else {
            $this->setErrorMessage('Une erreur est survenue lors de la mise à jour de l\'utilisateur.');
        }
        
        $this->redirect('admin/index.php?page=gerer_utilisateur');
    }
    
    /**
     * Affiche le formulaire de création d'un utilisateur
     * 
     * @return string Contenu HTML
     */
    public function create() {
        // Vérifier les droits d'accès (admin uniquement)
        $this->hasRole('admin', true);
        
        // Rendre la vue
        return $this->render('admin/users/create');
    }
    
    /**
     * Traite la création d'un utilisateur
     * 
     * @return void
     */
    public function store() {
        // Vérifier les droits d'accès (admin uniquement)
        $this->hasRole('admin', true);
        
        // Vérifier que la requête est de type POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setErrorMessage('Méthode non autorisée.');
            $this->redirect('admin/index.php?page=gerer_utilisateur');
        }
        
        // Récupérer les données du formulaire
        $nom = $this->getPostParam('nom');
        $prenom = $this->getPostParam('prenom');
        $email = $this->getPostParam('email');
        $telephone = $this->getPostParam('telephone');
        $type = $this->getPostParam('type');
        $motDePasse = $this->getPostParam('motdepasse');
        
        // Validation des données
        $errors = [];
        
        if (empty($nom)) {
            $errors[] = 'Le nom est requis.';
        }
        
        if (empty($prenom)) {
            $errors[] = 'Le prénom est requis.';
        }
        
        if (empty($email)) {
            $errors[] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email n\'est pas valide.';
        } elseif ($this->userModel->emailExists($email)) {
            $errors[] = 'Cet email est déjà utilisé.';
        }
        
        if (!empty($telephone) && !preg_match('/^[0-9]{10}$/', $telephone)) {
            $errors[] = 'Le numéro de téléphone doit contenir 10 chiffres.';
        }
        
        if (empty($type)) {
            $errors[] = 'Le type d\'utilisateur est requis.';
        }
        
        if (empty($motDePasse)) {
            $errors[] = 'Le mot de passe est requis.';
        } elseif (strlen($motDePasse) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        
        // Si des erreurs sont détectées, rediriger vers le formulaire
        if (!empty($errors)) {
            $this->setFormErrors($errors);
            $this->redirect('admin/index.php?page=ajouter_utilisateur');
        }
        
        // Préparer les données à insérer
        $userData = [
            'Nom' => $nom,
            'Prenom' => $prenom,
            'Email' => $email,
            'Telephone' => $telephone,
            'Type_utilisateur' => $type,
            'MotDePasse' => $motDePasse
        ];
        
        // Créer l'utilisateur
        $userId = $this->userModel->createUser($userData);
        
        if ($userId) {
            $this->setSuccessMessage('Utilisateur créé avec succès.');
        } else {
            $this->setErrorMessage('Une erreur est survenue lors de la création de l\'utilisateur.');
        }
        
        $this->redirect('admin/index.php?page=gerer_utilisateur');
    }
    
    /**
     * Traite la suppression d'un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @return void
     */
    public function delete($id = null) {
        // Vérifier les droits d'accès (admin uniquement)
        $this->hasRole('admin', true);
        
        // Si l'ID n'est pas fourni, le récupérer depuis la requête
        if ($id === null) {
            $id = (int)$this->getQueryParam('id');
        }
        
        // Vérifier que l'utilisateur existe
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->setErrorMessage('Utilisateur non trouvé.');
            $this->redirect('admin/index.php?page=gerer_utilisateur');
        }
        
        // Supprimer l'utilisateur
        $success = $this->userModel->delete($id);
        
        if ($success) {
            $this->setSuccessMessage('Utilisateur supprimé avec succès.');
        } else {
            $this->setErrorMessage('Une erreur est survenue lors de la suppression de l\'utilisateur.');
        }
        
        $this->redirect('admin/index.php?page=gerer_utilisateur');
    }
}