<?php
class Saison
{
    private $id_saison;
    private $libelle_saison;

    public function __construct($id_saison = null, $libelle_saison = "")
    {
        $this->id_saison = $id_saison;
        $this->libelle_saison = $libelle_saison;
    }

    // --- Getters ---
    public function getIdSaison() { return $this->id_saison; }
    public function getLibelleSaison() { return $this->libelle_saison; }

    // --- Setters ---
    public function setLibelleSaison($libelle_saison) { $this->libelle_saison = $libelle_saison; }
}
?>
