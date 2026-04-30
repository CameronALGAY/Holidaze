<?php
/**
 * AJAX Communes — Holidaze
 * CORRECTIONS :
 *  - Chemin corrigé : __DIR__ . '/../include/db.php'  (plus ../../includes/)
 *  - require communes_traitement.php avec chemin absolu correct
 *  - header JSON dès le début (zéro HTML en réponse)
 */
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);

require_once __DIR__ . '/../include/db.php';                                   // ✅ chemin correct
require_once __DIR__ . '/../Pages/Communes/communes_traitement.php';           // ✅ chemin correct

$controller = new CommunesController($pdo);
$response   = ['success' => false, 'message' => ''];

try {
    // --- GET ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':
                $response = ['success' => true, 'data' => $controller->getAllCommunes()];
                break;

            case 'getById':
                $id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                $commune = $id ? $controller->getCommuneById($id) : false;
                $response = [
                    'success' => (bool)$commune,
                    'data'    => $commune ?: null,
                    'message' => $commune ? '' : 'Commune non trouvée',
                ];
                break;

            case 'getByCodePostal':
                $cp       = trim($_GET['cp'] ?? '');
                $communes = $cp ? $controller->getByCodePostal($cp) : [];
                $response = ['success' => true, 'data' => $communes];
                break;

            case 'getBiens':
                $id    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                $biens = $id ? $controller->getBiensByCommune($id) : [];
                $response = ['success' => true, 'data' => $biens];
                break;

            case 'search':
                $search   = trim($_GET['search'] ?? '');
                $communes = $search ? $controller->searchCommunes($search) : [];
                $response = ['success' => true, 'data' => $communes];
                break;

            default:
                $response['message'] = 'Action GET non reconnue';
        }
    }

    // --- POST ---
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $nom = trim($_POST['nom_commune'] ?? '');
                $cp  = trim($_POST['cp_commune']  ?? '');
                $gps = $_POST['gps_commune'] ?? null;

                if ($nom === '' || $cp === '') {
                    $response['message'] = 'Le nom et le code postal sont obligatoires';
                    break;
                }
                if (!preg_match('/^\d{5}$/', $cp)) {
                    $response['message'] = 'Le code postal doit contenir 5 chiffres';
                    break;
                }
                $ok = $controller->createCommune($nom, $cp, $gps);
                $response = ['success' => $ok, 'message' => $ok ? 'Commune créée' : 'Erreur création'];
                break;

            case 'update':
                $id  = filter_input(INPUT_POST, 'id_commune', FILTER_VALIDATE_INT);
                $nom = trim($_POST['nom_commune'] ?? '');
                $cp  = trim($_POST['cp_commune']  ?? '');
                $gps = $_POST['gps_commune'] ?? null;

                if (!$id || $nom === '' || $cp === '') {
                    $response['message'] = 'Données invalides ou manquantes';
                    break;
                }
                if (!preg_match('/^\d{5}$/', $cp)) {
                    $response['message'] = 'Le code postal doit contenir 5 chiffres';
                    break;
                }
                $ok = $controller->updateCommune($id, $nom, $cp, $gps);
                $response = ['success' => $ok, 'message' => $ok ? 'Commune modifiée' : 'Erreur modification'];
                break;

            case 'delete':
                $id = filter_input(INPUT_POST, 'id_commune', FILTER_VALIDATE_INT);
                if (!$id) { $response['message'] = 'ID invalide'; break; }

                // Vérifier les biens liés avant suppression
                $biens = $controller->getBiensByCommune($id);
                if (!empty($biens)) {
                    $response['message'] = 'Impossible : des biens sont rattachés à cette commune';
                    break;
                }
                $ok = $controller->deleteCommune($id);
                $response = ['success' => $ok, 'message' => $ok ? 'Commune supprimée' : 'Erreur suppression'];
                break;

            default:
                $response['message'] = 'Action POST non reconnue';
        }
    } else {
        $response['message'] = 'Méthode non autorisée';
    }

} catch (Exception $e) {
    error_log('ajax_commune error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Erreur serveur'];
}

echo json_encode($response);