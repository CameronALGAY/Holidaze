<?php
// Définir l'en-tête JSON en premier pour éviter les problèmes de "headers already sent"
header('Content-Type: application/json');

// Démarrage de session sécurisé (seulement si pas déjà démarrée)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction utilitaire pour retourner une réponse JSON et arrêter l'exécution
function sendJsonResponse($success, $message = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Sécurité : seul un admin peut accéder à ce script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    sendJsonResponse(false, 'Accès refusé', 403);
}

// Inclusion de la base de données (chemin correct depuis ajax/)
require_once '../include/db.php';

// Vérification de la méthode de requête et de la présence des données
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_message']) && isset($_POST['action'])) {
    
    $id_message = (int)$_POST['id_message'];
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'mark_read':
                $stmt = $pdo->prepare("UPDATE messages_contact SET lu = 1 WHERE id_message = ?");
                $stmt->execute([$id_message]);
                
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(true);
                } else {
                    sendJsonResponse(false, 'Message déjà lu ou introuvable');
                }
                break;

            case 'delete':
                // Soft delete: marquer comme supprimé
                $stmt = $pdo->prepare("UPDATE messages_contact SET supprime = 1 WHERE id_message = ?");
                $stmt->execute([$id_message]);
                
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(true, 'Message supprimé avec succès.');
                } else {
                    sendJsonResponse(false, 'Message introuvable.');
                }
                break;

            case 'reply':
                if (!isset($_POST['reponse_admin']) || empty(trim($_POST['reponse_admin']))) {
                    sendJsonResponse(false, 'Le contenu de la réponse est vide.', 400);
                }
                
                $reponse_admin = trim($_POST['reponse_admin']);
                
                // Mettre à jour la réponse, la date de réponse, et marquer comme lu
                $stmt = $pdo->prepare("
                    UPDATE messages_contact 
                    SET reponse_admin = ?, date_reponse = NOW(), lu = 1 
                    WHERE id_message = ?
                ");
                $stmt->execute([$reponse_admin, $id_message]);
                
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(true, 'Réponse envoyée avec succès.');
                } else {
                    sendJsonResponse(false, 'Erreur lors de l\'envoi de la réponse.');
                }
                break;

            default:
                sendJsonResponse(false, 'Action non reconnue.', 400);
                break;
        }
    } catch (Exception $e) {
        error_log("Erreur AJAX : " . $e->getMessage());
        sendJsonResponse(false, 'Erreur serveur interne : ' . $e->getMessage(), 500);
    }
} else {
    // Requête invalide (pas de POST, ou paramètres manquants)
    sendJsonResponse(false, 'Requête invalide ou paramètres manquants.', 400);
}
?>
