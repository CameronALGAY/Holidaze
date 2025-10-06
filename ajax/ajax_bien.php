<?php
header('Content-Type: application/json');
require_once '../include/db.php';
require_once '../Biens/bien_traitement.php';

$controller = new BiensController($pdo);
$response = ['success' => false, 'message' => ''];

try {
    // Gestion des requêtes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getAll':
                $biens = $controller->getAllBiens();
                $response = ['success' => true, 'data' => $biens];
                break;

            case 'getById':
                $id = $_GET['id'] ?? 0;
                $bien = $controller->getBienById($id);
                $response = [
                    'success' => $bien !== false,
                    'data' => $bien,
                    'message' => $bien ? '' : 'Bien non trouvé'
                ];
                break;

            case 'getByType':
                $id_typebien = $_GET['id_typebien'] ?? 0;
                $biens = $controller->getBiensByType($id_typebien);
                $response = ['success' => true, 'data' => $biens];
                break;

            case 'getByCommune':
                $id_commune = $_GET['id_commune'] ?? 0;
                $biens = $controller->getBiensByCommune($id_commune);
                $response = ['success' => true, 'data' => $biens];
                break;

            case 'search':
                $search = $_GET['search'] ?? '';
                $biens = $controller->searchBiens($search);
                $response = ['success' => true, 'data' => $biens];
                break;

            case 'filter':
                $filters = [
                    'id_typebien' => $_GET['id_typebien'] ?? null,
                    'id_commune' => $_GET['id_commune'] ?? null,
                    'min_superficie' => $_GET['min_superficie'] ?? null,
                    'max_superficie' => $_GET['max_superficie'] ?? null,
                    'animaux' => isset($_GET['animaux']) ? (int)$_GET['animaux'] : null,
                    'min_couchages' => $_GET['min_couchages'] ?? null
                ];
                $biens = $controller->filterBiens($filters);
                $response = ['success' => true, 'data' => $biens];
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
                $nomBien = $_POST['nomBien'] ?? '';
                $descriptionBien = $_POST['descriptionBien'] ?? '';
                $rueBien = $_POST['rueBien'] ?? '';
                $compBien = $_POST['compBien'] ?? '';
                $superficieBien = $_POST['superficieBien'] ?? 0;
                $animauxBien = $_POST['animauxBien'] ?? 0;
                $nbCouchagesBien = $_POST['nbCouchagesBien'] ?? 0;
                $id_commune = $_POST['id_commune'] ?? 0;
                $id_typebien = $_POST['id_typebien'] ?? 0;
                
                // Validations
                if (empty($nomBien)) {
                    $response['message'] = 'Le nom du bien est obligatoire';
                    break;
                }

                if (empty($rueBien)) {
                    $response['message'] = 'L\'adresse est obligatoire';
                    break;
                }

                if ($superficieBien <= 0) {
                    $response['message'] = 'La superficie doit être supérieure à 0';
                    break;
                }

                if ($nbCouchagesBien <= 0) {
                    $response['message'] = 'Le nombre de couchages doit être supérieur à 0';
                    break;
                }

                if ($id_commune <= 0) {
                    $response['message'] = 'Veuillez sélectionner une commune';
                    break;
                }

                if ($id_typebien <= 0) {
                    $response['message'] = 'Veuillez sélectionner un type de bien';
                    break;
                }

                $result = $controller->createBien(
                    $nomBien,
                    $descriptionBien,
                    $rueBien,
                    $compBien,
                    $superficieBien,
                    $animauxBien,
                    $nbCouchagesBien,
                    $id_commune,
                    $id_typebien
                );

                $response = [
                    'success' => $result,
                    'message' => $result ? 'Bien créé avec succès' : 'Erreur lors de la création'
                ];
                break;

            case 'update':
                $idBien = $_POST['idBien'] ?? 0;
                $nomBien = $_POST['nomBien'] ?? '';
                $descriptionBien = $_POST['descriptionBien'] ?? '';
                $rueBien = $_POST['rueBien'] ?? '';
                $compBien = $_POST['compBien'] ?? '';
                $superficieBien = $_POST['superficieBien'] ?? 0;
                $animauxBien = $_POST['animauxBien'] ?? 0;
                $nbCouchagesBien = $_POST['nbCouchagesBien'] ?? 0;
                $id_commune = $_POST['id_commune'] ?? 0;
                $id_typebien = $_POST['id_typebien'] ?? 0;

                // Validations
                if (empty($nomBien)) {
                    $response['message'] = 'Le nom du bien est obligatoire';
                    break;
                }

                if (empty($rueBien)) {
                    $response['message'] = 'L\'adresse est obligatoire';
                    break;
                }

                if ($superficieBien <= 0) {
                    $response['message'] = 'La superficie doit être supérieure à 0';
                    break;
                }

                if ($nbCouchagesBien <= 0) {
                    $response['message'] = 'Le nombre de couchages doit être supérieur à 0';
                    break;
                }

                $result = $controller->updateBien(
                    $idBien,
                    $nomBien,
                    $descriptionBien,
                    $rueBien,
                    $compBien,
                    $superficieBien,
                    $animauxBien,
                    $nbCouchagesBien,
                    $id_commune,
                    $id_typebien
                );

                $response = [
                    'success' => $result,
                    'message' => $result ? 'Bien modifié avec succès' : 'Erreur lors de la modification'
                ];
                break;

            case 'delete':
                $idBien = $_POST['idBien'] ?? 0;
                $result = $controller->deleteBien($idBien);
                $response = [
                    'success' => $result,
                    'message' => $result ? 'Bien supprimé avec succès' : 'Erreur lors de la suppression'
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