<?php

include_once '../Biens/biens_class.php';
include_once '../Saisons/saisons_class.php';

class Tarifs
{
    private $id_tarif;
    private $semaine_tarif;
    private $annee_tarif;
    private $tarif;
    private $id_saison;
    private $idBien;
    private $pdo;

    public function __construct($pdo, $id_tarif, $semaine_tarif, $annee_tarif, $tarif, $idBien, $id_saison)
    {
        $this->pdo = $pdo;
        $this->id_tarif = $id_tarif;
        $this->semaine_tarif = $semaine_tarif;
        $this->annee_tarif = $annee_tarif;
        $this->tarif = $tarif;
        $this->idBien = $idBien;
        $this->id_saison = $id_saison;
    }

    // Getters
    public function getIdTarif()
    {
        return $this->id_tarif;
    }
    public function getSemaineTarif()
    {
        return $this->semaine_tarif;
    }
    public function getAnneeTarif()
    {
        return $this->annee_tarif;
    }
    public function getTarif()
    {
        return $this->tarif;
    }
    public function getIdBiens()
    {
        return $this->idBien;
    }
    public function getIdSaison()
    {
        return $this->id_saison;
    }

    // Setters
    public function setSemaineTarif($semaine_tarif)
    {
        $this->semaine_tarif = $semaine_tarif;
    }
    public function setAnneeTarif($annee_tarif)
    {
        $this->annee_tarif = $annee_tarif;
    }
    public function setTarif($tarif)
    {
        $this->tarif = $tarif;
    }

    

    // 🔹 CREATE
    public function create()
    {
        $sql = "INSERT INTO tarifs (semaine_tarif, annee_tarif, tarif, idBien, id_saison) 
                VALUES (:semaine, :annee, :tarif, :idBien, :idSaison)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':semaine' => $this->semaine_tarif,
            ':annee'   => $this->annee_tarif,
            ':tarif'   => $this->tarif,
            ':idBien'  => $this->idBien,
            ':idSaison'=> $this->id_saison
        ]);
        $this->id_tarif = $this->pdo->lastInsertId();
        return $this->id_tarif;
    }

    // 🔹 READ (un seul enregistrement)
    public function read($id)
    {
        $sql = "SELECT * FROM tarifs WHERE id_tarif = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 READ ALL
    public function readAll()
    {
        $sql = "SELECT * FROM tarifs";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 UPDATE
    public function update()
    {
        $sql = "UPDATE tarifs 
                SET semaine_tarif = :semaine, annee_tarif = :annee, tarif = :tarif, idBien = :idBien, id_saison = :idSaison 
                WHERE id_tarif = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':semaine' => $this->semaine_tarif,
            ':annee'   => $this->annee_tarif,
            ':tarif'   => $this->tarif,
            ':idBien'  => $this->idBien,
            ':idSaison'=> $this->id_saison,
            ':id'      => $this->id_tarif
        ]);
    }

    // 🔹 DELETE
    public function delete($id)
    {
        $sql = "DELETE FROM tarifs WHERE id_tarif = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}