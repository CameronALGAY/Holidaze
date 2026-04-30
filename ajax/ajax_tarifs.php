<?php
/**
 * AJAX Tarifs — Holidaze
 * CORRECTIONS :
 *  - Chemin corrigé : ../../include/db.php  (plus ../../includes/)
 *  - header JSON dès le début pour éviter HTML parasite
 *  - Suppression de la vérification HTTP_X_REQUESTED_WITH (bloque fetch() natif)
 */
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);   // jamais de HTML en réponse AJAX

require_once __DIR__ . '/../include/db.php';             // ✅ chemin correct
require_once __DIR__ . '/../Pages/Tarifs/tarifs_class.php';
require_once __DIR__ . '/../Pages/Tarifs/tarifs_traitement.php';

$controller = new TarifsController($pdo);

// --- Requêtes GET ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'getAll':
                echo json_encode(['success' => true, 'data' => $controller->getAllTarifs()]);
                break;

            case 'getById':
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); break; }
                $tarif = $controller->getTarifById($id);
                echo json_encode(['success' => (bool)$tarif, 'data' => $tarif]);
                break;

            case 'getByBien':
                $idBien = filter_input(INPUT_GET, 'idBien', FILTER_VALIDATE_INT);
                if (!$idBien) { echo json_encode(['success' => false, 'message' => 'ID bien invalide']); break; }
                // Réutilise searchTarifsByBien du controller de réservation ou une requête directe
                $stmt = $pdo->prepare("
                    SELECT t.*, b.nom_bien AS nomBien, s.libelle_saison
                    FROM tarif t
                    LEFT JOIN bien b ON t.id_bien = b.id_bien
                    LEFT JOIN saison s ON t.id_saison = s.id_saison
                    WHERE t.id_bien = ?
                    ORDER BY t.annee_tarif DESC, t.semaine_tarif ASC
                ");
                $stmt->execute([$idBien]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
                break;

            case 'search':
                $search = $_GET['search'] ?? '';
                echo json_encode(['success' => true, 'data' => $controller->searchTarifs($search)]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
    } catch (Exception $e) {
        error_log('ajax_tarifs GET error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
    exit;
}

// --- Requêtes POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                $semaine  = filter_input(INPUT_POST, 'semaine_tarif', FILTER_VALIDATE_INT);
                $annee    = filter_input(INPUT_POST, 'annee_tarif',   FILTER_VALIDATE_INT);
                $tarif    = filter_input(INPUT_POST, 'tarif',         FILTER_VALIDATE_FLOAT);
                $idBien   = filter_input(INPUT_POST, 'idBien',        FILTER_VALIDATE_INT);
                $idSaison = filter_input(INPUT_POST, 'id_saison',     FILTER_VALIDATE_INT);

                if (!$semaine || !$annee || $tarif === false || !$idBien || !$idSaison) {
                    echo json_encode(['success' => false, 'message' => 'Données invalides ou manquantes']);
                    break;
                }
                if ($semaine < 1 || $semaine > 53) {
                    echo json_encode(['success' => false, 'message' => 'Semaine invalide (1–53)']);
                    break;
                }
                $result = $controller->createTarif($semaine, $annee, $tarif, $idBien, $idSaison);
                echo json_encode(['success' => $result !== false, 'message' => $result ? 'Tarif créé' : 'Erreur création']);
                break;

            case 'update':
                $id       = filter_input(INPUT_POST, 'id_tarif',      FILTER_VALIDATE_INT);
                $semaine  = filter_input(INPUT_POST, 'semaine_tarif',  FILTER_VALIDATE_INT);
                $annee    = filter_input(INPUT_POST, 'annee_tarif',    FILTER_VALIDATE_INT);
                $tarif    = filter_input(INPUT_POST, 'tarif',          FILTER_VALIDATE_FLOAT);
                $idBien   = filter_input(INPUT_POST, 'idBien',         FILTER_VALIDATE_INT);
                $idSaison = filter_input(INPUT_POST, 'id_saison',      FILTER_VALIDATE_INT);

                if (!$id || !$semaine || !$annee || $tarif === false || !$idBien || !$idSaison) {
                    echo json_encode(['success' => false, 'message' => 'Données invalides ou manquantes']);
                    break;
                }
                $result = $controller->updateTarif($id, $semaine, $annee, $tarif, $idBien, $idSaison);
                echo json_encode(['success' => $result, 'message' => $result ? 'Tarif modifié' : 'Erreur modification']);
                break;

            case 'delete':
                $id = filter_input(INPUT_POST, 'id_tarif', FILTER_VALIDATE_INT);
                if (!$id) { echo json_encode(['success' => false, 'message' => 'ID invalide']); break; }
                $result = $controller->deleteTarif($id);
                echo json_encode(['success' => $result, 'message' => $result ? 'Tarif supprimé' : 'Erreur suppression']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
    } catch (Exception $e) {
        error_log('ajax_tarifs POST error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);