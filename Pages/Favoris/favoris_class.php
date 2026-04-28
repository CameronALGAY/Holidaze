<?php
// Favoris/favoris_class.php

class FavorisController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ajouter un bien aux favoris
     */
    public function ajouterFavori($idUser, $idBien) {
        try {
            $sql = "INSERT INTO favoris (id_utilisateur, id_bien) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$idUser, $idBien]);
        } catch (PDOException $e) {
            // Si déjà en favori (UNIQUE constraint), on ignore
            if ($e->getCode() == 23000) {
                return true;
            }
            error_log("Erreur ajout favori: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retirer un bien des favoris
     */
    public function retirerFavori($idUser, $idBien) {
        try {
            $sql = "DELETE FROM favoris WHERE id_utilisateur = ? AND id_bien = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$idUser, $idBien]);
        } catch (PDOException $e) {
            error_log("Erreur retrait favori: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un bien est en favori
     */
    public function estEnFavori($idUser, $idBien) {
        try {
            $sql = "SELECT COUNT(*) FROM favoris WHERE id_utilisateur = ? AND id_bien = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idUser, $idBien]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur vérification favori: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les biens favoris d'un utilisateur avec leurs détails
     */
    public function getFavorisByUser($idUser) {
        try {
            $sql = "SELECT 
                        b.*,
                        c.nom_commune,
                        c.cp_commune,
                        tb.des_typebien,
                        f.date_ajout as date_favori
                    FROM favoris f
                    INNER JOIN bien b ON f.id_bien = b.id_bien
                    LEFT JOIN commune c ON b.id_commune = c.id_commune
                    LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
                    WHERE f.id_utilisateur = ?
                    ORDER BY f.date_ajout DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idUser]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération favoris: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compter le nombre de favoris d'un utilisateur
     */
    public function countFavorisByUser($idUser) {
        try {
            $sql = "SELECT COUNT(*) FROM favoris WHERE id_utilisateur = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idUser]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur comptage favoris: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer une photo d'un bien
     */
    public function getPhotosByBienId($idBien) {
        try {
            $sql = "SELECT * FROM photo WHERE id_bien = ? ORDER BY id_photo LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idBien]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération photos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les tarifs d'un bien
     */
    public function getTarifsByBienId($idBien) {
        try {
            $sql = "SELECT * FROM tarif WHERE id_bien = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idBien]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération tarifs: " . $e->getMessage());
            return [];
        }
    }
}