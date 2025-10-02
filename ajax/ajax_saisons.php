<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../Saisons/saison_traitement.php';

$controller = new Saison($pdo);
$response = ['success' => false, 'message' => ''];

try {
    // Gestion des requêtes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':
                $saisons = $controller->getAllSaisons();
                $response = ['success' => true, 'data' => $saisons];
                break;

            case 'getById':
                $id = $_GET['id'] ?? 0;
                $saison = $controller->getSaisonById($id);
                $response = [
                    'success' => $saison !== false,
                    'data' => $saison,
                    'message' => $saison ? '' : 'Saison non trouvée'
                ];
                break;

            case 'search':
                $search = $_GET['search'] ?? '';
                $saisons = $controller->searchSaisons($search);
                $response = ['success' => true, 'data' => $saisons];
                break;

            default:
                $response['message'] = 'Action GET non reconnue';
        }
    }

    // Gestion des requêtes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create':
                $libelle_saison = $_POST['libelle_saison'] ?? '';
                
                if (empty($libelle_saison)) {
                    $response['message'] = 'Le libellé est obligatoire';
                    break;
                }

                // Vérifier si la saison existe déjà
                $existing = $controller->getByLibelle($libelle_saison);
                if ($existing) {
                    $response['message'] = 'Cette saison existe déjà';
                    break;
                }

                $result = $controller->createSaison($libelle_saison);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Saison créée avec succès' : 'Erreur lors de la création'
                ];
                break;

            case 'update':
                $id = $_POST['id_saison'] ?? 0;
                $libelle_saison = $_POST['libelle_saison'] ?? '';

                if (empty($libelle_saison)) {
                    $response['message'] = 'Le libellé est obligatoire';
                    break;
                }

                $result = $controller->updateSaison($id, $libelle_saison);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Saison modifiée avec succès' : 'Erreur lors de la modification'
                ];
                break;

            case 'delete':
                $id = $_POST['id_saison'] ?? 0;
                $result = $controller->deleteSaison($id);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Saison supprimée avec succès' : 'Erreur lors de la suppression'
                ];
                break;

            default:
                $response['message'] = 'Action POST non reconnue';
        }
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>