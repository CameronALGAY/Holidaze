<?php

class Communes
{
    private $id_commune;
    private $nom_commune;
    private $cp_commune;
    private $gps_commune;

    public function __construct($id_commune, $nom_commune, $cp_commune, $gps_commune)
    {
        $this->id_commune = $id_commune;
        $this->nom_commune = $nom_commune;
        $this->cp_commune = $cp_commune;
        $this->gps_commune = $gps_commune;
    }

    // Getters
    public function getIdCommune()
    {
        return $this->id_commune;
    }

    public function getNomCommune()
    {
        return $this->nom_commune;
    }

    public function getCpCommune()
    {
        return $this->cp_commune;
    }

    public function getGpsCommune()
    {
        return $this->gps_commune;
    }

    // Setters

    public function setNomCommune($nom_commune)
    {
        $this->nom_commune = $nom_commune;
    }
    public function setCpCommune($cp_commune)
    {
        $this->cp_commune = $cp_commune;
    }
    public function setGpsCommune($gps_commune)
    {
        $this->gps_commune = $gps_commune;
    }
}