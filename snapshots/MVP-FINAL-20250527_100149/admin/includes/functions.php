<?php
// ================================
// Fichier : admin/includes/functions.php
// Rôle    : Fonctions utilitaires pour l'administration
// Auteur  : SAN Digital Solutions
// ================================

/**
 * Récupère les statistiques des rendez-vous
 * 
 * @param PDO $pdo Instance de PDO
 * @return array Tableau associatif des statistiques
 */
function getRdvStatistics(PDO $pdo): array {
    $stats = [];
    
    // Statistiques générales
    $stats['total_rdv'] = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    $stats['rdv_aujourdhui'] = $pdo->query("
        SELECT COUNT(*) FROM reservations 
        WHERE DATE(date_rdv) = CURDATE() 
        AND statut != 'annulé'
    ")->fetchColumn();
    $stats['rdv_a_venir'] = $pdo->query("
        SELECT COUNT(*) FROM reservations 
        WHERE date_rdv > NOW() 
        AND statut != 'annulé'
    ")->fetchColumn();
    $stats['total_prestataires'] = $pdo->query("SELECT COUNT(*) FROM prestataires")->fetchColumn();
    
    // Statistiques par statut
    $stmt = $pdo->query("
        SELECT statut, COUNT(*) as count 
        FROM reservations 
        GROUP BY statut
    ");
    $stats['par_statut'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Statistiques par mois (6 derniers mois)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date_rdv, '%Y-%m') as mois,
            COUNT(*) as total
        FROM reservations
        WHERE date_rdv >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(date_rdv, '%Y-%m')
        ORDER BY mois ASC
    ");
    $stats['par_mois'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return $stats;
}

/**
 * Récupère la classe CSS correspondant à un statut
 * 
 * @param string $status Statut du rendez-vous
 * @return string Classe CSS
 */
function getStatusClass(string $status): string {
    return match($status) {
        'confirmé' => 'success',
        'en_attente' => 'warning',
        'annulé' => 'danger',
        default => 'secondary'
    };
}

/**
 * Formate une date en français
 * 
 * @param string $date Date au format YYYY-MM-DD
 * @return string Date formatée en français
 */
function formatDate(string $date): string {
    $dateTime = new DateTime($date);
    return $dateTime->format('d/m/Y');
}

/**
 * Formate une heure
 * 
 * @param string $heure Heure au format HH:MM
 * @return string Heure formatée
 */
function formatTime(string $heure): string {
    return $heure;
}

/**
 * Vérifie si un rendez-vous peut être modifié
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $rdvId ID du rendez-vous
 * @return bool Vrai si le rendez-vous peut être modifié
 */
function canModifyRdv(PDO $pdo, int $rdvId): bool {
    $stmt = $pdo->prepare("
        SELECT date_rdv, heure_rdv, statut 
        FROM reservations 
        WHERE id = ?
    ");
    $stmt->execute([$rdvId]);
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rdv) {
        return false;
    }
    
    $rdvDateTime = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
    $now = new DateTime();
    
    // Vérifier si le rendez-vous est dans le passé
    if ($rdvDateTime < $now) {
        return false;
    }
    
    // Vérifier si le statut permet la modification
    return $rdv['statut'] !== 'annulé';
}

/**
 * Vérifie si un rendez-vous peut être supprimé
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $rdvId ID du rendez-vous
 * @return bool Vrai si le rendez-vous peut être supprimé
 */
function canDeleteRdv(PDO $pdo, int $rdvId): bool {
    $stmt = $pdo->prepare("
        SELECT date_rdv, heure_rdv, statut 
        FROM reservations 
        WHERE id = ?
    ");
    $stmt->execute([$rdvId]);
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rdv) {
        return false;
    }
    
    $rdvDateTime = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
    $now = new DateTime();
    
    // Vérifier si le rendez-vous est dans le passé
    if ($rdvDateTime < $now) {
        return false;
    }
    
    // Vérifier si le statut permet la suppression
    return $rdv['statut'] !== 'annulé';
}
