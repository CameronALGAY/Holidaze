<?php

include_once '../Communes/communes_class.php';
include_once '../TypeBien/typebien_class.php';
class Bien
{
    private $idBien;
    private $nomBien;
    private $rueBien;
    private $compBien;
    private $superficieBien;
    private $descriptionBien;
    private $animauxBien;
    private $nbCouchagesBien;
    private $id_commune;
    private $id_typebien;

    // Attributs supplémentaires pour les jointures
    private $nomProprietaire;
    private $prenomProprietaire;

    function __construct($idBien, $nomBien, $descriptionBien, $rueBien, $compBien, $superficieBien, $animauxBien, $nbCouchagesBien, $id_commune, $id_typebien)

    {
        $this->idBien = $idBien;
        $this->nomBien = $nomBien;
        $this->rueBien = $rueBien;
        $this->compBien = $compBien;
        $this->superficieBien = $superficieBien;
        $this->descriptionBien = $descriptionBien;
        $this->animauxBien = $animauxBien;
        $this->nbCouchagesBien = $nbCouchagesBien;
        $this->id_commune = $id_commune;
        $this->id_typebien = $id_typebien;
    }

    // GETTERS & SETTERS
    public function getIdBien()
    {
        return $this->idBien;
    }
    public function getNomBien()
    {
        return $this->nomBien;
    }
    public function getRueBien()
    {
        return $this->rueBien;
    }
    public function getCompBien()
    {
        return $this->compBien;
    }
    public function getSuperficieBien()
    {
        return $this->superficieBien;
    }
    public function getDescriptionBien()
    {
        return $this->descriptionBien;
    }
    public function getAnimauxBien()
    {
        return $this->animauxBien;
    }
    public function getNbCouchagesBien()
    {
        return $this->nbCouchagesBien;
    }
    public function getIdCommune()
    {
        return $this->id_commune;
    }
    public function getIdTypeBien()
    {
        return $this->id_typebien;
    }

    public function setNomBien($nomBien)
    {
        $this->nomBien = $nomBien;
    }
    public function setRueBien($rueBien)
    {
        $this->rueBien = $rueBien;
    }
    public function setCompBien($compBien)
    {
        $this->compBien = $compBien;
    }
    public function setSuperficieBien($superficieBien)
    {
        $this->superficieBien = $superficieBien;
    }
    public function setDescriptionBien($descriptionBien)
    {
        $this->descriptionBien = $descriptionBien;
    }
    public function setAnimauxBien($animauxBien)
    {
        $this->animauxBien = $animauxBien;
    }
    public function setNbCouchagesBien($nbCouchagesBien)
    {
        $this->nbCouchagesBien = $nbCouchagesBien;
    }
    
}