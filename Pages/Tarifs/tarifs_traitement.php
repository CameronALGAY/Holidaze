<?php
require_once '../../include/db.php';
require_once 'tarifs_class.php';

class TarifsController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // 🔹 READ ALL
    public function getAllTarifs()
    {
        $sql = "SELECT t.*, b.nom_bien AS nomBien, s.libelle_saison 
                FROM tarif t
                LEFT JOIN bien b ON t.id_bien = b.id_bien
                LEFT JOIN saison s ON t.id_saison = s.id_saison
                ORDER BY t.annee_tarif DESC, t.semaine_tarif";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 READ BY ID
    public function getTarifById($id)
    {
        $sql = "SELECT t.*, b.nom_bien AS nomBien, s.libelle_saison 
                FROM tarif t
                LEFT JOIN bien b ON t.id_bien = b.id_bien
                LEFT JOIN saison s ON t.id_saison = s.id_saison
                WHERE t.id_tarif = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 SEARCH
    public function searchTarifs($search)
    {
        $sql = "SELECT t.*, b.nom_bien AS nomBien, s.libelle_saison 
                FROM tarif t
                LEFT JOIN bien b ON t.id_bien = b.id_bien
                LEFT JOIN saison s ON t.id_saison = s.id_saison
                WHERE b.nom_bien LIKE ? OR s.libelle_saison LIKE ?
                ORDER BY t.annee_tarif DESC, t.semaine_tarif";
        $stmt = $this->pdo->prepare($sql);
        $term = '%' . $search . '%';
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 CREATE
    public function createTarif($semaine, $annee, $tarif, $idBien, $id_saison)
    {
        $tarifObj = new Tarifs($this->pdo, null, $semaine, $annee, $tarif, $idBien, $id_saison);
        return $tarifObj->create();
    }

    // 🔹 UPDATE
    public function updateTarif($id, $semaine, $annee, $tarif, $idBien, $id_saison)
    {
        $tarifObj = new Tarifs($this->pdo, $id, $semaine, $annee, $tarif, $idBien, $id_saison);
        return $tarifObj->update();
    }

    // 🔹 DELETE
    public function deleteTarif($id)
    {
        $tarifObj = new Tarifs($this->pdo, $id, null, null, null, null, null);
        return $tarifObj->delete($id);
    }
}

// --- AUTO-COMPLÉTION ---
if (isset($_GET['autocomplete'])) {
    $type = $_GET['autocomplete'];
    $q = trim($_GET['q'] ?? '');

    if ($type === 'bien') {
        $stmt = $pdo->prepare("SELECT id_bien AS idBien, nom_bien AS nomBien FROM bien WHERE nom_bien LIKE ? LIMIT 10");
        $stmt->execute(["%$q%"]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    } elseif ($type === 'saison') {
        $stmt = $pdo->prepare("SELECT id_saison, libelle_saison FROM saison WHERE libelle_saison LIKE ? LIMIT 10");
        $stmt->execute(["%$q%"]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
}

// --- REQUÊTES GET ---
if (isset($_GET['action'])) {
    $controller = new TarifsController($pdo);
    switch ($_GET['action']) {
        case 'getAll':
            echo json_encode(['success' => true, 'data' => $controller->getAllTarifs()]);
            break;
        case 'getById':
            $id = $_GET['id'] ?? 0;
            echo json_encode(['success' => true, 'tarif' => $controller->getTarifById($id)]);
            break;
        case 'search':
            $search = $_GET['search'] ?? '';
            echo json_encode(['success' => true, 'data' => $controller->searchTarifs($search)]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    exit;
}

// --- REQUÊTES POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new TarifsController($pdo);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = $controller->createTarif(
                $_POST['semaine_tarif'],
                $_POST['annee_tarif'],
                $_POST['tarif'],
                $_POST['idBien'],
                $_POST['id_saison']
            );
            echo json_encode(['success' => $result !== false, 'id' => $result]);
            break;

        case 'update':
            $result = $controller->updateTarif(
                $_POST['id_tarif'],
                $_POST['semaine_tarif'],
                $_POST['annee_tarif'],
                $_POST['tarif'],
                $_POST['idBien'],
                $_POST['id_saison']
            );
            echo json_encode(['success' => $result]);
            break;

        case 'delete':
            $result = $controller->deleteTarif($_POST['id_tarif']);
            echo json_encode(['success' => $result]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    exit;
}
?>
