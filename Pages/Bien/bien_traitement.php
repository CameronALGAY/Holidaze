<<?php
require_once __DIR__ . '/../../include/db.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';

// Vérification de la connexion
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base']);
    exit;
}

// ... le reste de ton code BiensController

class BiensController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tous les biens avec leurs relations
    public function getAllBiens()
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM biens b
                LEFT JOIN communes c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                ORDER BY b.nomBien";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un bien par ID
    public function getBienById($id)
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM biens b
                LEFT JOIN communes c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                WHERE b.idBien = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer un bien par nom
    public function getByName($nom)
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM biens b
                LEFT JOIN communes c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                WHERE b.nomBien = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$nom]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les biens par type
    public function getBiensByType($id_typebien)
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM biens b
                LEFT JOIN communes c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                WHERE b.id_typebien = ?
                ORDER BY b.nomBien";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_typebien]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les biens par commune
    public function getBiensByCommune($id_commune)
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM biens b
                LEFT JOIN communes c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                WHERE b.id_commune = ?
                ORDER BY b.nomBien";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_commune]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Créer un nouveau bien
    public function createBien($nomBien, $descriptionBien, $rueBien, $compBien, $superficieBien, $animauxBien, $nbCouchagesBien, $id_commune, $id_typebien)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO biens (nomBien, descriptionBien, rueBien, compBien, superficieBien, animauxBien, nbCouchagesBien, id_commune, id_typebien)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$nomBien, $descriptionBien, $rueBien, $compBien, $superficieBien, $animauxBien, $nbCouchagesBien, $id_commune, $id_typebien]);
    }

    // Mettre à jour un bien
    public function updateBien($idBien, $nomBien, $descriptionBien, $rueBien, $compBien, $superficieBien, $animauxBien, $nbCouchagesBien, $id_commune, $id_typebien)
    {
        $stmt = $this->pdo->prepare("
            UPDATE biens 
            SET nomBien = ?, descriptionBien = ?, rueBien = ?, compBien = ?, superficieBien = ?, 
                animauxBien = ?, nbCouchagesBien = ?, id_commune = ?, id_typebien = ?
            WHERE idBien = ?
        ");
        return $stmt->execute([$nomBien, $descriptionBien, $rueBien, $compBien, $superficieBien, $animauxBien, $nbCouchagesBien, $id_commune, $id_typebien, $idBien]);
    }

    // Supprimer un bien
    public function deleteBien($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM biens WHERE idBien = ?");
        return $stmt->execute([$id]);
    }

    // Rechercher des biens
    public function searchBiens($search)
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM biens b
                LEFT JOIN communes c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                WHERE b.nomBien LIKE ? 
                   OR b.descriptionBien LIKE ? 
                   OR c.nom_commune LIKE ?
                   OR t.des_typebien LIKE ?
                ORDER BY b.nomBien";
        $stmt = $this->pdo->prepare($sql);
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Filtrer les biens avec critères multiples
    public function filterBiens($filters = [])
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM biens b
                LEFT JOIN communes c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['id_typebien'])) {
            $sql .= " AND b.id_typebien = ?";
            $params[] = $filters['id_typebien'];
        }

        if (!empty($filters['id_commune'])) {
            $sql .= " AND b.id_commune = ?";
            $params[] = $filters['id_commune'];
        }

        if (!empty($filters['min_superficie'])) {
            $sql .= " AND b.superficieBien >= ?";
            $params[] = $filters['min_superficie'];
        }

        if (!empty($filters['max_superficie'])) {
            $sql .= " AND b.superficieBien <= ?";
            $params[] = $filters['max_superficie'];
        }

        if (isset($filters['animaux'])) {
            $sql .= " AND b.animauxBien = ?";
            $params[] = $filters['animaux'];
        }

        if (!empty($filters['min_couchages'])) {
            $sql .= " AND b.nbCouchagesBien >= ?";
            $params[] = $filters['min_couchages'];
        }

        $sql .= " ORDER BY b.nomBien";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    $controller = new BiensController($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            $biens = $controller->getAllBiens();
            echo json_encode($biens);
            break;

        case 'getById':
            $id = $_GET['id'] ?? 0;
            $bien = $controller->getBienById($id);
            echo json_encode([
                'success' => $bien !== false,
                'bien' => $bien
            ]);
            break;

        case 'getByName':
            $nom = $_GET['nom'] ?? '';
            $bien = $controller->getByName($nom);
            echo json_encode([
                'success' => $bien !== false,
                'bien' => $bien
            ]);
            break;

        case 'getByType':
            $id_typebien = $_GET['id_typebien'] ?? 0;
            $biens = $controller->getBiensByType($id_typebien);
            echo json_encode($biens);
            break;

        case 'getByCommune':
            $id_commune = $_GET['id_commune'] ?? 0;
            $biens = $controller->getBiensByCommune($id_commune);
            echo json_encode($biens);
            break;

        case 'search':
            $search = $_GET['search'] ?? '';
            $biens = $controller->searchBiens($search);
            echo json_encode($biens);
            break;

        case 'filter':
            $filters = [
                'id_typebien' => $_GET['id_typebien'] ?? null,
                'id_commune' => $_GET['id_commune'] ?? null,
                'min_superficie' => $_GET['min_superficie'] ?? null,
                'max_superficie' => $_GET['max_superficie'] ?? null,
                'animaux' => $_GET['animaux'] ?? null,
                'min_couchages' => $_GET['min_couchages'] ?? null
            ];
            $biens = $controller->filterBiens($filters);
            echo json_encode($biens);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}

// --- Gestion des requêtes POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new BiensController($pdo);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = $controller->createBien(
                $_POST['nomBien'],
                $_POST['descriptionBien'],
                $_POST['rueBien'],
                $_POST['compBien'] ?? '',
                $_POST['superficieBien'],
                $_POST['animauxBien'] ?? 0,
                $_POST['nbCouchagesBien'],
                $_POST['id_commune'],
                $_POST['id_typebien']
            );
            echo json_encode(['success' => $result]);
            break;

        case 'update':
            $result = $controller->updateBien(
                $_POST['idBien'],
                $_POST['nomBien'],
                $_POST['descriptionBien'],
                $_POST['rueBien'],
                $_POST['compBien'] ?? '',
                $_POST['superficieBien'],
                $_POST['animauxBien'] ?? 0,
                $_POST['nbCouchagesBien'],
                $_POST['id_commune'],
                $_POST['id_typebien']
            );
            echo json_encode(['success' => $result]);
            break;

        case 'delete':
            $result = $controller->deleteBien($_POST['idBien']);
            echo json_encode(['success' => $result]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
?>