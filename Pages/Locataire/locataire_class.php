<?php
require_once '../../includes/db.php';

class Locataire
{
    private $id_locataire;
    private $nom_locataire;
    private $prenom_locataire;
    private $dna_locataire;
    private $email_locataire;
    private $rue_locataire;
    private $pass_locataire;
    private $tel_locataire;
    private $comp_locataire;
    private $id_commune; // objet Commune
    private $raison_social;
    private $siret;

    public function __construct(
        $id_locataire = null,
        $nom_locataire = "",
        $prenom_locataire = "",
        $dna_locataire = "",
        $email_locataire = "",
        $rue_locataire = "",
        $pass_locataire = "",
        $tel_locataire = "",
        $comp_locataire = "",
        $id_commune = null,
        $raison_social = "",
        $siret = ""
    ) {
        $this->id_locataire = $id_locataire;
        $this->nom_locataire = $nom_locataire;
        $this->prenom_locataire = $prenom_locataire;
        $this->dna_locataire = $dna_locataire;
        $this->email_locataire = $email_locataire;
        $this->rue_locataire = $rue_locataire;
        $this->pass_locataire = $pass_locataire;
        $this->tel_locataire = $tel_locataire;
        $this->comp_locataire = $comp_locataire;
        $this->id_commune = $id_commune; // instance de Commune
        $this->raison_social = $raison_social;
        $this->siret = $siret;
    }

    // --- Getters ---
    public function getIdLocataire() { return $this->id_locataire; }
    public function getNomLocataire() { return $this->nom_locataire; }
    public function getPrenomLocataire() { return $this->prenom_locataire; }
    public function getDnaLocataire() { return $this->dna_locataire; }
    public function getEmailLocataire() { return $this->email_locataire; }
    public function getRueLocataire() { return $this->rue_locataire; }
    public function getPassLocataire() { return $this->pass_locataire; }
    public function getTelLocataire() { return $this->tel_locataire; }
    public function getCompLocataire() { return $this->comp_locataire; }
    public function getIdCommune() { return $this->id_commune; }
    public function getRaisonSocial() { return $this->raison_social; }
    public function getSiret() { return $this->siret; }

    // --- Setters ---
    public function setNomLocataire($val) { $this->nom_locataire = $val; }
    public function setPrenomLocataire($val) { $this->prenom_locataire = $val; }
    public function setDnaLocataire($val) { $this->dna_locataire = $val; }
    public function setEmailLocataire($val) { $this->email_locataire = $val; }
    public function setRueLocataire($val) { $this->rue_locataire = $val; }
    public function setPassLocataire($val) { $this->pass_locataire = $val; }
    public function setTelLocataire($val) { $this->tel_locataire = $val; }
    public function setCompLocataire($val) { $this->comp_locataire = $val; }
    public function setIdCommune($commune) { $this->id_commune = $commune; }
    public function setRaisonSocial($val) { $this->raison_social = $val; }
    public function setSiret($val) { $this->siret = $val; }
}
?>
