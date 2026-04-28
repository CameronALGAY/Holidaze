<?php
/**
 * CONTRÔLEUR BIENS IMMOBILIERS (MVC Pattern)
 * - CRUD complet : getAllBiens, détail, create, update, delete, validate
 * - Jointures systématiques (commune, type_bien, tarifs)
 * - Sécurité : prepared statements partout
 * - Admin : voit biens non-validés, validation en 1 clic
 * - Utilisé par : index.php, recherche, admin_dashboard, bien_detail/form
 */
class BiensController {
    private $pdo;  // Connexion DB injectée

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 1. Liste TOUS biens VALIDÉS (public/index.php)
     * WHERE valide=1 + jointures essentielles
     */
    public function getAllBiens() {
        $sql = "SELECT b.*, com.nom_commune, tb.des_typebien, MAX(tr.tarif) AS tarif
                FROM bien b LEFT JOIN commune com ON b.id_commune = com.id_commune
                LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
                LEFT JOIN tarif tr ON b.id_bien = tr.id_bien
                WHERE b.valide = 1 GROUP BY b.id_bien ORDER BY b.id_bien DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 2. Détail BIEN unique (bien_detail.php)
     * Admin voit non-validés, public seulement validés
     */
    public function getBienById($id_bien, $isAdmin = false) {
        $sql = "SELECT b.*, com.nom_commune, tb.des_typebien, MAX(tr.tarif) AS tarif
                FROM bien b LEFT JOIN commune com ON b.id_commune = com.id_commune
                LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
                LEFT JOIN tarif tr ON b.id_bien = tr.id_bien WHERE b.id_bien = ?";
        if (!$isAdmin) $sql .= " AND b.valide = 1";
        $sql .= " GROUP BY b.id_bien";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_bien]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 3. Création BIEN (bien_form → traitement)
     * valide=0 (attente admin)
     */
    public function createBien($nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien, $latitude = null, $longitude = null, $id_utilisateur = null) {
        $sql = "INSERT INTO bien (nom_bien, description_bien, rue_bien, com_bien, superficie_bien, animaux_bien, nb_couchage, id_commune, id_typebien, latitude_bien, longitude_bien, id_utilisateur_proprietaire, valide) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien, $latitude, $longitude, $id_utilisateur]);
        return $this->pdo->lastInsertId();
    }

    /**
     * 4. Biens ATTENTE admin (admin_dashboard)
     */
    public function getPendingBiens() {
        $sql = "SELECT b.*, com.nom_commune, tb.des_typebien FROM bien b LEFT JOIN commune com ON b.id_commune = com.id_commune LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien WHERE b.valide = 0 ORDER BY b.id_bien DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 5. VALIDATION admin (1 clic)
     */
    public function validateBien($id_bien) {
        $stmt = $this->pdo->prepare("UPDATE bien SET valide = 1 WHERE id_bien = ?");
        return $stmt->execute([$id_bien]);
    }

    /**
     * 6. UPDATE BIEN (admin/proprio)
     */
    public function updateBien($id_bien, $nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien, $latitude = null, $longitude = null) {
        $sql = "UPDATE bien SET nom_bien=?, description_bien=?, rue_bien=?, com_bien=?, superficie_bien=?, animaux_bien=?, nb_couchage=?, id_commune=?, id_typebien=?, latitude_bien=?, longitude_bien=? WHERE id_bien=?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien, $latitude, $longitude, $id_bien]);
    }

    /**
     * 7. DELETE CASCADE (transaction)
     * Supprime tout : photos/tarifs/prestations/reservations/bien
     */
    public function deleteBien($id_bien) {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM photo WHERE id_bien=?")->execute([$id_bien]);
            $this->pdo->prepare("DELETE FROM tarif WHERE id_bien=?")->execute([$id_bien]);
            $this->pdo->prepare("DELETE FROM secompose WHERE id_bien=?")->execute([$id_bien]);
            $this->pdo->prepare("DELETE FROM reservation WHERE id_bien=?")->execute([$id_bien]);
            $result = $this->pdo->prepare("DELETE FROM bien WHERE id_bien=?")->execute([$id_bien]);
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * 8. Photos galerie (bien_detail)
     */
    public function getPhotosByBienId($id_bien) {
        $stmt = $this->pdo->prepare("SELECT * FROM photo WHERE id_bien=? ORDER BY id_photo DESC");
        $stmt->execute([$id_bien]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 9. Tarifs + saisons (bien_detail)
     */
    public function getTarifsByBienId($id_bien) {
        $sql = "SELECT t.*, s.libelle_saison FROM tarif t LEFT JOIN saison s ON t.id_saison=s.id_saison WHERE t.id_bien=? ORDER BY t.annee_tarif DESC, t.semaine_tarif ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_bien]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

