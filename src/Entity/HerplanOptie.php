<?php
declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HerplanOptieRepository::class)
 */
class HerplanOptie
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $toernooi_id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $wedstrijd_wijziging_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $tijdslot;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $speler_1_akkoord;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $partner_1_akkoord;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $speler_2_akkoord;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $partner_2_akkoord;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $naam_met_wedstrijd;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $user_id_met_wedstrijd;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $dagnummer;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $starttijd;

    public function __construct (){
        $this->speler_1_akkoord = false;
        $this->partner_1_akkoord = false;
        $this->speler_2_akkoord = false;
        $this->partner_2_akkoord = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getWedstrijdWijzigingId(): ?int
    {
        return $this->wedstrijd_wijziging_id;
    }

    public function setWedstrijdWijzigingId(?int $wedstrijd_wijziging_id): self
    {
        $this->wedstrijd_wijziging_id = $wedstrijd_wijziging_id;

        return $this;
    }

    public function getTijdslot(): ?int
    {
        return $this->tijdslot;
    }

    public function setTijdslot(?int $tijdslot): self
    {
        $this->tijdslot = $tijdslot;

        return $this;
    }

    public function getSpeler1Akkoord(): ?bool
    {
        return $this->speler_1_akkoord;
    }

    public function setSpeler1Akkoord(?bool $speler_1_akkoord): self
    {
        $this->speler_1_akkoord = $speler_1_akkoord;

        return $this;
    }

    public function getPartner1Akkoord(): ?bool
    {
        return $this->partner_1_akkoord;
    }

    public function setPartner1Akkoord(?bool $partner_1_akkoord): self
    {
        $this->partner_1_akkoord = $partner_1_akkoord;

        return $this;
    }

    public function getSpeler2Akkoord(): ?bool
    {
        return $this->speler_2_akkoord;
    }

    public function setSpeler2Akkoord(?bool $speler_2_akkoord): self
    {
        $this->speler_2_akkoord = $speler_2_akkoord;

        return $this;
    }

    public function getPartner2Akkoord(): ?bool
    {
        return $this->partner_2_akkoord;
    }

    public function setPartner2Akkoord(?bool $partner_2_akkoord): self
    {
        $this->partner_2_akkoord = $partner_2_akkoord;

        return $this;
    }

    public function getNaamMetWedstrijd(): ?string
    {
        return $this->naam_met_wedstrijd;
    }

    public function setNaamMetWedstrijd(?string $naam_met_wedstrijd): self
    {
        $this->naam_met_wedstrijd = $naam_met_wedstrijd;

        return $this;
    }

    public function getUserIdMetWedstrijd(): ?int
    {
        return $this->user_id_met_wedstrijd;
    }

    public function setUserIdMetWedstrijd(?int $user_id_met_wedstrijd): self
    {
        $this->user_id_met_wedstrijd = $user_id_met_wedstrijd;

        return $this;
    }

    public function getDagnummer(): ?int
    {
        return $this->dagnummer;
    }

    public function setDagnummer(?int $dagnummer): self
    {
        $this->dagnummer = $dagnummer;

        return $this;
    }

    public function getStarttijd(): ?string
    {
        return $this->starttijd;
    }

    public function setStarttijd(?string $starttijd): self
    {
        $this->starttijd = $starttijd;

        return $this;
    }
}
