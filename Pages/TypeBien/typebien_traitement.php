<?php
require_once '../../include/db.php';
require_once '../TypeBien/typebien_class.php';

class TypeBienController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Méthodes CRUD ---
    public function getAllTypeBien()
    {
        $stmt = $this->pdo->query("SELECT * FROM type_bien ORDER BY des_typebien");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTypeBienById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM type_bien WHERE id_typebien = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByDescription($desc)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM type_bien WHERE des_typebien = ?");
        $stmt->execute([$desc]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createTypeBien($des_typebien)
    {
        $stmt = $this->pdo->prepare("INSERT INTO type_bien (des_typebien) VALUES (?)");
        return $stmt->execute([$des_typebien]);
    }

    public function updateTypeBien($id, $des_typebien)
    {
        $stmt = $this->pdo->prepare("UPDATE type_bien SET des_typebien = ? WHERE id_typebien = ?");
        return $stmt->execute([$des_typebien, $id]);
    }

    public function deleteTypeBien($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM type_bien WHERE id_typebien = ?");
        return $stmt->execute([$id]);
    }

    public function searchTypeBien($search)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM type_bien WHERE des_typebien LIKE ? ORDER BY des_typebien");
        $stmt->execute(['%' . $search . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// --- Initialisation du contrôleur ---
$controller = new TypeBienController($pdo);

// --- Gestion du formulaire POST classique ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $des_typebien = trim($_POST['des_typebien'] ?? '');

    if (!empty($des_typebien)) {
        $success = $controller->createTypeBien($des_typebien);
        if ($success) {
            header('Location: typebien.php?success=1');
            header('Location: typebien.php?success=1');
            exit;
        } else {
            header('Location: typebien.php?error=1');
            exit;
        }
    } else {
        header('Location: typebien.php?error=2');
        exit;
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
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

        case 'delete':
            $id = $_GET['id'] ?? 0;
            if ($id) {
                $success = $controller->deleteTypeBien($id);
                if ($success) {
                    header('Location: typebien_form.php?success=1');
                    exit;
                } else {
                    header('Location: typebien_form.php?error=1');
                    exit;
                }
            } else {
                header('Location: typebien_form.php?error=2');
                exit;
            }

        case 'update':
            $id = $_POST['id'] ?? 0;
            $des_typebien = trim($_POST['des_typebien'] ?? '');
            if ($id && !empty($des_typebien)) {
                $success = $controller->updateTypeBien($id, $des_typebien);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
