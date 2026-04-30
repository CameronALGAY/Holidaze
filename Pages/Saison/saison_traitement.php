<?php
// Empêcher l'affichage des erreurs en HTML
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Définir le header JSON dès le début
header('Content-Type: application/json');

try {
    require_once '../../include/db.php';
    require_once '../../include/csrf.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    class SaisonController {
        private $pdo;
        
        public function __construct($pdo) { 
            $this->pdo = $pdo; 
        }

        public function getAllSaisons() {
            $stmt = $this->pdo->query("SELECT * FROM saison ORDER BY libelle_saison");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getById($id) {
            $stmt = $this->pdo->prepare("SELECT * FROM saison WHERE id_saison = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function create($libelle) {
            $stmt = $this->pdo->prepare("INSERT INTO saison (libelle_saison) VALUES (?)");
            return $stmt->execute([$libelle]);
        }

        public function update($id, $libelle) {
            $stmt = $this->pdo->prepare("UPDATE saison SET libelle_saison = ? WHERE id_saison = ?");
            return $stmt->execute([$libelle, $id]);
        }

        public function delete($id) {
            $stmt = $this->pdo->prepare("DELETE FROM saison WHERE id_saison = ?");
            return $stmt->execute([$id]);
        }

        public function search($search) {
            $stmt = $this->pdo->prepare("SELECT * FROM saison WHERE libelle_saison LIKE ? ORDER BY libelle_saison");
            $stmt->execute(["%$search%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $controller = new SaisonController($pdo);
    $action = $_REQUEST['action'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
    }

    switch ($action) {
        case 'getAll':
            echo json_encode($controller->getAllSaisons());
            break;

        case 'getById':
            echo json_encode($controller->getById($_GET['id'] ?? 0));
            break;

        case 'create':
            $libelle = $_POST['libelle_saison'] ?? '';
            if (empty($libelle)) {
                echo json_encode(['success' => false, 'message' => 'Le libellé est requis']);
            } else {
                echo json_encode(['success' => $controller->create($libelle)]);
            }
            break;

        case 'update':
            $id = $_POST['id'] ?? 0;
            $libelle = $_POST['libelle_saison'] ?? '';
            if (empty($libelle)) {
                echo json_encode(['success' => false, 'message' => 'Le libellé est requis']);
            } else {
                echo json_encode(['success' => $controller->update($id, $libelle)]);
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            echo json_encode(['success' => $controller->delete($id)]);
            break;

        case 'search':
            $search = $_GET['search'] ?? '';
            echo json_encode($controller->search($search));
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }

} catch (Exception $e) {
    // En cas d'erreur, retourner du JSON valide
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}