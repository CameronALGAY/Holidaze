<?php
session_start();
header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Récupérer les données
$id_bien = isset($_POST['id_bien']) ? (int)$_POST['id_bien'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$id_bien || !in_array($action, ['ajouter', 'retirer'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

require_once dirname(__DIR__) . '/include/db.php';
require_once __DIR__ . '/Favoris/favoris_class.php';

try {
    $favorisController = new FavorisController($pdo);
    $userId = $_SESSION['utilisateur_id'];
    
    if ($action === 'ajouter') {
        // Ajouter aux favoris
        if ($favorisController->ajouterFavori($userId, $id_bien)) {
            $estEnFavori = true;
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Ajouté aux favoris !',
                'estEnFavori' => $estEnFavori
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout aux favoris.']);
        }
    } else {
        // Retirer des favoris
        if ($favorisController->retirerFavori($userId, $id_bien)) {
            $estEnFavori = false;
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Retiré des favoris !',
                'estEnFavori' => $estEnFavori
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors du retrait des favoris.']);
        }
    }
} catch (Exception $e) {
    error_log('Erreur favoris_action.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
?>
