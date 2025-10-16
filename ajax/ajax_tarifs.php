<?php
header('Content-Type: application/json');
require_once '../../include/db.php';
require_once 'tarifs_class.php';
require_once 'tarifs_traitement.php';

// Vérifier que la requête est bien AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Requête non autorisée']);
    exit;
}

$controller = new TarifsController($pdo);

// --- Gestion des requêtes GET ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'getAll':
                $tarifs = $controller->getAllTarifs();
                echo json_encode(['success' => true, 'data' => $tarifs]);
                break;

            case 'getById':
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    echo json_encode(['success' => false, 'message' => 'ID invalide']);
                    break;
                }
                $tarif = $controller->getTarifById($id);
                echo json_encode([
                    'success' => $tarif !== false,
                    'data' => $tarif,
                    'message' => $tarif ? 'Tarif trouvé' : 'Tarif introuvable'
                ]);
                break;

            case 'getByBien':
                $idBien = filter_input(INPUT_GET, 'idBien', FILTER_VALIDATE_INT);
                if (!$idBien) {
                    echo json_encode(['success' => false, 'message' => 'ID bien invalide']);
                    break;
                }
                $tarifs = $controller->getTarifsByBien($idBien);
                echo json_encode(['success' => true, 'data' => $tarifs]);
                break;

            case 'getBySaison':
                $idSaison = filter_input(INPUT_GET, 'idSaison', FILTER_VALIDATE_INT);
                if (!$idSaison) {
                    echo json_encode(['success' => false, 'message' => 'ID saison invalide']);
                    break;
                }
                $tarifs = $controller->getTarifsBySaison($idSaison);
                echo json_encode(['success' => true, 'data' => $tarifs]);
                break;

            case 'search':
                $search = $_GET['search'] ?? '';
                $tarifs = $controller->searchTarifs($search);
                echo json_encode([
                    'success' => true,
                    'data' => $tarifs,
                    'count' => count($tarifs)
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
}

// --- Gestion des requêtes POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                // Validation des données
                $semaine = filter_input(INPUT_POST, 'semaine_tarif', FILTER_VALIDATE_INT);
                $annee = filter_input(INPUT_POST, 'annee_tarif', FILTER_VALIDATE_INT);
                $tarif = filter_input(INPUT_POST, 'tarif', FILTER_VALIDATE_FLOAT);
                $idBien = filter_input(INPUT_POST, 'idBien', FILTER_VALIDATE_INT);
                $idSaison = filter_input(INPUT_POST, 'id_saison', FILTER_VALIDATE_INT);

                if (!$semaine || !$annee || !$tarif || !$idBien || !$idSaison) {
                    echo json_encode(['success' => false, 'message' => 'Données invalides']);
                    break;
                }

                if ($semaine < 1 || $semaine > 53) {
                    echo json_encode(['success' => false, 'message' => 'Numéro de semaine invalide (1-53)']);
                    break;
                }

                $result = $controller->createTarif($semaine, $annee, $tarif, $idBien, $idSaison);
                echo json_encode([
                    'success' => $result !== false,
                    'id' => $result,
                    'message' => $result ? 'Tarif créé avec succès' : 'Erreur lors de la création'
                ]);
                break;

            case 'update':
                $id = filter_input(INPUT_POST, 'id_tarif', FILTER_VALIDATE_INT);
                $semaine = filter_input(INPUT_POST, 'semaine_tarif', FILTER_VALIDATE_INT);
                $annee = filter_input(INPUT_POST, 'annee_tarif', FILTER_VALIDATE_INT);
                $tarif = filter_input(INPUT_POST, 'tarif', FILTER_VALIDATE_FLOAT);
                $idBien = filter_input(INPUT_POST, 'idBien', FILTER_VALIDATE_INT);
                $idSaison = filter_input(INPUT_POST, 'id_saison', FILTER_VALIDATE_INT);

                if (!$id || !$semaine || !$annee || !$tarif || !$idBien || !$idSaison) {
                    echo json_encode(['success' => false, 'message' => 'Données invalides']);
                    break;
                }

                if ($semaine < 1 || $semaine > 53) {
                    echo json_encode(['success' => false, 'message' => 'Numéro de semaine invalide (1-53)']);
                    break;
                }

                $result = $controller->updateTarif($id, $semaine, $annee, $tarif, $idBien, $idSaison);
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Tarif modifié avec succès' : 'Erreur lors de la modification'
                ]);
                break;

            case 'delete':
                $id = filter_input(INPUT_POST, 'id_tarif', FILTER_VALIDATE_INT);
                if (!$id) {
                    echo json_encode(['success' => false, 'message' => 'ID invalide']);
                    break;
                }

                $result = $controller->deleteTarif($id);
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Tarif supprimé avec succès' : 'Erreur lors de la suppression'
                ]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
}
?>