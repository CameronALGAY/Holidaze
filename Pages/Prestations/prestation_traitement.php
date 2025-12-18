<?php
require_once(__DIR__ . '/../../include/db.php');
require_once 'prestation_class.php';

class PrestationController {
    private $pdo;
    
    public function __construct($pdo) { 
        $this->pdo = $pdo; 
    }

    public function getAllPrestations() {
        $stmt = $this->pdo->query("SELECT * FROM prestation ORDER BY libelle_prestation");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPrestationById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM prestation WHERE id_prestation = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByLibelle($libelle) {
        $stmt = $this->pdo->prepare("SELECT * FROM prestation WHERE libelle_prestation = ?");
        $stmt->execute([$libelle]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($libelle) {
        if (empty($libelle)) {
            return false;
        }
        $stmt = $this->pdo->prepare("INSERT INTO prestation (libelle_prestation) VALUES (?)");
        return $stmt->execute([$libelle]);
    }

    public function update($id, $libelle) {
        if ($id <= 0 || empty($libelle)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE prestation SET libelle_prestation = ? WHERE id_prestation = ?");
        return $stmt->execute([$libelle, $id]);
    }

    public function delete($id) {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare("DELETE FROM prestation WHERE id_prestation = ?");
        return $stmt->execute([$id]);
    }

    public function searchPrestations($search) {
        $stmt = $this->pdo->prepare("SELECT * FROM prestation WHERE libelle_prestation LIKE ? ORDER BY libelle_prestation");
        $stmt->execute(["%$search%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByBien($idBien) {
        $sql = "SELECT p.* FROM prestation p
                INNER JOIN bien_prestation bp ON p.id_prestation = bp.id_prestation
                WHERE bp.idBien = ?
                ORDER BY p.libelle_prestation";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idBien]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>