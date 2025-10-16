<?php
class BiensController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Récupère tous les biens avec leur commune, type et tarif (si existant)
    public function getAllBiens() {
        $sql = "SELECT 
                    b.*,
                    com.nom_commune,
                    tb.des_typebien,
                    tr.tarif
                FROM bien b
                LEFT JOIN commune com ON b.id_commune = com.id_commune
                LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
                LEFT JOIN tarif tr ON b.id_bien = tr.id_bien
                GROUP BY b.id_bien";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupère un bien précis par son ID
    public function getBienById($id_bien) {
        $sql = "SELECT 
                    b.*,
                    com.nom_commune,
                    tb.des_typebien,
                    tr.tarif
                FROM bien b
                LEFT JOIN commune com ON b.id_commune = com.id_commune
                LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
                LEFT JOIN tarif tr ON b.id_bien = tr.id_bien
                WHERE b.id_bien = :id_bien
                GROUP BY b.id_bien";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_bien' => $id_bien]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Création d'un nouveau bien
    public function createBien($nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien) {
        $sql = "INSERT INTO bien 
                    (nom_bien, description_bien, rue_bien, com_bien, superficie_bien, animaux_bien, nb_couchage, id_commune, id_typebien)
                VALUES
                    (:nom, :description, :rue, :com, :superficie, :animaux, :nbCouchages, :id_commune, :id_typebien)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nom' => $nom,
            'description' => $description,
            'rue' => $rue,
            'com' => $com,
            'superficie' => $superficie,
            'animaux' => $animaux,
            'nbCouchages' => $nbCouchages,
            'id_commune' => $id_commune,
            'id_typebien' => $id_typebien
        ]);

        return $this->pdo->lastInsertId();
    }

    // Mise à jour d'un bien existant
    public function updateBien($id_bien, $nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien) {
        $sql = "UPDATE bien SET
                    nom_bien = :nom,
                    description_bien = :description,
                    rue_bien = :rue,
                    com_bien = :com,
                    superficie_bien = :superficie,
                    animaux_bien = :animaux,
                    nb_couchage = :nbCouchages,
                    id_commune = :id_commune,
                    id_typebien = :id_typebien
                WHERE id_bien = :id_bien";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'nom' => $nom,
            'description' => $description,
            'rue' => $rue,
            'com' => $com,
            'superficie' => $superficie,
            'animaux' => $animaux,
            'nbCouchages' => $nbCouchages,
            'id_commune' => $id_commune,
            'id_typebien' => $id_typebien,
            'id_bien' => $id_bien
        ]);
    }

    // Suppression d'un bien (supprime aussi photos et tarifs liés)
    public function deleteBien($id_bien) {
        $this->pdo->beginTransaction();
        try {
            // Supprimer les photos
            $stmtPhoto = $this->pdo->prepare("DELETE FROM photo WHERE id_bien = ?");
            $stmtPhoto->execute([$id_bien]);
            
            // Supprimer les tarifs
            $stmtTarif = $this->pdo->prepare("DELETE FROM tarif WHERE id_bien = ?");
            $stmtTarif->execute([$id_bien]);
            
            // Supprimer le bien
            $stmt = $this->pdo->prepare("DELETE FROM bien WHERE id_bien = ?");
            $result = $stmt->execute([$id_bien]);
            
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // Récupérer les photos d'un bien
    public function getPhotosByBienId($id_bien) {
        $sql = "SELECT * FROM photo WHERE id_bien = ? ORDER BY id_photo DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_bien]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
