<?php
// VERSION DEBUG - remplacer temporairement reservation_traitement.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../include/db.php';
require_once 'reservation_class.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$controller = new ReservationsController($pdo);

// === GESTION DES AUTOCOMPLETIONS ===
if (isset($_GET['autocomplete'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_GET['autocomplete'] === 'locataire') {
            $query = $_GET['q'] ?? '';
            
            // DEBUG
            error_log("=== DEBUG LOCATAIRE ===");
            error_log("Query reçue: '" . $query . "'");
            error_log("Longueur query: " . strlen($query));
            
            // Test direct de la connexion
            $testQuery = $pdo->query("SELECT COUNT(*) as total FROM locataire");
            $total = $testQuery->fetch(PDO::FETCH_ASSOC);
            error_log("Total locataires en BDD: " . $total['total']);
            
            // Test avec query simple
            $testStmt = $pdo->prepare("SELECT * FROM locataire WHERE nom_locataire LIKE :q LIMIT 5");
            $testStmt->execute(['q' => '%' . $query . '%']);
            $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Résultats test direct: " . count($testResults));
            error_log("Test results: " . print_r($testResults, true));
            
            // Appel normal
            $locataires = $controller->searchLocataires($query);
            error_log("Résultats via controller: " . count($locataires));
            error_log("Locataires: " . print_r($locataires, true));
            
            echo json_encode([
                'success' => true, 
                'data' => $locataires,
                'debug' => [
                    'query' => $query,
                    'total_bdd' => $total['total'],
                    'count' => count($locataires)
                ]
            ]);
            exit;
        }
        
        if ($_GET['autocomplete'] === 'bien') {
            $query = $_GET['q'] ?? '';
            $biens = $controller->searchBiens($query);
            echo json_encode(['success' => true, 'data' => $biens]);
            exit;
        }
        
        if ($_GET['autocomplete'] === 'tarif') {
            $id_bien = $_GET['id_bien'] ?? null;
            $query = $_GET['q'] ?? '';
            
            if ($id_bien) {
                $tarifs = $controller->searchTarifsByBien($id_bien, $query);
                echo json_encode(['success' => true, 'data' => $tarifs]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID bien manquant']);
            }
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Type d\'autocomplétion non reconnu']);
        exit;
        
    } catch (Exception $e) {
        error_log("Erreur autocomplétion: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur', 'error' => $e->getMessage()]);
        exit;
    }
}

// === GESTION DES ACTIONS CRUD ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reservation_form.php');
    exit;
}

$action = $_POST['action'] ?? '';

// Récupération des champs
$date_debut = isset($_POST['date_debut']) ? trim($_POST['date_debut']) : null;
$date_fin = isset($_POST['date_fin']) ? trim($_POST['date_fin']) : null;
$id_locataire = isset($_POST['id_locataire']) && $_POST['id_locataire'] !== '' ? (int)$_POST['id_locataire'] : null;
$id_bien = isset($_POST['id_bien']) && $_POST['id_bien'] !== '' ? (int)$_POST['id_bien'] : null;
$id_tarif = isset($_POST['id_tarif']) && $_POST['id_tarif'] !== '' ? (int)$_POST['id_tarif'] : null;
$id_reservation = isset($_POST['id_reservation']) && $_POST['id_reservation'] !== '' ? (int)$_POST['id_reservation'] : null;

try {
    if ($action === 'create') {
        // Validation
        if (!$date_debut || !$date_fin || !$id_locataire || !$id_bien || !$id_tarif) {
            header('Location: reservation_form.php?success=0&error=' . urlencode('Tous les champs sont obligatoires'));
            exit;
        }

        // Vérifier que la date de fin est après la date de début
        if (strtotime($date_fin) < strtotime($date_debut)) {
            header('Location: reservation_form.php?success=0&error=' . urlencode('La date de fin doit être après la date de début'));
            exit;
        }

        // Vérifier les conflits de réservation
        if ($controller->checkConflict($id_bien, $date_debut, $date_fin)) {
            header('Location: reservation_form.php?success=0&error=' . urlencode('Ce bien est déjà réservé pour cette période'));
            exit;
        }

        $newId = $controller->createReservation($date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif);

        if ($newId) {
            header('Location: reservation_form.php?success=1');
        } else {
            header('Location: reservation_form.php?success=0&error=' . urlencode('Erreur lors de la création'));
        }
        exit;

    } elseif ($action === 'update') {
        if (!$id_reservation) {
            header('Location: reservation_form.php?success=0&error=' . urlencode('ID réservation manquant'));
            exit;
        }

        // Validation
        if (!$date_debut || !$date_fin || !$id_locataire || !$id_bien || !$id_tarif) {
            header('Location: reservation_form.php?success=0&error=' . urlencode('Tous les champs sont obligatoires'));
            exit;
        }

        // Vérifier que la date de fin est après la date de début
        if (strtotime($date_fin) < strtotime($date_debut)) {
            header('Location: reservation_form.php?success=0&error=' . urlencode('La date de fin doit être après la date de début'));
            exit;
        }

        // Vérifier les conflits (en excluant la réservation actuelle)
        if ($controller->checkConflict($id_bien, $date_debut, $date_fin, $id_reservation)) {
            header('Location: reservation_form.php?success=0&error=' . urlencode('Ce bien est déjà réservé pour cette période'));
            exit;
        }

        $ok = $controller->updateReservation($id_reservation, $date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif);

        if ($ok) {
            header('Location: reservation_form.php?success=1');
        } else {
            header('Location: reservation_form.php?success=0&error=' . urlencode('Erreur lors de la mise à jour'));
        }
        exit;

    } elseif ($action === 'delete') {
        $id_to_delete = isset($_POST['id_reservation']) ? (int)$_POST['id_reservation'] : null;
        
        if ($id_to_delete) {
            $res = $controller->deleteReservation($id_to_delete);
            echo json_encode(['success' => (bool)$res]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID manquant']);
        }
        exit;

    } else {
        header('Location: reservation_form.php?success=0&error=' . urlencode('Action non reconnue'));
        exit;
    }

} catch (Exception $e) {
    error_log("reservation_traitement.php Exception: " . $e->getMessage());
    header('Location: reservation_form.php?success=0&error=' . urlencode($e->getMessage()));
    exit;
}
?>