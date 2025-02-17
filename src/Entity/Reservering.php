<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReserveringRepository")
 */
class Reservering
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="integer")
     */
    public $tijdslot_id;

    /**
     * @ORM\Column(type="integer")
     */
    public $wedstrijd_id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $speler_1_bevestigd;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $partner_1_bevestigd;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $speler_2_bevestigd;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    public $partner_2_bevestigd;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $res_status;

    /**
     * @ORM\Column(type="integer")
     */
    private $toernooi_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $oude_tijdslot;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTijdslotId(): ?int
    {
        return $this->tijdslot_id;
    }

    public function setTijdslotId(int $tijdslot_id): self
    {
        $this->tijdslot_id = $tijdslot_id;

        return $this;
    }

    public function getWedstrijdId(): ?int
    {
        return $this->wedstrijd_id;
    }

    public function setWedstrijdId(int $wedstrijd_id): self
    {
        $this->wedstrijd_id = $wedstrijd_id;

        return $this;
    }

    public function getSpeler1Bevestigd(): ?bool
    {
        return $this->speler_1_bevestigd;
    }

    public function setSpeler1Bevestigd(?bool $speler_1_bevestigd): self
    {
        $this->speler_1_bevestigd = $speler_1_bevestigd;

        return $this;
    }

    public function getPartner1Bevestigd(): ?bool
    {
        return $this->partner_1_bevestigd;
    }

    public function setPartner1Bevestigd(?bool $partner_1_bevestigd): self
    {
        $this->partner_1_bevestigd = $partner_1_bevestigd;

        return $this;
    }

    public function getSpeler2Bevestigd(): ?bool
    {
        return $this->speler_2_bevestigd;
    }

    public function setSpeler2Bevestigd(?bool $speler_2_bevestigd): self
    {
        $this->speler_2_bevestigd = $speler_2_bevestigd;

        return $this;
    }

    public function getPartner2Bevestigd(): ?bool
    {
        return $this->partner_2_bevestigd;
    }

    public function setPartner2Bevestigd(?bool $partner_2_bevestigd): self
    {
        $this->partner_2_bevestigd = $partner_2_bevestigd;

        return $this;
    }

    public function getResStatus(): ?string
    {
        return $this->res_status;
    }

    public function setResStatus(string $res_status): self
    {
        $this->res_status = $res_status;

        return $this;
    }

    public function getToernooiId(): ?int
    {
        return $this->toernooi_id;
    }

    public function setToernooiId(int $toernooi_id): self
    {
        $this->toernooi_id = $toernooi_id;

        return $this;
    }

    public function getOudeTijdslot(): ?int
    {
        return $this->oude_tijdslot;
    }

    public function setOudeTijdslot(int $oude_tijdslot): self
    {
        $this->oude_tijdslot = $oude_tijdslot;

        return $this;
    }

}
