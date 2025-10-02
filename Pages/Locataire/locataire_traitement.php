<?php

include 'locataire_class.php';

class Locataire
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tous les locataires avec leur commune
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

    // Récupérer un locataire par son ID
    public function getLocataireById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, c.nom_commune
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            WHERE l.id_locataire = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer un locataire par son nom
    public function getByName($nom)
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, c.nom_commune
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            WHERE l.nom_locataire = ?
        ");
        $stmt->execute([$nom]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer un locataire
    public function createLocataire($nom, $prenom, $dna, $email, $rue, $pass, $tel, $comp, $id_commune, $raison, $siret)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO locataire (nom_locataire, prenom_locataire, dna_locataire, email_locataire, rue_locataire, pass_locataire, tel_locataire, comp_locataire, id_commune, raison_social, siret)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$nom, $prenom, $dna, $email, $rue, $pass, $tel, $comp, $id_commune, $raison, $siret]);
    }

    // Modifier un locataire
    public function updateLocataire($id, $nom, $prenom, $dna, $email, $rue, $pass, $tel, $comp, $id_commune, $raison, $siret)
    {
        $stmt = $this->pdo->prepare("
            UPDATE locataire
            SET nom_locataire = ?, prenom_locataire = ?, dna_locataire = ?, email_locataire = ?, rue_locataire = ?, pass_locataire = ?, tel_locataire = ?, comp_locataire = ?, id_commune = ?, raison_social = ?, siret = ?
            WHERE id_locataire = ?
        ");
        return $stmt->execute([$nom, $prenom, $dna, $email, $rue, $pass, $tel, $comp, $id_commune, $raison, $siret, $id]);
    }

    // Supprimer un locataire
    public function deleteLocataire($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM locataire WHERE id_locataire = ?");
        return $stmt->execute([$id]);
    }

    // Rechercher un locataire par nom, prénom, email ou raison sociale
    public function searchLocataires($search)
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, c.nom_commune
            FROM locataire l
            LEFT JOIN commune c ON l.id_commune = c.id_commune
            WHERE l.nom_locataire LIKE ? 
               OR l.prenom_locataire LIKE ? 
               OR l.email_locataire LIKE ? 
               OR l.raison_social LIKE ?
            ORDER BY l.nom_locataire
        ");
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

