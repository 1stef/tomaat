<?php
declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TijdslotRepository")
 */
class Tijdslot
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
    private ?int $toernooi_id;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $dagnummer;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $baannummer;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $slotnummer;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private ?string $starttijd;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private ?string $eindtijd;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $vrij = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToernooiId(): ?int
    {
        return $this->toernooi_id;
    }

    public function setToernooiId(?int $toernooi_id): Tijdslot
    {
        $this->toernooi_id = $toernooi_id;
        return $this;
    }

    public function getDagnummer(): ?int
    {
        return $this->dagnummer;
    }

    public function setDagnummer(?int $dagnummer): Tijdslot
    {
        $this->dagnummer = $dagnummer;
        return $this;
    }

    public function getBaannummer(): ?int
    {
        return $this->baannummer;
    }

    public function setBaannummer(?int $baannummer): Tijdslot
    {
        $this->baannummer = $baannummer;
        return $this;
    }

    public function getSlotnummer(): ?int
    {
        return $this->slotnummer;
    }

    public function setSlotnummer(?int $slotnummer): Tijdslot
    {
        $this->slotnummer = $slotnummer;
        return $this;
    }

    public function getStarttijd(): ?string
    {
        return $this->starttijd;
    }

    public function setStarttijd(?string $starttijd): Tijdslot
    {
        $this->starttijd = $starttijd;
        return $this;
    }

    public function getEindtijd(): ?string
    {
        return $this->eindtijd;
    }

    public function setEindtijd(?string $eindtijd): Tijdslot
    {
        $this->eindtijd = $eindtijd;
        return $this;
    }

    public function isVrij(): bool
    {
        return $this->vrij;
    }

    public function setVrij(bool $vrij): Tijdslot
    {
        $this->vrij = $vrij;
        return $this;
    }

}
