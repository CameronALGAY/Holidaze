<?php
require_once '../../includes/db.php';
require_once 'Prestations/prestation_class.php';

class PrestationController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer toutes les prestations
    public function getAllPrestations()
    {
        $stmt = $this->pdo->query("SELECT * FROM prestations ORDER BY libelle_prestation");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer une prestation par ID
    public function getPrestationById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prestations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer une prestation par libellé
    public function getByLibelle($libelle)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prestations WHERE libelle_prestation = ?");
        $stmt->execute([$libelle]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer une nouvelle prestation
    public function createPrestation($libelle_prestation)
    {
        $stmt = $this->pdo->prepare("INSERT INTO prestations (libelle_prestation) VALUES (?)");
        return $stmt->execute([$libelle_prestation]);
    }

    // Mettre à jour une prestation
    public function updatePrestation($id, $libelle_prestation)
    {
        $stmt = $this->pdo->prepare("UPDATE prestations SET libelle_prestation = ? WHERE id = ?");
        return $stmt->execute([$libelle_prestation, $id]);
    }

    // Supprimer une prestation
    public function deletePrestation($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM prestations WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Rechercher des prestations
    public function searchPrestations($search)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prestations WHERE libelle_prestation LIKE ? ORDER BY libelle_prestation");
        $stmt->execute(['%' . $search . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les prestations d'un bien spécifique
    public function getPrestationsByBien($idBien)
    {
        $sql = "SELECT p.* FROM prestations p
                INNER JOIN bien_prestation bp ON p.id = bp.id_prestation
                WHERE bp.idBien = ?
                ORDER BY p.libelle_prestation";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idBien]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    $controller = new PrestationController($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            $prestations = $controller->getAllPrestations();
            echo json_encode($prestations);
            break;

        case 'getById':
            $id = $_GET['id'] ?? 0;
            $prestation = $controller->getPrestationById($id);
            echo json_encode([
                'success' => $prestation !== false,
                'prestation' => $prestation
            ]);
            break;

        case 'getByLibelle':
            $libelle = $_GET['libelle'] ?? '';
            $prestation = $controller->getByLibelle($libelle);
            echo json_encode([
                'success' => $prestation !== false,
                'prestation' => $prestation
            ]);
            break;

        case 'getByBien':
            $idBien = $_GET['idBien'] ?? 0;
            $prestations = $controller->getPrestationsByBien($idBien);
            echo json_encode($prestations);
            break;

        case 'search':
            $search = $_GET['search'] ?? '';
            $prestations = $controller->searchPrestations($search);
            echo json_encode($prestations);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}

// --- Gestion des requêtes POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new PrestationController($pdo);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = $controller->createPrestation($_POST['libelle_prestation']);
            echo json_encode(['success' => $result]);
            break;

        case 'update':
            $result = $controller->updatePrestation(
                $_POST['id'],
                $_POST['libelle_prestation']
            );
            echo json_encode(['success' => $result]);
            break;

        case 'delete':
            $result = $controller->deletePrestation($_POST['id']);
            echo json_encode(['success' => $result]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
?>