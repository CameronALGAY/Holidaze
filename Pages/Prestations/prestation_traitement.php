<?php
// Empêcher l'affichage des erreurs en HTML
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Définir le header JSON dès le début
header('Content-Type: application/json');

try {
    require_once '../../include/db.php';
    require_once 'prestation_class.php';

    class PrestationController {
        private $pdo;
        
        public function __construct($pdo) { 
            $this->pdo = $pdo; 
        }

        public function getAll() {
            $stmt = $this->pdo->query("SELECT * FROM prestation ORDER BY libelle_prestation");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getById($id) {
            $stmt = $this->pdo->prepare("SELECT * FROM prestation WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function getByLibelle($libelle) {
            $stmt = $this->pdo->prepare("SELECT * FROM prestation WHERE libelle_prestation = ?");
            $stmt->execute([$libelle]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function create($libelle) {
            $stmt = $this->pdo->prepare("INSERT INTO prestation (libelle_prestation) VALUES (?)");
            return $stmt->execute([$libelle]);
        }

        public function update($id, $libelle) {
            $stmt = $this->pdo->prepare("UPDATE prestation SET libelle_prestation = ? WHERE id = ?");
            return $stmt->execute([$libelle, $id]);
        }

        public function delete($id) {
            $stmt = $this->pdo->prepare("DELETE FROM prestation WHERE id = ?");
            return $stmt->execute([$id]);
        }

        public function search($search) {
            $stmt = $this->pdo->prepare("SELECT * FROM prestation WHERE libelle_prestation LIKE ? ORDER BY libelle_prestation");
            $stmt->execute(["%$search%"]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getByBien($idBien) {
            $sql = "SELECT p.* FROM prestation p
                    INNER JOIN bien_prestation bp ON p.id = bp.id_prestation
                    WHERE bp.idBien = ?
                    ORDER BY p.libelle_prestation";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idBien]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $controller = new PrestationController($pdo);
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'getAll': 
            echo json_encode($controller->getAll()); 
            break;
            
        case 'getById': 
            echo json_encode($controller->getById($_GET['id'] ?? 0)); 
            break;
            
        case 'getByLibelle': 
            echo json_encode($controller->getByLibelle($_GET['libelle'] ?? '')); 
            break;
            
        case 'getByBien': 
            echo json_encode($controller->getByBien($_GET['idBien'] ?? 0)); 
            break;
            
        case 'search': 
            echo json_encode($controller->search($_GET['search'] ?? '')); 
            break;

        case 'create':
            $result = $controller->create($_POST['libelle_prestation'] ?? '');
            echo json_encode(['success' => $result]);
            break;
            
        case 'update':
            $result = $controller->update($_POST['id'] ?? 0, $_POST['libelle_prestation'] ?? '');
            echo json_encode(['success' => $result]);
            break;
            
        case 'delete':
            $result = $controller->delete($_POST['id'] ?? 0);
            echo json_encode(['success' => $result]);
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