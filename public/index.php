<?php
require_once '../config/base.php';

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['utilisateurs'])) {
    header("Location: ../connexion.php");
}

