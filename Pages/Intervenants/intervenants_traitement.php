<?php

require_once 'intervenants_class.php';

class IntervenantManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @return Intervenant[]
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM intervenants ORDER BY nom_intervenant, prenom_intervenant");
        $liste = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $liste[] = new Intervenant(
                (int)$row['id_intervenant'],
                $row['nom_intervenant'],
                $row['prenom_intervenant']
            );
        }

        return $liste;
    }

    public function getById(int $id): ?Intervenant
    {
        $stmt = $this->db->prepare("SELECT * FROM intervenants WHERE id_intervenant = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        return new Intervenant(
            (int)$row['id_intervenant'],
            $row['nom_intervenant'],
            $row['prenom_intervenant']
        );
    }

    public function add(Intervenant $intervenant): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO intervenants (nom_intervenant, prenom_intervenant)
            VALUES (:nom, :prenom)
        ");
        $stmt->bindValue(':nom', $intervenant->getNomIntervenant());
        $stmt->bindValue(':prenom', $intervenant->getPrenomIntervenant());

        $ok = $stmt->execute();
        if ($ok) {
            $intervenant->setIdIntervenant((int)$this->db->lastInsertId());
        }
        return $ok;
    }

    public function update(Intervenant $intervenant): bool
    {
        $stmt = $this->db->prepare("
            UPDATE intervenants
            SET nom_intervenant = :nom,
                prenom_intervenant = :prenom
            WHERE id_intervenant = :id
        ");
        $stmt->bindValue(':nom', $intervenant->getNomIntervenant());
        $stmt->bindValue(':prenom', $intervenant->getPrenomIntervenant());
        $stmt->bindValue(':id', $intervenant->getIdIntervenant(), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM intervenants WHERE id_intervenant = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}