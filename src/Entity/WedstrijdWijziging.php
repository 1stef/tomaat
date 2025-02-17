<?php
declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WedstrijdWijzigingRepository::class)
 */
class WedstrijdWijziging
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
    private int $indiener;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $speler1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $partner1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $speler2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $partner2;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private string $wijziging_status;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private string $actie;

    /**
     * @ORM\Column(type="integer", length=45)
     */
    private int $wedstrijd_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $tijdslot_oud;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $tijdslot_nieuw;

        /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $herplan_optie_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $cat_type;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $indiener_veranderd;

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

    public function getIndiener(): ?int
    {
        return $this->indiener;
    }

    public function setIndiener(int $indiener): self
    {
        $this->indiener = $indiener;

        return $this;
    }

    public function getSpeler1(): ?int
    {
        return $this->speler1;
    }

    public function setSpeler1(?int $speler1): self
    {
        $this->speler1 = $speler1;

        return $this;
    }

    public function getPartner1(): ?int
    {
        return $this->partner1;
    }

    public function setPartner1(?int $partner1): self
    {
        $this->partner1 = $partner1;

        return $this;
    }

    public function getSpeler2(): ?int
    {
        return $this->speler2;
    }

    public function setSpeler2(?int $speler2): self
    {
        $this->speler2 = $speler2;

        return $this;
    }

    public function getPartner2(): ?int
    {
        return $this->partner2;
    }

    public function setPartner2(?int $partner2): self
    {
        $this->partner2 = $partner2;

        return $this;
    }

    public function getWijzigingStatus(): string
    {
        return $this->wijziging_status;
    }

    public function setWijzigingStatus(string $wijziging_status): self
    {
        $this->wijziging_status = $wijziging_status;

        return $this;
    }

    public function getActie(): string
    {
        return $this->actie;
    }

    public function setActie(string $actie): self
    {
        $this->actie = $actie;

        return $this;
    }

    public function getWedstrijdId(): int
    {
        return $this->wedstrijd_id;
    }

    public function setWedstrijdId(int $wedstrijd_id): self
    {
        $this->wedstrijd_id = $wedstrijd_id;

        return $this;
    }

    public function getTijdslotOud(): ?int
    {
        return $this->tijdslot_oud;
    }

    public function setTijdslotOud(?int $tijdslot_oud): self
    {
        $this->tijdslot_oud = $tijdslot_oud;

        return $this;
    }

    public function getTijdslotNieuw(): ?int
    {
        return $this->tijdslot_nieuw;
    }

    public function setTijdslotNieuw(?int $tijdslot_nieuw): self
    {
        $this->tijdslot_nieuw = $tijdslot_nieuw;

        return $this;
    }

    public function getHerplanOptieId(): ?int
    {
        return $this->herplan_optie_id;
    }

    public function setHerplanOptieId(?int $herplan_optie_id): self
    {
        $this->herplan_optie_id = $herplan_optie_id;

        return $this;
    }

    public function getCatType(): string
    {
        return $this->cat_type;
    }

    public function setCatType(string $cat_type): self
    {
        $this->cat_type = $cat_type;

        return $this;
    }

    public function hasUser(int $user_id): bool
    {
        return (($user_id == $this->speler1) || ($user_id == $this->partner1) ||
                ($user_id == $this->speler2) || ($user_id == $this->partner2));
    }

    public function getIndienerVeranderd(): ?bool
    {
        return $this->indiener_veranderd;
    }

    public function setIndienerVeranderd(bool $indiener_veranderd): self
    {
        $this->indiener_veranderd = $indiener_veranderd;

        return $this;
    }
}
