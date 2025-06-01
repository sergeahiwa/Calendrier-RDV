<?php
// ================================
// Fichier : admin/auth.php
// Rôle    : Protection des pages d'administration
// Auteur  : SAN Digital Solutions
// ================================

// Démarrage de la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour vérifier si l'utilisateur est connecté
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Vérification de l'authentification
if (!is_admin_logged_in()) {
    // Redirection vers la page de connexion
    header('Location: login.php');
    exit;
}

// Vérification de l'expiration de session (30 minutes)
$session_timeout = 30 * 60; // 30 minutes en secondes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expirée, déconnexion
    session_unset();
    session_destroy();
    
    // Redirection avec message
    header('Location: login.php?session_expired=1');
    exit;
}

// Mise à jour du timestamp de dernière activité
$_SESSION['last_activity'] = time();
