<?php

include 'bien_class.php';

class Bien

{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tous les biens avec leur propriétaire
    public function getAllBiens()
    {
        $stmt = $this->pdo->query("
            SELECT b.*, p.nom_proprietaire, p.prenom_proprietaire
            FROM bien b
            LEFT JOIN proprietaire p ON b.id_proprietaire = p.id_proprietaire
            ORDER BY b.nom_bien
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un bien par son ID
    public function getBienById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, p.nom_proprietaire, p.prenom_proprietaire
            FROM bien b
            LEFT JOIN proprietaire p ON b.id_proprietaire = p.id_proprietaire
            WHERE b.id_bien = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer un bien par son nom
    public function getByName($nom)
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, p.nom_proprietaire, p.prenom_proprietaire
            FROM bien b
            LEFT JOIN proprietaire p ON b.id_proprietaire = p.id_proprietaire
            WHERE b.nom_bien = ?
        ");
        $stmt->execute([$nom]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer un bien
    public function createBien($nom, $description, $rue, $codePostal, $ville, $prix, $surface, $nbPieces, $type, $disponibilite, $idProprietaire)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO bien (nom_bien, description_bien, rue_bien, code_postal_bien, ville_bien, prix_bien, surface_bien, nb_pieces_bien, type_bien, disponibilite_bien, id_proprietaire)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$nom, $description, $rue, $codePostal, $ville, $prix, $surface, $nbPieces, $type, $disponibilite, $idProprietaire]);
    }
}