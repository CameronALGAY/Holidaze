<?php
require_once '../../includes/db.php';
require_once 'Tarifs/tarifs_class.php';

class TarifsController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tous les tarifs avec les informations des biens et saisons
    public function getAllTarifs()
    {
        $sql = "SELECT t.*, b.nomBien, s.libelle_saison 
                FROM tarifs t
                LEFT JOIN biens b ON t.idBien = b.idBien
                LEFT JOIN saisons s ON t.id_saison = s.id_saison
                ORDER BY t.annee_tarif DESC, t.semaine_tarif";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un tarif par ID
    public function getTarifById($id)
    {
        $sql = "SELECT t.*, b.nomBien, s.libelle_saison 
                FROM tarifs t
                LEFT JOIN biens b ON t.idBien = b.idBien
                LEFT JOIN saisons s ON t.id_saison = s.id_saison
                WHERE t.id_tarif = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les tarifs par bien
    public function getTarifsByBien($idBien)
    {
        $sql = "SELECT t.*, s.libelle_saison 
                FROM tarifs t
                LEFT JOIN saisons s ON t.id_saison = s.id_saison
                WHERE t.idBien = ?
                ORDER BY t.annee_tarif DESC, t.semaine_tarif";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idBien]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les tarifs par saison
    public function getTarifsBySaison($idSaison)
    {
        $sql = "SELECT t.*, b.nomBien 
                FROM tarifs t
                LEFT JOIN biens b ON t.idBien = b.idBien
                WHERE t.id_saison = ?
                ORDER BY t.annee_tarif DESC, t.semaine_tarif";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idSaison]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Créer un nouveau tarif
    public function createTarif($semaine_tarif, $annee_tarif, $tarif, $idBien, $id_saison)
    {
        $tarifObj = new Tarifs($this->pdo, null, $semaine_tarif, $annee_tarif, $tarif, $idBien, $id_saison);
        return $tarifObj->create();
    }

    // Mettre à jour un tarif
    public function updateTarif($id, $semaine_tarif, $annee_tarif, $tarif, $idBien, $id_saison)
    {
        $tarifObj = new Tarifs($this->pdo, $id, $semaine_tarif, $annee_tarif, $tarif, $idBien, $id_saison);
        return $tarifObj->update();
    }

    // Supprimer un tarif
    public function deleteTarif($id)
    {
        $tarifObj = new Tarifs($this->pdo, $id, null, null, null, null, null);
        return $tarifObj->delete($id);
    }

    // Rechercher des tarifs
    public function searchTarifs($search)
    {
        $sql = "SELECT t.*, b.nomBien, s.libelle_saison 
                FROM tarifs t
                LEFT JOIN biens b ON t.idBien = b.idBien
                LEFT JOIN saisons s ON t.id_saison = s.id_saison
                WHERE b.nomBien LIKE ? OR s.libelle_saison LIKE ?
                ORDER BY t.annee_tarif DESC, t.semaine_tarif";
        $stmt = $this->pdo->prepare($sql);
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    $controller = new TarifsController($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            $tarifs = $controller->getAllTarifs();
            echo json_encode($tarifs);
            break;

        case 'getById':
            $id = $_GET['id'] ?? 0;
            $tarif = $controller->getTarifById($id);
            echo json_encode([
                'success' => $tarif !== false,
                'tarif' => $tarif
            ]);
            break;

        case 'getByBien':
            $idBien = $_GET['idBien'] ?? 0;
            $tarifs = $controller->getTarifsByBien($idBien);
            echo json_encode($tarifs);
            break;

        case 'getBySaison':
            $idSaison = $_GET['idSaison'] ?? 0;
            $tarifs = $controller->getTarifsBySaison($idSaison);
            echo json_encode($tarifs);
            break;

        case 'search':
            $search = $_GET['search'] ?? '';
            $tarifs = $controller->searchTarifs($search);
            echo json_encode($tarifs);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}

// --- Gestion des requêtes POST ---
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
}
?>