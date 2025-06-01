<?php
// ================================
// Fichier : admin/logout.php
// Rôle    : Déconnexion de l'administration
// Auteur  : SAN Digital Solutions
// ================================

// Démarrage de la session
session_start();

// Destruction de toutes les variables de session
$_SESSION = array();

// Destruction du cookie de session si existant
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destruction de la session
session_destroy();

// Redirection vers la page de connexion
header('Location: login.php?logout=1');
exit;
?>
