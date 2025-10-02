<?php

class Communes
{
    private $id_commune;
    private $nom_commune;

    public function __construct($id_commune, $nom_commune)
    {
        $this->id_commune = $id_commune;
        $this->nom_commune = $nom_commune;
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
}