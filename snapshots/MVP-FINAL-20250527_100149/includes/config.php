<?php
// ================================
// Fichier : includes/config.php
// Rôle    : Configuration de la base de données et initialisation du logger
// Auteur  : SAN Digital Solutions
// ================================

// Chargement des variables d'environnement
require_once __DIR__ . '/env_loader.php';

// Chargement du fichier .env
try {
    EnvLoader::load(dirname(__DIR__) . '/.env');
} catch (Exception $e) {
    die("Erreur de chargement du fichier .env : " . $e->getMessage());
}

// Inclusion du logger
require_once __DIR__ . '/logger.php';

// Lecture des informations de connexion à la base de données depuis .env
$servername = EnvLoader::get('DB_HOST', 'localhost');
$username   = EnvLoader::get('DB_USER', '');
$password   = EnvLoader::get('DB_PASS', ''); // Maintenant sécurisé dans le fichier .env
$dbname     = EnvLoader::get('DB_NAME', '');

// Initialisation du logger
$logger = Logger::getInstance();

// Connexion PDO avec gestion des erreurs
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $logger->info("Connexion à la base de données réussie");
} catch (PDOException $e) {
    $logger->error("Erreur de connexion à la base de données", ['message' => $e->getMessage()]);
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour nettoyer les entrées utilisateur (sécurité)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

