<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../Communes/communes_traitement.php';

$controller = new CommunesController($pdo);
$response = ['success' => false, 'message' => ''];

try {
    // Gestion des requêtes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':
                $communes = $controller->getAllCommunes();
                $response = ['success' => true, 'data' => $communes];
                break;

            case 'getById':
                $id = $_GET['id'] ?? 0;
                $commune = $controller->getCommuneById($id);
                $response = [
                    'success' => $commune !== false,
                    'data' => $commune,
                    'message' => $commune ? '' : 'Commune non trouvée'
                ];
                break;

            case 'getByCodePostal':
                $cp = $_GET['cp'] ?? '';
                $communes = $controller->getByCodePostal($cp);
                $response = ['success' => true, 'data' => $communes];
                break;

            case 'getBiens':
                $id = $_GET['id'] ?? 0;
                $biens = $controller->getBiensByCommune($id);
                $response = ['success' => true, 'data' => $biens];
                break;

            case 'search':
                $search = $_GET['search'] ?? '';
                $communes = $controller->searchCommunes($search);
                $response = ['success' => true, 'data' => $communes];
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
                $nom_commune = $_POST['nom_commune'] ?? '';
                $cp_commune = $_POST['cp_commune'] ?? '';
                $gps_commune = $_POST['gps_commune'] ?? null;
                
                if (empty($nom_commune) || empty($cp_commune)) {
                    $response['message'] = 'Le nom et le code postal sont obligatoires';
                    break;
                }

                // Validation code postal
                if (!preg_match('/^\d{5}$/', $cp_commune)) {
                    $response['message'] = 'Le code postal doit contenir 5 chiffres';
                    break;
                }

                $result = $controller->createCommune($nom_commune, $cp_commune, $gps_commune);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Commune créée avec succès' : 'Erreur lors de la création'
                ];
                break;

            case 'update':
                $id = $_POST['id_commune'] ?? 0;
                $nom_commune = $_POST['nom_commune'] ?? '';
                $cp_commune = $_POST['cp_commune'] ?? '';
                $gps_commune = $_POST['gps_commune'] ?? null;

                if (empty($nom_commune) || empty($cp_commune)) {
                    $response['message'] = 'Le nom et le code postal sont obligatoires';
                    break;
                }

                // Validation code postal
                if (!preg_match('/^\d{5}$/', $cp_commune)) {
                    $response['message'] = 'Le code postal doit contenir 5 chiffres';
                    break;
                }

                $result = $controller->updateCommune($id, $nom_commune, $cp_commune, $gps_commune);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Commune modifiée avec succès' : 'Erreur lors de la modification'
                ];
                break;

            case 'delete':
                $id = $_POST['id_commune'] ?? 0;
                
                // Vérifier si des biens sont liés
                $biens = $controller->getBiensByCommune($id);
                if (count($biens) > 0) {
                    $response['message'] = 'Impossible de supprimer: des biens sont rattachés à cette commune';
                    break;
                }

                $result = $controller->deleteCommune($id);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Commune supprimée avec succès' : 'Erreur lors de la suppression'
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