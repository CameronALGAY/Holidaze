<?php
// Définir l'en-tête JSON en premier
header('Content-Type: application/json');

// Démarrage de session sécurisé
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction utilitaire pour retourner une réponse JSON
function sendJsonResponse($success, $message = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Vérification : seul un utilisateur connecté peut répondre
if (!isset($_SESSION['utilisateur_id'])) {
    sendJsonResponse(false, 'Vous devez être connecté.', 403);
}

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Méthode non autorisée.', 405);
}

// Vérification des données
if (!isset($_POST['id_conversation']) || !isset($_POST['message'])) {
    sendJsonResponse(false, 'Données manquantes.', 400);
}

$id_conversation = (int)$_POST['id_conversation'];
$contenu = trim($_POST['message']);

// Validation du contenu
if (empty($contenu) || strlen($contenu) < 5) {
    sendJsonResponse(false, 'Le message doit contenir au moins 5 caractères.', 400);
}

// Inclusion de la base de données
require_once '../../include/db.php';

try {
    // Vérifier que la conversation appartient à l'utilisateur
    $stmt_check = $pdo->prepare("
        SELECT id_conversation FROM conversations 
        WHERE id_conversation = ? AND id_utilisateur = ? AND supprime = 0
    ");
    $stmt_check->execute([$id_conversation, $_SESSION['utilisateur_id']]);
    
    if (!$stmt_check->fetch()) {
        sendJsonResponse(false, 'Conversation non trouvée ou accès refusé.', 403);
    }
    
    // Insérer le nouveau message
    $stmt_insert = $pdo->prepare("
        INSERT INTO messages (id_conversation, role_expediteur, contenu, date_envoi) 
        VALUES (?, 'utilisateur', ?, NOW())
    ");
    $stmt_insert->execute([$id_conversation, $contenu]);
    
    // Mettre à jour le statut de la conversation
    $stmt_update = $pdo->prepare("
        UPDATE conversations SET statut = 'en attente admin' 
        WHERE id_conversation = ?
    ");
    $stmt_update->execute([$id_conversation]);
    
    sendJsonResponse(true, 'Votre réponse a été envoyée avec succès.');
    
} catch (PDOException $e) {
    error_log('Erreur contact_reply.php: ' . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de l\'envoi du message.', 500);
} catch (Exception $e) {
    error_log('Erreur contact_reply.php: ' . $e->getMessage());
    sendJsonResponse(false, 'Erreur lors de l\'envoi du message.', 500);
}
?>
