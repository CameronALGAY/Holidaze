<?php
class ReservationsController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Récupère toutes les réservations avec détails
    public function getAllReservations() {
        $sql = "SELECT 
                    r.*,
                    b.nom_bien,
                    l.nom_locataire,
                    l.prenom_locataire,
                    t.tarif,
                    t.semaine_tarif,
                    t.annee_tarif,
                    s.libelle_saison
                FROM reservation r
                LEFT JOIN bien b ON r.id_bien = b.id_bien
                LEFT JOIN locataire l ON r.id_locataire = l.id_locataire
                LEFT JOIN tarif t ON r.id_tarif = t.id_tarif
                LEFT JOIN saison s ON t.id_saison = s.id_saison
                ORDER BY r.date_debut DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupère une réservation par ID
    public function getReservationById($id_reservation) {
        $sql = "SELECT 
                    r.*,
                    b.nom_bien,
                    l.nom_locataire,
                    l.prenom_locataire,
                    t.tarif,
                    t.semaine_tarif,
                    t.annee_tarif
                FROM reservation r
                LEFT JOIN bien b ON r.id_bien = b.id_bien
                LEFT JOIN locataire l ON r.id_locataire = l.id_locataire
                LEFT JOIN tarif t ON r.id_tarif = t.id_tarif
                WHERE r.id_reservations = :id_reservation";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_reservation' => $id_reservation]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Création d'une nouvelle réservation
    public function createReservation($date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif) {
        $sql = "INSERT INTO reservation 
                    (date_debut, date_fin, id_locataire, id_bien, id_tarif)
                VALUES
                    (:date_debut, :date_fin, :id_locataire, :id_bien, :id_tarif)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'date_debut' => $date_debut,
            'date_fin' => $date_fin,
            'id_locataire' => $id_locataire,
            'id_bien' => $id_bien,
            'id_tarif' => $id_tarif
        ]);

        return $this->pdo->lastInsertId();
    }

    // Mise à jour d'une réservation
    public function updateReservation($id_reservation, $date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif) {
        $sql = "UPDATE reservation SET
                    date_debut = :date_debut,
                    date_fin = :date_fin,
                    id_locataire = :id_locataire,
                    id_bien = :id_bien,
                    id_tarif = :id_tarif
                WHERE id_reservations = :id_reservation";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'date_debut' => $date_debut,
            'date_fin' => $date_fin,
            'id_locataire' => $id_locataire,
            'id_bien' => $id_bien,
            'id_tarif' => $id_tarif,
            'id_reservation' => $id_reservation
        ]);
    }

    // Suppression d'une réservation
    public function deleteReservation($id_reservation) {
        $stmt = $this->pdo->prepare("DELETE FROM reservation WHERE id_reservations = ?");
        return $stmt->execute([$id_reservation]);
    }

    // Recherche de biens pour autocomplétion
    public function searchBiens($query) {
        $sql = "SELECT 
                    b.id_bien,
                    b.nom_bien,
                    b.description_bien,
                    c.nom_commune,
                    tb.des_typebien
                FROM bien b
                LEFT JOIN commune c ON b.id_commune = c.id_commune
                LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
                WHERE b.nom_bien LIKE :query
                   OR b.description_bien LIKE :query
                   OR c.nom_commune LIKE :query
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['query' => '%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recherche de tarifs pour un bien spécifique
    public function searchTarifsByBien($id_bien, $query = '') {
        $sql = "SELECT 
                    t.id_tarif,
                    t.semaine_tarif,
                    t.annee_tarif,
                    t.tarif,
                    s.libelle_saison
                FROM tarif t
                LEFT JOIN saison s ON t.id_saison = s.id_saison
                WHERE t.id_bien = :id_bien";
        
        if (!empty($query)) {
            $sql .= " AND (t.semaine_tarif LIKE :query 
                      OR t.annee_tarif LIKE :query 
                      OR s.libelle_saison LIKE :query)";
        }
        
        $sql .= " ORDER BY t.annee_tarif DESC, t.semaine_tarif ASC LIMIT 20";

        $stmt = $this->pdo->prepare($sql);
        $params = ['id_bien' => $id_bien];
        if (!empty($query)) {
            $params['query'] = '%' . $query . '%';
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Vérifier les conflits de réservation
    public function checkConflict($id_bien, $date_debut, $date_fin, $id_reservation = null) {
        $sql = "SELECT COUNT(*) as count 
                FROM reservation 
                WHERE id_bien = :id_bien
                AND (
                    (date_debut <= :date_fin AND date_fin >= :date_debut)
                )";
        
        if ($id_reservation) {
            $sql .= " AND id_reservations != :id_reservation";
        }

        $stmt = $this->pdo->prepare($sql);
        $params = [
            'id_bien' => $id_bien,
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ];
        
        if ($id_reservation) {
            $params['id_reservation'] = $id_reservation;
        }

        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    // Récupérer tous les locataires
    public function getAllLocataires() {
        $sql = "SELECT id_locataire, nom_locataire, prenom_locataire 
                FROM locataire 
                ORDER BY nom_locataire, prenom_locataire";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recherche de locataires pour autocomplétion
    // Recherche de locataires pour autocomplétion
    public function searchLocataires($query) {
        $sql = "SELECT 
                    id_locataire,
                    nom_locataire,
                    prenom_locataire,
                    email_locataire,
                    tel_locataire
                FROM locataire
                WHERE LOWER(nom_locataire) LIKE LOWER(:query)
                   OR LOWER(prenom_locataire) LIKE LOWER(:query)
                   OR LOWER(email_locataire) LIKE LOWER(:query)
                   OR LOWER(tel_locataire) LIKE LOWER(:query)
                ORDER BY nom_locataire, prenom_locataire
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['query' => '%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>