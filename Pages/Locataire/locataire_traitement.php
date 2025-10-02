<?php
require_once '../../includes/db.php';
require_once 'locataire_class.php';
require_once '../Communes/communes_class.php';

include_once '../include/db.php';
include_once 'locataire_class.php';

class Locataire
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllLocataires()
    {
        $stmt = $this->pdo->query("
            SELECT l.*, c.nom_commune
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            ORDER BY l.nom_locataire
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLocataireById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, c.nom_commune
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            WHERE id_locataire = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createLocataire($locataire)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO locataire 
            (nom_locataire, prenom_locataire, dna_locataire, email_locataire, rue_locataire, pass_locataire, tel_locataire, comp_locataire, id_commune, raison_social, siret)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $locataire->getNomLocataire(),
            $locataire->getPrenomLocataire(),
            $locataire->getDnaLocataire(),
            $locataire->getEmailLocataire(),
            $locataire->getRueLocataire(),
            $locataire->getPassLocataire(),
            $locataire->getTelLocataire(),
            $locataire->getCompLocataire(),
            $locataire->getIdCommune()->getIdCommune(),
            $locataire->getRaisonSocial(),
            $locataire->getSiret()
        ]);
    }

    public function updateLocataire($locataire)
    {
        $stmt = $this->pdo->prepare("
            UPDATE locataire
            SET nom_locataire = ?, prenom_locataire = ?, dna_locataire = ?, email_locataire = ?, rue_locataire = ?, pass_locataire = ?, tel_locataire = ?, comp_locataire = ?, id_commune = ?, raison_social = ?, siret = ?
            WHERE id_locataire = ?
        ");
        return $stmt->execute([
            $locataire->getNomLocataire(),
            $locataire->getPrenomLocataire(),
            $locataire->getDnaLocataire(),
            $locataire->getEmailLocataire(),
            $locataire->getRueLocataire(),
            $locataire->getPassLocataire(),
            $locataire->getTelLocataire(),
            $locataire->getCompLocataire(),
            $locataire->getIdCommune()->getIdCommune(),
            $locataire->getRaisonSocial(),
            $locataire->getSiret(),
            $locataire->getIdLocataire()
        ]);
    }

    public function deleteLocataire($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM locataire WHERE id_locataire = ?");
        return $stmt->execute([$id]);
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    $controller = new LocataireController($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            echo json_encode($controller->getAllLocataires());
            break;
        case 'getById':
            $id = $_GET['id'] ?? 0;
            echo json_encode($controller->getLocataireById($id));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
}
?>
