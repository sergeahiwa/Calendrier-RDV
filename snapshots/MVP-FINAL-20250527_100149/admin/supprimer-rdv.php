<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Inclusion du fichier de configuration
require_once __DIR__ . '/../includes/config.php';

// Vérification des données du formulaire
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: index.php?error=invalid_id');
    exit();
}

$rdv_id = (int)$_POST['id'];

try {
    // Suppression du rendez-vous
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->execute([$rdv_id]);
    
    // Suppression des logs associés
    $stmt = $pdo->prepare("DELETE FROM logs WHERE reference_id = ? AND type = 'rdv'");
    $stmt->execute([$rdv_id]);
    
    // Création du log de suppression
    $logger->log("Suppression du rendez-vous ID: $rdv_id", 'admin', $_SESSION['admin_id']);
    
    header('Location: index.php?success=rdv_deleted');
    exit();
} catch (PDOException $e) {
    error_log("Erreur lors de la suppression du rendez-vous: " . $e->getMessage());
    header('Location: index.php?error=delete_failed');
}
