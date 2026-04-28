<?php

class Intervenant
{
    private int $id_intervenant;
    private string $nom_intervenant;
    private string $prenom_intervenant;

    public function __construct(
        ?int $id_intervenant,
        string $nom_intervenant,
        string $prenom_intervenant
    ) {
        $this->id_intervenant = $id_intervenant ?? 0;
        $this->nom_intervenant = $nom_intervenant;
        $this->prenom_intervenant = $prenom_intervenant;
    }

    public function getIdIntervenant(): int
    {
        return $this->id_intervenant;
    }

    public function setIdIntervenant(int $id_intervenant): void
    {
        $this->id_intervenant = $id_intervenant;
    }

    public function getNomIntervenant(): string
    {
        return $this->nom_intervenant;
    }

    public function setNomIntervenant(string $nom_intervenant): void
    {
        $this->nom_intervenant = $nom_intervenant;
    }

    public function getPrenomIntervenant(): string
    {
        return $this->prenom_intervenant;
    }

    public function setPrenomIntervenant(string $prenom_intervenant): void
    {
        $this->prenom_intervenant = $prenom_intervenant;
    }
}