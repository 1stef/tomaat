<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CombinationsRepository::class)
 */
class Combinations
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
     * @ORM\Column(type="string", length=45)
     */
    private string $categorie;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $deelnemer1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $deelnemer2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $plan_ronde;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $tijdslot;

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

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getDeelnemer1(): ?int
    {
        return $this->deelnemer1;
    }

    public function setDeelnemer1(?int $deelnemer1): self
    {
        $this->deelnemer1 = $deelnemer1;

        return $this;
    }

    public function getDeelnemer2(): ?int
    {
        return $this->deelnemer2;
    }

    public function setDeelnemer2(?int $deelnemer2): self
    {
        $this->deelnemer2 = $deelnemer2;

        return $this;
    }

    public function getPlanRonde(): ?int
    {
        return $this->plan_ronde;
    }

    public function setPlanRonde(?int $plan_ronde): self
    {
        $this->plan_ronde = $plan_ronde;

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
}
