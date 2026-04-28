<?php

require_once 'menages_class.php'; // contient la classe Menage

class MenageManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère tous les ménages
     * @return Menage[]
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM menage";
        $stmt = $this->db->query($sql);

        $menages = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $menage = new Menage(
                $row['id_reservations'],
                $row['id_intervenant'],
                $row['date_menage'],
                $row['statut'],
                $row['commentaire'] ?? null,
                (int)$row['id_menage']
            );
            $menages[] = $menage;
        }

        return $menages;
    }

    /**
     * Récupère un ménage par son id
     */
    public function getById(int $id): ?Menage
    {
        $sql = "SELECT * FROM menage WHERE id_menage = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Menage(
            $row['id_reservations'],
            $row['id_intervenant'],
            $row['date_menage'],
            $row['statut'],
            $row['commentaire'] ?? null,
            (int)$row['id_menage']
        );
    }

    /**
     * Ajoute un ménage en base
     */
    public function add(Menage $menage): bool
    {
        $sql = "INSERT INTO menage (id_reservations, id_intervenant, date_menage, statut, commentaire)
                VALUES (:id_reservations, :id_intervenant, :date_menage, :statut, :commentaire)";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':id_reservations', $menage->getIdReservations(), PDO::PARAM_INT);
        $stmt->bindValue(':id_intervenant', $menage->getIdIntervenant(), PDO::PARAM_INT);
        $stmt->bindValue(':date_menage', $menage->getDateMenage());
        $stmt->bindValue(':statut', $menage->getStatut());
        $stmt->bindValue(':commentaire', $menage->getCommentaire());

        $result = $stmt->execute();

        if ($result) {
            $menage->setIdMenage((int)$this->db->lastInsertId());
        }

        return $result;
    }

    /**
     * Met à jour un ménage
     */
    public function update(Menage $menage): bool
    {
        $sql = "UPDATE menage
                SET id_reservations = :id_reservations,
                    id_intervenant = :id_intervenant,
                    date_menage = :date_menage,
                    statut = :statut,
                    commentaire = :commentaire
                WHERE id_menage = :id_menage";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':id_reservations', $menage->getIdReservations(), PDO::PARAM_INT);
        $stmt->bindValue(':id_intervenant', $menage->getIdIntervenant(), PDO::PARAM_INT);
        $stmt->bindValue(':date_menage', $menage->getDateMenage());
        $stmt->bindValue(':statut', $menage->getStatut());
        $stmt->bindValue(':commentaire', $menage->getCommentaire());
        $stmt->bindValue(':id_menage', $menage->getIdMenage(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Supprime un ménage
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM menage WHERE id_menage = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Récupère tous les ménages d'une réservation
     * (pour respecter le 1,n entre reservation et menage)
     */
    public function getByReservation(int $id_reservations): array
    {
        $sql = "SELECT * FROM menage WHERE id_reservations = :id_reservations";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_reservations', $id_reservations, PDO::PARAM_INT);
        $stmt->execute();

        $menages = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $menage = new Menage(
                $row['id_reservations'],
                $row['id_intervenant'],
                $row['date_menage'],
                $row['statut'],
                $row['commentaire'] ?? null,
                (int)$row['id_menage']
            );
            $menages[] = $menage;
        }

        return $menages;
    }
}