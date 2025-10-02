<?php
require_once '../include/db.php';
require_once 'communes_class.php';

class CommunesController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer toutes les communes
    public function getAllCommunes()
    {
        $stmt = $this->pdo->query("SELECT * FROM communes ORDER BY nom_commune");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer une commune par ID
    public function getCommuneById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM communes WHERE id_commune = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer une commune par nom
    public function getByNom($nom)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM communes WHERE nom_commune = ?");
        $stmt->execute([$nom]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les communes par code postal
    public function getByCodePostal($cp)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM communes WHERE cp_commune = ? ORDER BY nom_commune");
        $stmt->execute([$cp]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Créer une nouvelle commune
    public function createCommune($nom_commune, $cp_commune, $gps_commune)
    {
        $stmt = $this->pdo->prepare("INSERT INTO communes (nom_commune, cp_commune, gps_commune) VALUES (?, ?, ?)");
        return $stmt->execute([$nom_commune, $cp_commune, $gps_commune]);
    }

    // Mettre à jour une commune
    public function updateCommune($id, $nom_commune, $cp_commune, $gps_commune)
    {
        $stmt = $this->pdo->prepare("UPDATE communes SET nom_commune = ?, cp_commune = ?, gps_commune = ? WHERE id_commune = ?");
        return $stmt->execute([$nom_commune, $cp_commune, $gps_commune, $id]);
    }

    // Supprimer une commune
    public function deleteCommune($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM communes WHERE id_commune = ?");
        return $stmt->execute([$id]);
    }

    // Rechercher des communes
    public function searchCommunes($search)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM communes WHERE nom_commune LIKE ? OR cp_commune LIKE ? ORDER BY nom_commune");
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les biens d'une commune
    public function getBiensByCommune($id_commune)
    {
        $sql = "SELECT b.* FROM biens b WHERE b.id_commune = ? ORDER BY b.nomBien";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_commune]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    $controller = new CommunesController($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            $communes = $controller->getAllCommunes();
            echo json_encode($communes);
            break;

        case 'getById':
            $id = $_GET['id'] ?? 0;
            $commune = $controller->getCommuneById($id);
            echo json_encode([
                'success' => $commune !== false,
                'commune' => $commune
            ]);
            break;

        case 'getByNom':
            $nom = $_GET['nom'] ?? '';
            $commune = $controller->getByNom($nom);
            echo json_encode([
                'success' => $commune !== false,
                'commune' => $commune
            ]);
            break;

        case 'getByCodePostal':
            $cp = $_GET['cp'] ?? '';
            $communes = $controller->getByCodePostal($cp);
            echo json_encode($communes);
            break;

        case 'getBiens':
            $id = $_GET['id'] ?? 0;
            $biens = $controller->getBiensByCommune($id);
            echo json_encode($biens);
            break;

        case 'search':
            $search = $_GET['search'] ?? '';
            $communes = $controller->searchCommunes($search);
            echo json_encode($communes);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}

// --- Gestion des requêtes POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new CommunesController($pdo);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = $controller->createCommune(
                $_POST['nom_commune'],
                $_POST['cp_commune'],
                $_POST['gps_commune'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;

        case 'update':
            $result = $controller->updateCommune(
                $_POST['id_commune'],
                $_POST['nom_commune'],
                $_POST['cp_commune'],
                $_POST['gps_commune'] ?? null
            );
            echo json_encode(['success' => $result]);
            break;

        case 'delete':
            $result = $controller->deleteCommune($_POST['id_commune']);
            echo json_encode(['success' => $result]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
?>