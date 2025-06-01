<?php
// ================================
// Fichier : admin/includes/notifications.php
// Rôle    : Gestion des notifications
// Auteur  : SAN Digital Solutions
// ================================

/**
 * Crée une nouvelle notification
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $adminId ID de l'administrateur
 * @param string $type Type de notification
 * @param string $message Message de la notification
 * @param int|null $rdvId ID du rendez-vous (optionnel)
 * @param int|null $prestataireId ID du prestataire (optionnel)
 * @return bool Vrai si la notification a été créée
 */
function createNotification(PDO $pdo, int $adminId, string $type, string $message, ?int $rdvId = null, ?int $prestataireId = null): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                admin_id, type, message, rdv_id, prestataire_id
            ) VALUES (
                ?, ?, ?, ?, ?
            )
        ");
        
        return $stmt->execute([
            $adminId,
            $type,
            $message,
            $rdvId,
            $prestataireId
        ]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de la notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les notifications non lues
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $adminId ID de l'administrateur
 * @return array Tableau des notifications
 */
function getUnreadNotifications(PDO $pdo, int $adminId): array {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                n.*, 
                r.date_rdv,
                r.heure_rdv,
                r.nom as rdv_nom,
                p.nom as prestataire_nom
            FROM notifications n
            LEFT JOIN reservations r ON n.rdv_id = r.id
            LEFT JOIN prestataires p ON n.prestataire_id = p.id
            WHERE n.admin_id = ? AND n.lue = FALSE
            ORDER BY n.date_creation DESC
            LIMIT 5
        ");
        
        $stmt->execute([$adminId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Marque une notification comme lue
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $notificationId ID de la notification
 * @return bool Vrai si la notification a été marquée comme lue
 */
function markNotificationAsRead(PDO $pdo, int $notificationId): bool {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET lue = TRUE, date_lue = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$notificationId]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour de la notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Crée une notification pour un rendez-vous proche
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $adminId ID de l'administrateur
 * @param array $rdv Informations sur le rendez-vous
 * @return bool Vrai si la notification a été créée
 */
function createUpcomingRdvNotification(PDO $pdo, int $adminId, array $rdv): bool {
    $dateTime = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
    $date = $dateTime->format('d/m/Y H:i');
    
    $message = "Nouveau rendez-vous prévu pour le {$date} avec {$rdv['nom']}";
    
    return createNotification($pdo, $adminId, 'upcoming_rdv', $message, $rdv['id']);
}

/**
 * Crée une notification pour un rendez-vous annulé
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $adminId ID de l'administrateur
 * @param array $rdv Informations sur le rendez-vous
 * @return bool Vrai si la notification a été créée
 */
function createCancelledRdvNotification(PDO $pdo, int $adminId, array $rdv): bool {
    $dateTime = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']);
    $date = $dateTime->format('d/m/Y H:i');
    
    $message = "Le rendez-vous du {$date} avec {$rdv['nom']} a été annulé";
    
    return createNotification($pdo, $adminId, 'cancelled_rdv', $message, $rdv['id']);
}

/**
 * Crée une notification pour un nouveau prestataire
 * 
 * @param PDO $pdo Instance de PDO
 * @param int $adminId ID de l'administrateur
 * @param array $prestataire Informations sur le prestataire
 * @return bool Vrai si la notification a été créée
 */
function createNewProviderNotification(PDO $pdo, int $adminId, array $prestataire): bool {
    $message = "Nouveau prestataire ajouté : {$prestataire['nom']}";
    
    return createNotification($pdo, $adminId, 'new_provider', $message, null, $prestataire['id']);
}
