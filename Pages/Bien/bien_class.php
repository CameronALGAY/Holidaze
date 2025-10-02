<?php

class Bien
{
    private $idBien;
    private $nomBien;
    private $descriptionBien;
    private $rueBien;
    private $codePostalBien;
    private $villeBien;
    private $prixBien;
    private $surfaceBien;
    private $nbPiecesBien;
    private $typeBien;
    private $disponibiliteBien;
    private $idProprietaire;

    // Attributs supplémentaires pour les jointures
    private $nomProprietaire;
    private $prenomProprietaire;

    function __construct($idBien, $titreBien, $descriptionBien, $adresseBien, $codePostalBien, $villeBien, $prixBien, $surfaceBien, $nbPiecesBien, $typeBien, $disponibiliteBien, $idProprietaire, $nomProprietaire, $prenomProprietaire)
    {
        $this->idBien = $idBien;
        $this->titreBien = $titreBien;
        $this->descriptionBien = $descriptionBien;
        $this->adresseBien = $adresseBien;
        $this->codePostalBien = $codePostalBien;
        $this->villeBien = $villeBien;
        $this->prixBien = $prixBien;
        $this->surfaceBien = $surfaceBien;
        $this->nbPiecesBien = $nbPiecesBien;
        $this->typeBien = $typeBien;
        $this->disponibiliteBien = $disponibiliteBien;
        $this->idProprietaire = $idProprietaire;
        $this->nomProprietaire = $nomProprietaire;
        $this->prenomProprietaire = $prenomProprietaire;
    }

    // GETTERS & SETTERS
    public function getIdBien() { return $this->idBien; }

    public function getTitreBien() { return $this->nomBien; }
    public function setTitreBien($nomBien) { $this->nomBien = $nomBien; }

    public function getDescriptionBien() { return $this->descriptionBien; }
    public function setDescriptionBien($descriptionBien) { $this->descriptionBien = $descriptionBien; }

    public function getAdresseBien() { return $this->rueBien; }
    public function setAdresseBien($rueBien) { $this->rueBien = $rueBien; }

    public function getCodePostalBien() { return $this->codePostalBien; }
    public function setCodePostalBien($codePostalBien) { $this->codePostalBien = $codePostalBien; }
}