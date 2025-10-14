<?php
require_once '../../include/db.php';

class BiensController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer tous les biens
    public function getAllBiens()
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM bien b
                LEFT JOIN commune c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                ORDER BY b.nom_bien";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un bien par ID
    public function getBienById($id)
    {
        $sql = "SELECT b.*, c.nom_commune, c.cp_commune, t.des_typebien
                FROM bien b
                LEFT JOIN commune c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien t ON b.id_typebien = t.id_typebien
                WHERE b.id_bien = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Créer un bien
    public function createBien($nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO bien (nom_bien, description_bien, rue_bien, com_bien, superficie_bien, animaux_bien, nb_couchage, id_commune, id_typebien)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien]);
    }

    // Mettre à jour un bien
    public function updateBien($id, $nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien)
    {
        $stmt = $this->pdo->prepare("
            UPDATE bien
            SET nom_bien = ?, description_bien = ?, rue_bien = ?, com_bien = ?, superficie_bien = ?, 
                animaux_bien = ?, nb_couchage = ?, id_commune = ?, id_typebien = ?
            WHERE id_bien = ?
        ");
        return $stmt->execute([$nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien, $id]);
    }

    // Supprimer un bien
    public function deleteBien($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM bien WHERE id_bien = ?");
        return $stmt->execute([$id]);
    }
}
?>
