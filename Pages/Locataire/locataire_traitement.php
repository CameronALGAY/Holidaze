<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session pour les messages
session_start();

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
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO locataire 
                (nom_locataire, prenom_locataire, dna_locataire, email_locataire, rue_locataire, pass_locataire, tel_locataire, comp_locataire, id_commune, raison_social, siret)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Récupérer l'ID de la commune (objet ou entier)
            $idCommune = is_object($locataire->getIdCommune()) ? 
                $locataire->getIdCommune()->getIdCommune() : 
                $locataire->getIdCommune();
            
            $result = $stmt->execute([
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
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateLocataire($locataire)
    {
        $idCommune = is_object($locataire->getIdCommune()) ? 
            $locataire->getIdCommune()->getIdCommune() : 
            $locataire->getIdCommune();
        
        $stmt = $this->pdo->prepare("
            UPDATE locataire
            SET nom_locataire = ?, prenom_locataire = ?, dna_locataire = ?, email_locataire = ?, 
                rue_locataire = ?, pass_locataire = ?, tel_locataire = ?, comp_locataire = ?, 
                id_commune = ?, raison_social = ?, siret = ?
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
            $idCommune,
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

// --- Traitement du formulaire ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: Afficher les données reçues
        error_log("POST data: " . print_r($_POST, true));
        
        // Vérifier que $pdo existe
        if (!isset($pdo)) {
            throw new Exception("Connexion à la base de données non établie");
        }
        
        // Récupération des données du formulaire
        $nom = trim($_POST['nom_locataire'] ?? '');
        $prenom = trim($_POST['prenom_locataire'] ?? '');
        $dna = $_POST['dna_locataire'] ?? '';
        $email = trim($_POST['email_locataire'] ?? '');
        $rue = trim($_POST['rue_locataire'] ?? '');
        $pass = $_POST['pass_locataire'] ?? '';
        $tel = trim($_POST['tel_locataire'] ?? '');
        $comp = trim($_POST['comp_locataire'] ?? '');
        $id_commune = $_POST['id_commune'] ?? null;
        $raison_social = trim($_POST['raison_social'] ?? '');
        $siret = trim($_POST['siret'] ?? '');

        // Validation basique
        if (empty($nom) || empty($prenom) || empty($email) || empty($pass) || empty($id_commune)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }

        // Création de l'objet Locataire
        $locataire = new Locataire(
            null,
            $nom,
            $prenom,
            $dna,
            $email,
            $rue,
            $pass,
            $tel,
            $comp,
            $id_commune,
            $raison_social,
            $siret
        );

        // Création du contrôleur et insertion
        $controller = new LocataireController($pdo);
        
        if ($controller->createLocataire($locataire)) {
            $_SESSION['success'] = "Locataire ajouté avec succès !";
            header('Location: locataire_form.php?success=1');
            exit;
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout du locataire";
            header('Location: locataire_form.php?error=1');
            exit;
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout du locataire: " . $e->getMessage());
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header('Location: locataire_form.php?error=1&msg=' . urlencode($e->getMessage()));
        exit;
    }
}

// --- Gestion des requêtes AJAX ---
if (isset($_GET['action'])) {
    if (!isset($pdo)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Connexion BDD non établie']);
        exit;
    }
    
    $controller = new LocataireController($pdo);

    switch ($_GET['action']) {
        case 'getAll':
            header('Content-Type: application/json');
            echo json_encode($controller->getAllLocataires());
            break;
        case 'getById':
            $id = $_GET['id'] ?? 0;
            header('Content-Type: application/json');
            echo json_encode($controller->getLocataireById($id));
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }
    exit;
}
?>