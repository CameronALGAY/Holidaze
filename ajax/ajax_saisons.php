<?php
/**
 * AJAX Saisons — Holidaze
 * CORRECTIONS :
 *  - Chemin corrigé : __DIR__ . '/../include/db.php'  (plus ../../includes/)
 *  - require de saison_traitement.php corrigé (chemin absolu)
 *  - Classe instanciée correctement (SaisonController, pas Saison)
 *  - header JSON dès le début
 */
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);

require_once __DIR__ . '/../include/db.php';                           // ✅ chemin correct
require_once __DIR__ . '/../Pages/Saison/saison_traitement.php';       // ✅ chemin correct

// saison_traitement.php définit SaisonController (class interne)
// On recrée une instance ici
$controller = new SaisonController($pdo);

// --- GET ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'getAll':
                echo json_encode(['success' => true, 'data' => $controller->getAllSaisons()]);
                break;

            case 'getById':
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); break; }
                $saison = $controller->getById($id);
                echo json_encode(['success' => (bool)$saison, 'data' => $saison]);
                break;

            case 'search':
                $search = trim($_GET['search'] ?? '');
                echo json_encode(['success' => true, 'data' => $controller->search($search)]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action GET non reconnue']);
        }
    } catch (Exception $e) {
        error_log('ajax_saisons GET error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
    exit;
}

// --- POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                $libelle = trim($_POST['libelle_saison'] ?? '');
                if ($libelle === '') {
                    echo json_encode(['success' => false, 'message' => 'Le libellé est obligatoire']);
                    break;
                }
                echo json_encode(['success' => $controller->create($libelle), 'message' => 'Saison créée']);
                break;

            case 'update':
                $id      = filter_input(INPUT_POST, 'id_saison', FILTER_VALIDATE_INT);
                $libelle = trim($_POST['libelle_saison'] ?? '');
                if (!$id || $libelle === '') {
                    echo json_encode(['success' => false, 'message' => 'Données invalides']);
                    break;
                }
                echo json_encode(['success' => $controller->update($id, $libelle), 'message' => 'Saison modifiée']);
                break;

            case 'delete':
                $id = filter_input(INPUT_POST, 'id_saison', FILTER_VALIDATE_INT);
                if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); break; }
                echo json_encode(['success' => $controller->delete($id), 'message' => 'Saison supprimée']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action POST non reconnue']);
        }
    } catch (Exception $e) {
        error_log('ajax_saisons POST error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);