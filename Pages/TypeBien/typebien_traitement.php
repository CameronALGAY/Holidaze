<?php
require_once '../../includes/db.php';
require_once '../TupeBien/typebien_class.php';

class TypeBienController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tous les types de bien
    public function getAllTypeBien()
    {
        $stmt = $this->pdo->query("SELECT * FROM type_bien ORDER BY des_typebien");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un type de bien par ID
    public function getTypeBienById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM type_bien WHERE id_typebien = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer un type de bien par description
    public function getByDescription($desc)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM type_bien WHERE des_typebien = ?");
        $stmt->execute([$desc]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer un nouveau type de bien
    public function createTypeBien($des_typebien)
    {
        $stmt = $this->pdo->prepare("INSERT INTO type_bien (des_typebien) VALUES (?)");
        return $stmt->execute([$des_typebien]);
    }

    // Mettre à jour un type de bien
    public function updateTypeBien($id, $des_typebien)
    {
        $stmt = $this->pdo->prepare("UPDATE type_bien SET des_typebien = ? WHERE id_typebien = ?");
        return $stmt->execute([$des_typebien, $id]);
    }

    // Supprimer un type de bien
    public function deleteTypeBien($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM type_bien WHERE id_typebien = ?");
        return $stmt->execute([$id]);
    }

    // Rechercher des types de bien
    public function searchTypeBien($search)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM type_bien WHERE des_typebien LIKE ? ORDER BY des_typebien");
        $stmt->execute(['%' . $search . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    $controller = new TypeBienController($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            $types = $controller->getAllTypeBien();
            echo json_encode($types);
            break;

        case 'getById':
            $id = $_GET['id'] ?? 0;
            $type = $controller->getTypeBienById($id);
            echo json_encode([
                'success' => $type !== false,
                'type' => $type
            ]);
            break;

        case 'getByDescription':
            $desc = $_GET['desc'] ?? '';
            $type = $controller->getByDescription($desc);
            echo json_encode([
                'success' => $type !== false,
                'type' => $type
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
?>
