<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../Prestations/prestation_traitement.php';

$controller = new PrestationController($pdo);
$response = ['success' => false, 'message' => ''];

try {
    // Gestion des requêtes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':
                $prestations = $controller->getAllPrestations();
                $response = ['success' => true, 'data' => $prestations];
                break;

            case 'getById':
                $id = $_GET['id'] ?? 0;
                $prestation = $controller->getPrestationById($id);
                $response = [
                    'success' => $prestation !== false,
                    'data' => $prestation,
                    'message' => $prestation ? '' : 'Prestation non trouvée'
                ];
                break;

            case 'getByLibelle':
                $idBien = $_GET['idBien'] ?? 0;
                $prestations = $controller->getByLibelle($idBien);
                $response = ['success' => true, 'data' => $prestations];
                break;

            case 'getByBien':
                $idBien = $_GET['idBien'] ?? 0;
                $prestations = $controller->getByLibelle($idBien);
                $response = ['success' => true, 'data' => $prestations];
                break;

            case 'search':
                $search = $_GET['search'] ?? '';
                $prestations = $controller->searchPrestations($search);
                $response = ['success' => true, 'data' => $prestations];
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
                $libelle_prestation = $_POST['libelle_prestation'] ?? '';
                
                if (empty($libelle_prestation)) {
                    $response['message'] = 'Le libellé est obligatoire';
                    break;
                }

                // Vérifier si la prestation existe déjà
                $existing = $controller->getByLibelle($libelle_prestation);
                if ($existing) {
                    $response['message'] = 'Cette prestation existe déjà';
                    break;
                }

                $result = $controller->create($libelle_prestation);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Prestation créée avec succès' : 'Erreur lors de la création'
                ];
                break;

            case 'update':
                $id = $_POST['id'] ?? 0;
                $libelle_prestation = $_POST['libelle_prestation'] ?? '';

                if (empty($libelle_prestation)) {
                    $response['message'] = 'Le libellé est obligatoire';
                    break;
                }

                $result = $controller->update($id, $libelle_prestation);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Prestation modifiée avec succès' : 'Erreur lors de la modification'
                ];
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                $result = $controller->delete($id);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Prestation supprimée avec succès' : 'Erreur lors de la suppression'
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