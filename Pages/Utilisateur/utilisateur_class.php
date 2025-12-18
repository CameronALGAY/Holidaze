<?php
class Utilisateur
{
    private $id_utilisateur;
    private $email;
    private $mot_de_passe;
    private $nom;
    private $prenom;
    private $role;
    private $tel;
    private $photo_profil;
    private $actif;
    private $date_inscription;

    public function __construct(
        $id_utilisateur = null,
        $email = "",
        $mot_de_passe = "",
        $nom = "",
        $prenom = "",
        $role = "user",
        $tel = "",
        $photo_profil = "",
        $actif = 1,
        $date_inscription = null
    ) {
        $this->id_utilisateur   = $id_utilisateur;
        $this->email            = $email;
        $this->mot_de_passe     = $mot_de_passe;
        $this->nom              = $nom;
        $this->prenom           = $prenom;
        $this->role             = $role;
        $this->tel              = $tel;
        $this->photo_profil     = $photo_profil;
        $this->actif            = $actif;
        $this->date_inscription = $date_inscription;
    }

    // --- Getters ---
    public function getId()           { return $this->id_utilisateur; }
    public function getEmail()         { return $this->email; }
    public function getNom()           { return $this->nom; }
    public function getPrenom()        { return $this->prenom; }
    public function getNomComplet()   { return trim($this->prenom . " " . $this->nom); }
    public function getRole()          { return $this->role; }
    public function getTel()           { return $this->tel; }
    public function getPhotoProfil()   { return $this->photo_profil ?: 'default-avatar.png'; }
    public function isActif()          { return $this->actif == 1; }
    public function getDateInscription() { return $this->date_inscription; }

    // --- Setters ---
    public function setEmail($email)           { $this->email = $email; }
    public function setNom($nom)               { $this->nom = $nom; }
    public function setPrenom($prenom)         { $this->prenom = $prenom; }
    public function setRole($role)             { $this->role = $role; }
    public function setTel($tel)               { $this->tel = $tel; }
    public function setPhotoProfil($photo)     { $this->photo_profil = $photo; }
    public function setActif($actif)           { $this->actif = $actif; }
}
?>