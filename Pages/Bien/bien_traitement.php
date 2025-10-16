<?php
require_once '../../include/db.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';

header('Content-Type: application/json');

// Vérification de la connexion
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base']);
    exit;
}

// ✅ AJOUT POUR L'AUTOCOMPLÉTION
if (isset($_GET['autocomplete']) && $_GET['autocomplete'] === 'bien') {
    $term = $_GET['term'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT idBien, nomBien FROM bien WHERE nomBien LIKE ? LIMIT 10");
        $stmt->execute(['%' . $term . '%']);
        $biens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($biens as $b) {
            $results[] = [
                'id' => $b['idBien'],
                'label' => $b['nomBien'],
                'value' => $b['nomBien']
            ];
        }

        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $e->getMessage()]);
    }
    exit;
}
// ✅ FIN DE L'AJOUT


// Instanciation du contrôleur
$controller = new BiensController($pdo);

// --- Gestion des requêtes GET pour l'API ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'getAll':
            echo json_encode(['success' => true, 'data' => $controller->getAllBiens()]);
            exit;
        case 'getById':
            $id = $_GET['id'] ?? 0;
            $bien = $controller->getBienById($id);
            echo json_encode(['success' => $bien !== false, 'bien' => $bien]);
            exit;
        case 'search':
            $search = $_GET['search'] ?? '';
            echo json_encode(['success' => true, 'data' => $controller->searchBiens($search)]);
            exit;
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            exit;
    }
}

// --- Gestion des requêtes POST pour CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Récupération sécurisée des champs POST
    $nom = $_POST['nomBien'] ?? null;
    $description = $_POST['descriptionBien'] ?? null;
    $rue = $_POST['rueBien'] ?? null;
    $com = $_POST['compBien'] ?? '';
    $superficie = $_POST['superficieBien'] ?? null;
    $animaux = $_POST['animauxBien'] ?? 0;
    $nbCouchages = $_POST['nbCouchagesBien'] ?? null;
    $id_commune = $_POST['communeIdInput'] ?? null;
    $id_typebien = $_POST['typebienIdInput'] ?? null;
    $id_bien = $_POST['editId'] ?? $_POST['id_bien'] ?? null;

    switch ($action) {
        case 'create':
            $result = $controller->createBien($nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien);
            header('Location: bien_form.php?success=' . ($result ? '1' : '0'));
            exit;
        case 'update':
            if ($id_bien) {
                $result = $controller->updateBien($id_bien, $nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien);
                header('Location: bien_form.php?success=' . ($result ? '1' : '0'));
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'ID manquant pour la mise à jour']);
            exit;
        case 'delete':
            if ($id_bien) {
                $result = $controller->deleteBien($id_bien);
                echo json_encode(['success' => $result]);
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'ID manquant pour la suppression']);
            exit;
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            exit;
    }

    if(isset($_GET['action']) && $_GET['action']==='getByBien'){
    $idBien = $_GET['idBien'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM tarifs WHERE idBien=? ORDER BY annee_tarif DESC, semaine_tarif");
    $stmt->execute([$idBien]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

}
?>
