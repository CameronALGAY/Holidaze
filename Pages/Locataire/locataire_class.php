<?php
class Locataire
{
    private $idLocataire;
    private $nomLocataire;
    private $prenomLocataire;
    private $dateNaissanceLocataire;
    private $emailLocataire;
    private $rueLocataire;
    private $passwordLocataire;
    private $telLocataire;
    private $compLocataire;
    private $idCommune;
    private $raisonSociale;
    private $siret;

    // Attributs supplémentaires pour les jointures
    private $nomCommune;

    function __construct($idLocataire, $nomLocataire, $prenomLocataire, $dateNaissanceLocataire, $emailLocataire, $rueLocataire, $passwordLocataire, $telLocataire, $compLocataire, $idCommune, $raisonSociale, $siret)
    {
        $this->idLocataire = $idLocataire;
        $this->nomLocataire = $nomLocataire;
        $this->prenomLocataire = $prenomLocataire;
        $this->dateNaissanceLocataire = $dateNaissanceLocataire;
        $this->emailLocataire = $emailLocataire;
        $this->rueLocataire = $rueLocataire;
        $this->passwordLocataire = $passwordLocataire;
        $this->telLocataire = $telLocataire;
        $this->compLocataire = $compLocataire;
        $this->idCommune = $idCommune;
        $this->raisonSociale = $raisonSociale;
        $this->siret = $siret;
    }

    // GETTERS & SETTERS
    public function getIdLocataire() { return $this->idLocataire; }

    public function getNomLocataire() { return $this->nomLocataire; }
    public function setNomLocataire($nomLocataire) { $this->nomLocataire = $nomLocataire; }

    public function getPrenomLocataire() { return $this->prenomLocataire; }
    public function setPrenomLocataire($prenomLocataire) { $this->prenomLocataire = $prenomLocataire; }

    public function getDateNaissanceLocataire() { return $this->dateNaissanceLocataire; }
    public function setDateNaissanceLocataire($dateNaissanceLocataire) { $this->dateNaissanceLocataire = $dateNaissanceLocataire; }

    public function getEmailLocataire() { return $this->emailLocataire; }
    public function setEmailLocataire($emailLocataire) { $this->emailLocataire = $emailLocataire; }

    public function getRueLocataire() { return $this->rueLocataire; }
    public function setRueLocataire($rueLocataire) { $this->rueLocataire = $rueLocataire; }

    public function getPasswordLocataire() { return $this->passwordLocataire; }
    public function setPasswordLocataire($passwordLocataire) { $this->passwordLocataire = $passwordLocataire; }

    public function getTelLocataire() { return $this->telLocataire; }
    public function setTelLocataire($telLocataire) { $this->telLocataire = $telLocataire; }

    public function getCompLocataire() { return $this->compLocataire; }
    public function setCompLocataire($compLocataire) { $this->compLocataire = $compLocataire; }

    public function getIdCommune() { return $this->idCommune; }
    public function setIdCommune($idCommune) { $this->idCommune = $idCommune; }

    public function getRaisonSociale() { return $this->raisonSociale; }
    public function setRaisonSociale($raisonSociale) { $this->raisonSociale = $raisonSociale; }

    public function getSiret() { return $this->siret; }
    public function setSiret($siret) { $this->siret = $siret; }

    public function getNomCommune() { return $this->nomCommune; }
    public function setNomCommune($nomCommune) { $this->nomCommune = $nomCommune; }

    // --- CRUD ---
    public function InsertLocataire()
    {
        global $con;

        $data = [
            ':nl' => $this->nomLocataire,
            ':pl' => $this->prenomLocataire,
            ':dnl' => $this->dateNaissanceLocataire,
            ':el' => $this->emailLocataire,
            ':rl' => $this->rueLocataire,
            ':pw' => $this->passwordLocataire,
            ':tl' => $this->telLocataire,
            ':cl' => $this->compLocataire,
            ':idc' => $this->idCommune,
            ':rs' => $this->raisonSociale,
            ':siret' => $this->siret
        ];

        $sql = "INSERT INTO locataire (id_locataire, nom_locataire, prenom_locataire, dna_locataire, email_locataire, rue_locataire, pass_locataire, tel_locataire, comp_locataire, id_commune, raison_social, siret) 
                VALUES (null, :nl, :pl, :dnl, :el, :rl, :pw, :tl, :cl, :idc, :rs, :siret)";
        $stmt = $con->prepare($sql);

        if ($stmt->execute($data)) {
            echo "Locataire inséré avec succès";
            return $con->lastInsertId();
        } else {
            echo implode(", ", $stmt->errorInfo());
            return false;
        }
    }

    public function LocataireAll() 
    {
        global $con;

        $sql = "SELECT l.id_locataire, l.nom_locataire, l.prenom_locataire, l.dna_locataire, l.email_locataire, l.rue_locataire, 
                       l.pass_locataire, l.tel_locataire, l.comp_locataire, l.id_commune, l.raison_social, l.siret, c.ville AS nomcommune
                FROM locataire l
                JOIN commune c ON l.id_commune = c.idcommune";
                
        $req = $con->query($sql);
        $locataires = [];

        foreach ($req->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $locataire = new Locataire(
                $row['id_locataire'],
                $row['nom_locataire'],
                $row['prenom_locataire'],
                $row['dna_locataire'],
                $row['email_locataire'],
                $row['rue_locataire'],
                $row['pass_locataire'],
                $row['tel_locataire'],
                $row['comp_locataire'],
                $row['id_commune'],
                $row['raison_social'],
                $row['siret']
            );
            $locataire->nomCommune = $row['nomcommune'];
            $locataires[] = $locataire;
        }

        return $locataires;
    }

    public function DeleteLocataire($id)
    {
        global $con;
        $data = [':id' => $id];
        $sql = "DELETE FROM locataire WHERE id_locataire = :id";
        $stmt = $con->prepare($sql);

        if ($stmt->execute($data)) {
            echo "Suppression réussie";
            return true;
        } else {
            echo "Erreur lors de la suppression : " . implode(", ", $stmt->errorInfo());
            return false;
        }
    }

    public function UpdateLocataire()
    {
        global $con;
        $data = [
            ':id' => $this->idLocataire,
            ':nl' => $this->nomLocataire,
            ':pl' => $this->prenomLocataire,
            ':dnl' => $this->dateNaissanceLocataire,
            ':el' => $this->emailLocataire,
            ':rl' => $this->rueLocataire,
            ':pw' => $this->passwordLocataire,
            ':tl' => $this->telLocataire,
            ':cl' => $this->compLocataire,
            ':idc' => $this->idCommune,
            ':rs' => $this->raisonSociale,
            ':siret' => $this->siret
        ];

        $sql = "UPDATE locataire	
                SET nom_locataire = :nl, prenom_locataire = :pl, dna_locataire = :dnl, email_locataire = :el, 
                    rue_locataire = :rl, pass_locataire = :pw, tel_locataire = :tl, comp_locataire = :cl, 
                    id_commune = :idc, raison_social = :rs, siret = :siret
                WHERE id_locataire = :id;";
        $stmt = $con->prepare($sql);

        if ($stmt->execute($data)) {
            echo "Modification réussie";
            return true;
        } else {
            echo implode(", ", $stmt->errorInfo());
            return false;
        }
    }
}
?>
