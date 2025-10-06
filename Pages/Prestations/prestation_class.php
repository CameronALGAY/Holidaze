<?php

class Prestation {
    private $id;
    private $libelle_prestation;

    public function __construct($id, $libelle_prestation) {
        $this->id = $id;
        $this->libelle_prestation = $libelle_prestation;
    }

    public function getId() {
        return $this->id;
    }

    public function getLibelle() {
        return $this->libelle_prestation;
    }

    public function setLibelle($libelle_prestation) {
        $this->libelle_prestation = $libelle_prestation;
    }
}   