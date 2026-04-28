<?php

class Menage
{
    private int $id_menage;
    private int $id_reservations;
    private int $id_intervenant;
    private string $date_menage;
    private string $statut;
    private ?string $commentaire;

    public function __construct(
        int $id_reservations,
        int $id_intervenant,
        string $date_menage,
        string $statut = 'a_faire',
        ?string $commentaire = null,
        int $id_menage = 0
    ) {
        $this->id_menage = $id_menage;
        $this->id_reservations = $id_reservations;
        $this->id_intervenant = $id_intervenant;
        $this->date_menage = $date_menage;
        $this->statut = $statut;
        $this->commentaire = $commentaire;
    }

    // Getters
    public function getIdMenage(): int
    {
        return $this->id_menage;
    }

    public function getIdReservations(): int
    {
        return $this->id_reservations;
    }

    public function getIdIntervenant(): int
    {
        return $this->id_intervenant;
    }

    public function getDateMenage(): string
    {
        return $this->date_menage;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    // Setters
    public function setIdMenage(int $id_menage): void
    {
        $this->id_menage = $id_menage;
    }

    public function setIdReservations(int $id_reservations): void
    {
        $this->id_reservations = $id_reservations;
    }

    public function setIdIntervenant(int $id_intervenant): void
    {
        $this->id_intervenant = $id_intervenant;
    }

    public function setDateMenage(string $date_menage): void
    {
        $this->date_menage = $date_menage;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function setCommentaire(?string $commentaire): void
    {
        $this->commentaire = $commentaire;
    }
}