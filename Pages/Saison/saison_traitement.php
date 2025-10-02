<?php
require_once '../../includes/db.php';
require_once 'saison_class.php';

class Saison
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer toutes les saisons
    public function getAllSaisons()
    {
        $stmt = $this->pdo->query("SELECT * FROM saison ORDER BY libelle_saison");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer une saison par ID
    public function getSaisonById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM saison WHERE id_saison = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer une saison par nom
    public function getByLibelle($libelle)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM saison WHERE libelle_saison = ?");
        $stmt->execute([$libelle]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer une nouvelle saison
    public function createSaison($libelle_saison)
    {
        $stmt = $this->pdo->prepare("INSERT INTO saison (libelle_saison) VALUES (?)");
        return $stmt->execute([$libelle_saison]);
    }

    // Mettre à jour une saison
    public function updateSaison($id, $libelle_saison)
    {
        $stmt = $this->pdo->prepare("UPDATE saison SET libelle_saison = ? WHERE id_saison = ?");
        return $stmt->execute([$libelle_saison, $id]);
    }

    // Supprimer une saison
    public function deleteSaison($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM saison WHERE id_saison = ?");
        return $stmt->execute([$id]);
    }

    // Rechercher des saisons par mot-clé
    public function searchSaisons($search)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM saison WHERE libelle_saison LIKE ? ORDER BY libelle_saison");
        $stmt->execute(['%' . $search . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    $controller = new Saison($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            $saisons = $controller->getAllSaisons();
            echo json_encode($saisons);
            break;

        case 'getById':
            $id = $_GET['id'] ?? 0;
            $saison = $controller->getSaisonById($id);
            echo json_encode([
                'success' => $saison !== false,
                'saison' => $saison
            ]);
            break;

        case 'getByLibelle':
            $libelle = $_GET['libelle'] ?? '';
            $saison = $controller->getByLibelle($libelle);
            echo json_encode([
                'success' => $saison !== false,
                'saison' => $saison
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
?>
