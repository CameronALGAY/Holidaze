<?php
// Définir l'en-tête JSON en premier pour éviter les problèmes de "headers already sent"
header('Content-Type: application/json');

// Démarrage de session sécurisé (seulement si pas déjà démarrée)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction utilitaire pour retourner une réponse JSON et arrêter l'exécution
function sendJsonResponse($success, $message = null, $httpCode = 200 ) {
    http_response_code($httpCode );
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Sécurité : seul un admin peut accéder à ce script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    sendJsonResponse(false, 'Accès refusé', 403);
}

// Inclusion de la base de données (chemin correct depuis ajax/)
require_once '../include/db.php';
require_once '../include/csrf.php';

// Vérification de la méthode de requête et de la présence des données
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_message']) && isset($_POST['action'])) {
    csrf_verify();
    
    // id_message correspond maintenant à id_conversation
    $id_message = (int)$_POST['id_message'];
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'mark_read':
                // Met à jour le statut 'lu' dans la table conversations
                $stmt = $pdo->prepare("UPDATE conversations SET lu = 1 WHERE id_conversation = ?");
                $stmt->execute([$id_message]);
                
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(true);
                } else {
                    sendJsonResponse(false, 'Conversation déjà lue ou introuvable');
                }
                break;

            case 'delete':
                // Soft delete: marquer la conversation comme supprimée
                $stmt = $pdo->prepare("UPDATE conversations SET supprime = 1 WHERE id_conversation = ?");
                $stmt->execute([$id_message]);
                
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(true, 'Conversation supprimée avec succès.');
                } else {
                    sendJsonResponse(false, 'Conversation introuvable.');
                }
                break;

            case 'continue_reply':
                if (!isset($_POST['reponse_admin']) || empty(trim($_POST['reponse_admin']))) {
                    sendJsonResponse(false, 'Le contenu de la réponse est vide.', 400);
                }
                
                $contenu = trim($_POST['reponse_admin']);
                
                // 1. Insérer le nouveau message dans la table messages
                $stmt_insert = $pdo->prepare("
                    INSERT INTO messages (id_conversation, role_expediteur, contenu, date_envoi)
                    VALUES (?, 'admin', ?, NOW())
                ");
                $stmt_insert->execute([$id_message, $contenu]);
                
                // 2. Mettre à jour le statut 'lu' et 'statut' dans la table conversations
                $stmt_update_conv = $pdo->prepare("
                    UPDATE conversations 
                    SET lu = 1, statut = 'en attente utilisateur' 
                    WHERE id_conversation = ?
                ");
                $stmt_update_conv->execute([$id_message]);
                
                if ($stmt_insert->rowCount() > 0) {
                    sendJsonResponse(true, 'Réponse envoyée avec succès. La conversation est maintenant en attente de la réponse de l\'utilisateur.');
                } else {
                    sendJsonResponse(false, 'Erreur lors de l\'envoi de la réponse.');
                }
                break;

            case 'reply':
                // L'ancienne action 'reply' est conservée pour la compatibilité, mais devrait être remplacée par 'continue_reply'
                sendJsonResponse(false, 'Action "reply" obsolète. Veuillez utiliser "continue_reply".', 400);
                break;

            case 'close_conversation':
                // Logique pour fermer la conversation
                $stmt = $pdo->prepare("UPDATE conversations SET statut = 'fermé' WHERE id_conversation = ?");
                $stmt->execute([$id_message]);
                
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(true, 'Conversation fermée avec succès.');
                } else {
                    sendJsonResponse(false, 'Erreur lors de la fermeture de la conversation.');
                }
                break;

            case 'open_conversation':
                // Logique pour rouvrir la conversation
                $stmt = $pdo->prepare("UPDATE conversations SET statut = 'ouvert' WHERE id_conversation = ?");
                $stmt->execute([$id_message]);
                
                if ($stmt->rowCount() > 0) {
                    sendJsonResponse(true, 'Conversation rouverte avec succès.');
                } else {
                    sendJsonResponse(false, 'Erreur lors de la réouverture de la conversation.');
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
