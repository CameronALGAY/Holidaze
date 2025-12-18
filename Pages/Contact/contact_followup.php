<?php
session_start();
header('Content-Type: application/json');

// Sécurité : seul un utilisateur connecté peut envoyer un suivi
if (!isset($_SESSION['utilisateur_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour envoyer un message.']);
    exit;
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

require_once '../../include/db.php';

$utilisateur_id = $_SESSION['utilisateur_id'];
$id_message = isset($_POST['id_message']) ? intval($_POST['id_message']) : 0;
$reponse_utilisateur = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
if ($id_message <= 0) {
    echo json_encode(['success' => false, 'message' => 'Message invalide.']);
    exit;
}

if (strlen($reponse_utilisateur) < 5) {
    echo json_encode(['success' => false, 'message' => 'Le message doit contenir au moins 5 caractères.']);
    exit;
}

if (strlen($reponse_utilisateur) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Le message est trop long (maximum 5000 caractères).']);
    exit;
}

try {
    // Vérifier que le message appartient bien à l'utilisateur et qu'il y a une réponse admin
    $stmt = $pdo->prepare("
        SELECT id_message, reponse_admin 
        FROM messages_contact 
        WHERE id_message = ? AND id_utilisateur = ?
    ");
    $stmt->execute([$id_message, $utilisateur_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message introuvable ou non autorisé.']);
        exit;
    }
    
    if (empty($message['reponse_admin'])) {
        echo json_encode(['success' => false, 'message' => 'L\'administrateur n\'a pas encore répondu à ce message.']);
        exit;
    }
    
    // Mettre à jour le message avec la réponse de l'utilisateur
    $stmt = $pdo->prepare("
        UPDATE messages_contact 
        SET reponse_utilisateur = ?, 
            date_reponse_utilisateur = NOW(),
            lu = 0
        WHERE id_message = ?
    ");
    
    $stmt->execute([$reponse_utilisateur, $id_message]);
    
    echo json_encode(['success' => true, 'message' => 'Votre réponse a été envoyée avec succès.']);
    
} catch (PDOException $e) {
    error_log("Erreur lors de l'envoi de la réponse : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi de la réponse. Veuillez réessayer.']);
}