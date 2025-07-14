<?php
session_start();
require_once 'base.php';

// Vérification que l'utilisateur connecté est un administrateur.
if ($_SESSION['utilisateurs']['id'] !== -1) {
    header("Location: connexion.php");
    exit("Accès refusé.");
}