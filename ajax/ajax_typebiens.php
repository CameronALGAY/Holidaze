<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../TypeBien/typebien_traitement.php';

$controller = new TypeBienController($pdo);
$response = ['success' => false, 'message' => ''];

try {
    // Gestion des requêtes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':
                $types = $controller->getAllTypeBien();
                $response = ['success' => true, 'data' => $types];
                break;

            case 'getById':
                $id = $_GET['id'] ?? 0;
                $type = $controller->getTypeBienById($id);
                $response = [
                    'success' => $type !== false,
                    'data' => $type,
                    'message' => $type ? '' : 'Type de bien non trouvé'
                ];
                break;

            case 'search':
                $search = $_GET['search'] ?? '';
                $types = $controller->searchTypeBien($search);
                $response = ['success' => true, 'data' => $types];
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
                $des_typebien = $_POST['des_typebien'] ?? '';
                
                if (empty($des_typebien)) {
                    $response['message'] = 'La description est obligatoire';
                    break;
                }

                // Vérifier si le type existe déjà
                $existing = $controller->getByDescription($des_typebien);
                if ($existing) {
                    $response['message'] = 'Ce type de bien existe déjà';
                    break;
                }

                $result = $controller->createTypeBien($des_typebien);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Type de bien créé avec succès' : 'Erreur lors de la création'
                ];
                break;

            case 'update':
                $id = $_POST['id_typebien'] ?? 0;
                $des_typebien = $_POST['des_typebien'] ?? '';

                if (empty($des_typebien)) {
                    $response['message'] = 'La description est obligatoire';
                    break;
                }

                $result = $controller->updateTypeBien($id, $des_typebien);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Type de bien modifié avec succès' : 'Erreur lors de la modification'
                ];
                break;

            case 'delete':
                $id = $_POST['id_typebien'] ?? 0;
                $result = $controller->deleteTypeBien($id);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Type de bien supprimé avec succès' : 'Erreur lors de la suppression'
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