<?php

include_once '../Bien/bien_class.php';
include_once '../Saison/saison_class.php';

class Tarifs
{
    private $id_tarif;
    private $semaine_tarif;
    private $annee_tarif;
    private $tarif;
    private $id_bien;
    private $id_saison;
    private $pdo;

    public function __construct($pdo, $id_tarif = null, $semaine_tarif = null, $annee_tarif = null, $tarif = null, $id_bien = null, $id_saison = null)
    {
        $this->pdo = $pdo;
        $this->id_tarif = $id_tarif;
        $this->semaine_tarif = $semaine_tarif;
        $this->annee_tarif = $annee_tarif;
        $this->tarif = $tarif;
        $this->id_bien = $id_bien;
        $this->id_saison = $id_saison;
    }

    // --- Getters ---
    public function getIdTarif() { return $this->id_tarif; }
    public function getSemaineTarif() { return $this->semaine_tarif; }
    public function getAnneeTarif() { return $this->annee_tarif; }
    public function getTarif() { return $this->tarif; }
    public function getIdBien() { return $this->id_bien; }
    public function getIdSaison() { return $this->id_saison; }

    // --- Setters ---
    public function setSemaineTarif($semaine_tarif) { $this->semaine_tarif = $semaine_tarif; }
    public function setAnneeTarif($annee_tarif) { $this->annee_tarif = $annee_tarif; }
    public function setTarif($tarif) { $this->tarif = $tarif; }
    public function setIdBien($id_bien) { $this->id_bien = $id_bien; }
    public function setIdSaison($id_saison) { $this->id_saison = $id_saison; }

    // --- CREATE ---
    public function create() {
    $sql = "INSERT INTO tarif (semaine_tarif, annee_tarif, tarif, id_bien, id_saison)
            VALUES (:semaine, :annee, :tarif, :id_bien, :id_saison)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([
        ':semaine' => $this->semaine_tarif,
        ':annee' => $this->annee_tarif,
        ':tarif' => $this->tarif,
        ':id_bien' => $this->id_bien,
        ':id_saison' => $this->id_saison
    ]);
}


    // --- READ (un tarif)
    public function read($id)
    {
        $sql = "SELECT * FROM tarif WHERE id_tarif = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- READ ALL avec noms des biens et saisons
    public function readAll()
    {
        $sql = "SELECT 
                    t.id_tarif,
                    t.semaine_tarif,
                    t.annee_tarif,
                    t.tarif,
                    b.nom_bien AS nomBien,
                    s.libelle_saison
                FROM tarif t
                INNER JOIN bien b ON t.id_bien = b.id_bien
                INNER JOIN saison s ON t.id_saison = s.id_saison
                ORDER BY t.annee_tarif DESC, t.semaine_tarif ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- UPDATE ---
    public function update()
    {
        $sql = "UPDATE tarif 
                SET semaine_tarif = :semaine, annee_tarif = :annee, tarif = :tarif, id_bien = :idBien, id_saison = :idSaison 
                WHERE id_tarif = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':semaine'  => $this->semaine_tarif,
            ':annee'    => $this->annee_tarif,
            ':tarif'    => $this->tarif,
            ':idBien'   => $this->id_bien,
            ':idSaison' => $this->id_saison,
            ':id'       => $this->id_tarif
        ]);
    }

    // --- DELETE ---
    public function delete($id)
    {
        $sql = "DELETE FROM tarif WHERE id_tarif = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>
