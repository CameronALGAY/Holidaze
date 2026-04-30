<?php
/**
 * AJAX Favoris — Holidaze
 * CORRECTIONS :
 *  - Clé session corrigée : $_SESSION['utilisateur_id']  (plus $_SESSION['id_user'])
 *  - header JSON dès le début
 *  - Codes HTTP corrects (403, 400…)
 */
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Clé corrigée : utilisateur_id (cohérent avec connexion.php et tout le reste du projet)
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../Pages/Favoris/favoris_class.php';

$idUser  = (int)$_SESSION['utilisateur_id'];   // ✅ cast sécurisé
$idBien  = filter_input(INPUT_POST, 'id_bien', FILTER_VALIDATE_INT);
$action  = trim($_POST['action'] ?? '');

if (!$idBien || $idBien <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID bien invalide']);
    exit;
}

$favorisController = new FavorisController($pdo);

try {
    switch ($action) {
        case 'ajouter':
            $ok = $favorisController->ajouterFavori($idUser, $idBien);
            echo json_encode([
                'success'     => $ok,
                'estEnFavori' => true,
                'message'     => $ok ? 'Ajouté aux favoris' : 'Erreur lors de l\'ajout',
            ]);
            break;

        case 'retirer':
            $ok = $favorisController->retirerFavori($idUser, $idBien);
            echo json_encode([
                'success'     => $ok,
                'estEnFavori' => false,
                'message'     => $ok ? 'Retiré des favoris' : 'Erreur lors du retrait',
            ]);
            break;

        case 'toggle':
            $estEnFavori = $favorisController->estEnFavori($idUser, $idBien);
            if ($estEnFavori) {
                $ok          = $favorisController->retirerFavori($idUser, $idBien);
                $estEnFavori = false;
                $message     = 'Retiré des favoris';
            } else {
                $ok          = $favorisController->ajouterFavori($idUser, $idBien);
                $estEnFavori = true;
                $message     = 'Ajouté aux favoris';
            }
            echo json_encode(['success' => $ok, 'estEnFavori' => $estEnFavori, 'message' => $message]);
            break;

        case 'verifier':
            $estEnFavori = $favorisController->estEnFavori($idUser, $idBien);
            echo json_encode(['success' => true, 'estEnFavori' => $estEnFavori]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action invalide']);
    }
} catch (Exception $e) {
    error_log('ajax_favoris error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}