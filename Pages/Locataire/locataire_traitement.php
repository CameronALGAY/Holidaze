<?php
// Vérifier si une session est déjà active avant d'appeler session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'locataire_class.php';
require_once '../Communes/communes_class.php';
require_once __DIR__ . '/../../include/db.php';

class LocataireController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createLocataire($locataire)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO locataire 
            (nom_locataire, prenom_locataire, dna_locataire, email_locataire, rue_locataire, pass_locataire, tel_locataire, comp_locataire, id_commune, raison_sociale, siret)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $idCommune = is_object($locataire->getIdCommune()) ? 
            $locataire->getIdCommune()->getIdCommune() : 
            $locataire->getIdCommune();
        
        return $stmt->execute([
            $locataire->getNomLocataire(),
            $locataire->getPrenomLocataire(),
            $locataire->getDnaLocataire(),
            $locataire->getEmailLocataire(),
            $locataire->getRueLocataire(),
            password_hash($locataire->getPassLocataire(), PASSWORD_DEFAULT),
            $locataire->getTelLocataire(),
            $locataire->getCompLocataire(),
            $idCommune,
            $locataire->getRaisonSocial(),
            $locataire->getSiret()
        ]);
    }

    public function getAllLocataires()
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, c.nom_commune, c.cp_commune, c.commune_departement
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            ORDER BY l.nom_locataire, l.prenom_locataire
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateLocataire($id, $locataire)
    {
        if ($id <= 0 || empty($locataire->getNomLocataire()) || empty($locataire->getEmailLocataire()) || empty($locataire->getIdCommune())) {
            return false;
        }
        $stmt = $this->pdo->prepare("
            UPDATE locataire 
            SET nom_locataire = ?, prenom_locataire = ?, dna_locataire = ?, email_locataire = ?, 
                rue_locataire = ?, pass_locataire = ?, tel_locataire = ?, comp_locataire = ?, 
                id_commune = ?, raison_sociale = ?, siret = ?
            WHERE id_locataire = ?
        ");
        
        $idCommune = is_object($locataire->getIdCommune()) ? 
            $locataire->getIdCommune()->getIdCommune() : 
            $locataire->getIdCommune();
        
        $pass = $locataire->getPassLocataire() ? 
            password_hash($locataire->getPassLocataire(), PASSWORD_DEFAULT) : 
            $this->getLocataireById($id)['pass_locataire'];
        
        return $stmt->execute([
            $locataire->getNomLocataire(),
            $locataire->getPrenomLocataire(),
            $locataire->getDnaLocataire(),
            $locataire->getEmailLocataire(),
            $locataire->getRueLocataire(),
            $pass,
            $locataire->getTelLocataire(),
            $locataire->getCompLocataire(),
            $idCommune,
            $locataire->getRaisonSocial(),
            $locataire->getSiret(),
            $id
        ]);
    }

    public function deleteLocataire($id)
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM locataire WHERE id_locataire = ?");
        return $stmt->execute([$id]);
    }

    public function searchLocataires($search)
    {
        $search = '%' . $search . '%';
        $stmt = $this->pdo->prepare("
            SELECT l.*, c.nom_commune, c.cp_commune, c.commune_departement
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            WHERE l.nom_locataire LIKE ? OR l.prenom_locataire LIKE ? OR l.email_locataire LIKE ?
            ORDER BY l.nom_locataire, l.prenom_locataire
        ");
        $stmt->execute([$search, $search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLocataireById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, c.nom_commune, c.cp_commune, c.commune_departement
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            WHERE l.id_locataire = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($pdo)) {
            throw new Exception("Connexion à la base de données non établie");
        }
        
        $controller = new LocataireController($pdo);
        
        if ($_POST['action'] === 'create') {
            $locataire = new Locataire(
                null,
                trim($_POST['nom_locataire'] ?? ''),
                trim($_POST['prenom_locataire'] ?? ''),
                $_POST['dna_locataire'] ?? '',
                trim($_POST['email_locataire'] ?? ''),
                trim($_POST['rue_locataire'] ?? ''),
                $_POST['pass_locataire'] ?? '',
                trim($_POST['tel_locataire'] ?? ''),
                trim($_POST['comp_locataire'] ?? ''),
                $_POST['id_commune'] ?? null,
                trim($_POST['raison_sociale'] ?? ''),
                trim($_POST['siret'] ?? '')
            );
            if ($controller->createLocataire($locataire)) {
                $_SESSION['success'] = "Locataire ajouté avec succès !";
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout du locataire";
            }
        } elseif ($_POST['action'] === 'update') {
            $id = $_POST['id_locataire'] ?? 0;
            $locataire = new Locataire(
                $id,
                trim($_POST['nom_locataire'] ?? ''),
                trim($_POST['prenom_locataire'] ?? ''),
                $_POST['dna_locataire'] ?? '',
                trim($_POST['email_locataire'] ?? ''),
                trim($_POST['rue_locataire'] ?? ''),
                $_POST['pass_locataire'] ?? '',
                trim($_POST['tel_locataire'] ?? ''),
                trim($_POST['comp_locataire'] ?? ''),
                $_POST['id_commune'] ?? null,
                trim($_POST['raison_sociale'] ?? ''),
                trim($_POST['siret'] ?? '')
            );
            if ($controller->updateLocataire($id, $locataire)) {
                $_SESSION['success'] = "Locataire modifié avec succès !";
            } else {
                $_SESSION['error'] = "Erreur lors de la modification du locataire";
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id_locataire'] ?? 0;
            if ($controller->deleteLocataire($id)) {
                $_SESSION['success'] = "Locataire supprimé avec succès !";
            } else {
                $_SESSION['error'] = "Erreur lors de la suppression du locataire";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
    }
    
    header('Location: locataire_form.php' . (isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}
?>