<?php
/**
 * Fichier de gestion des notifications
 */

/**
 * Crée une nouvelle notification pour un utilisateur
 * 
 * @param int $userId ID de l'utilisateur destinataire
 * @param string $message Message de la notification
 * @param string $type Type de notification (ticket_validated, ticket_rejected, etc.)
 * @param int|null $referenceId ID de référence (ID du ticket, de l'événement, etc.)
 * @param string|null $additionalData Données supplémentaires au format JSON
 * @return bool Succès de l'opération
 */
function createNotification($userId, $message, $type, $referenceId = null, $additionalData = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO notifications (Id_Utilisateur, Message, Type, Id_Reference, DonneesSupplementaires)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("issss", $userId, $message, $type, $referenceId, $additionalData);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Récupère les notifications d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @param bool $unreadOnly Ne récupérer que les notifications non lues
 * @param int $limit Nombre maximum de notifications à récupérer
 * @return array Tableau de notifications
 */
function getUserNotifications($userId, $unreadOnly = false, $limit = 10) {
    global $conn;
    
    $query = "
        SELECT * FROM notifications 
        WHERE Id_Utilisateur = ?
    ";
    
    if ($unreadOnly) {
        $query .= " AND Lu = 0";
    }
    
    $query .= " ORDER BY DateCreation DESC LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    
    return $notifications;
}

/**
 * Marque une notification comme lue
 * 
 * @param int $notificationId ID de la notification
 * @param int $userId ID de l'utilisateur (pour vérification)
 * @return bool Succès de l'opération
 */
function markNotificationAsRead($notificationId, $userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET Lu = 1 
        WHERE Id_Notification = ? AND Id_Utilisateur = ?
    ");
    
    $stmt->bind_param("ii", $notificationId, $userId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Marque toutes les notifications d'un utilisateur comme lues
 * 
 * @param int $userId ID de l'utilisateur
 * @return bool Succès de l'opération
 */
function markAllNotificationsAsRead($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET Lu = 1 
        WHERE Id_Utilisateur = ?
    ");
    
    $stmt->bind_param("i", $userId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Compte le nombre de notifications non lues d'un utilisateur
 * 
 * @param int $userId ID de l'utilisateur
 * @return int Nombre de notifications non lues
 */
function countUnreadNotifications($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE Id_Utilisateur = ? AND Lu = 0
    ");
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    return $count;
}