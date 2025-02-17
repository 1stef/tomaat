<?php
declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InschrijvingRepository")
 */
class Inschrijving
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $toernooi_id;

    /**
     * @ORM\Column(type="integer", name="deelnemerA")
     */
    private int $deelnemerA;

    /**
     * @ORM\Column(type="integer", name = "deelnemerB", nullable=true)
     */
    private ?int $deelnemerB;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $categorie;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private ?string $cat_type;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $ranking_effectief;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $aantal;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $actief = true;

    /**
     * Constructor
     */ 
    public function __construct (int $toernooi_id, int $bondsnummer){
        $this->toernooi_id = $toernooi_id;
        $this->deelnemerA = $bondsnummer;
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

    public function getDeelnemerA(): ?int
    {
        return $this->deelnemerA;
    }

    public function setDeelnemerA(?int $deelnemerA): self
    {
        $this->deelnemerA = $deelnemerA;

        return $this;
    }

    public function getDeelnemerB(): ?int
    {
        return $this->deelnemerB;
    }

    public function setDeelnemerB(?int $deelnemerB): self
    {
        $this->deelnemerB = $deelnemerB;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getRankingEffectief(): ?float
    {
        return $this->ranking_effectief;
    }

    public function setRankingEffectief(?float $ranking_effectief): self
    {
        $this->ranking_effectief = $ranking_effectief;

        return $this;
    }

    public function getAantal(): ?int
    {
        return $this->aantal;
    }

    public function setAantal(?int $aantal): self
    {
        $this->aantal = $aantal;

        return $this;
    }

    public function getCatType(): ?string
    {
        return $this->cat_type;
    }

    public function setCatType(?string $cat_type): self
    {
        $this->cat_type = $cat_type;

        return $this;
    }

    public function getActief(): bool
    {
        return $this->actief;
    }

    public function setActief(bool $actief): self
    {
        $this->actief = $actief;

        return $this;
    }
}
