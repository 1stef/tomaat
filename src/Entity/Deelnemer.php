<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeelnemerRepository")
 */
class Deelnemer
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    public int $user_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(message="Vul uw bondsnummer in")
     */
    public ?int $bondsnummer = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Vul uw naam in")
     */
    public ?string $naam;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(message="Vul uw geboortedatum in")
     */
    public ?string $geb_datum;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\NotBlank(message="Vul uw enkelspel ranking in")
     */
    public ?float $ranking_enkel;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\NotBlank(message="Vul uw dubbelspel ranking in")
     */
    public ?float $ranking_dubbel;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @Assert\NotBlank(message="Vul uw telefoonnummer in")
     */
    private ?string $telefoonnummer;
 
    /** 
     * The user must be logged in to be able te create a Deelnemer object
     */
    public function __construct(int $userId) {
        $this->user_id = $userId;
    }

    /** 
     * public function getId(): ?int
    *  {
    *      return $this->id;
    *  }
    */

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $userId): self
    {
        $this->user_id = $userId;

        return $this;
    }

    public function getBondsnummer(): ?int
    {
        return $this->bondsnummer;
    }

    public function setBondsnummer(?int $bondsnummer): self
    {
        $this->bondsnummer = $bondsnummer;

        return $this;
    }

    public function getNaam(): ?string
    {
        return $this->naam;
    }

    public function setNaam(?string $naam): self
    {
        $this->naam = $naam;

        return $this;
    }

    public function getGebDatum(): ?string
    {
        return $this->geb_datum;
    }

    public function setGebDatum(?string $geb_datum): self
    {
        $this->geb_datum = $geb_datum;

        return $this;
    }

    public function getRankingEnkel(): ?float
    {
        return $this->ranking_enkel;
    }

    public function setRankingEnkel(?float $ranking_enkel): self
    {
        $this->ranking_enkel = $ranking_enkel;

        return $this;
    }

    public function getRankingDubbel(): ?float
    {
        return $this->ranking_dubbel;
    }

    public function setRankingDubbel(?float $ranking_dubbel): self
    {
        $this->ranking_dubbel = $ranking_dubbel;

        return $this;
    }

    public function getTelefoonnummer(): ?string
    {
        return $this->telefoonnummer;
    }

    public function setTelefoonnummer(?string $telefoonnummer): self
    {
        $this->telefoonnummer = $telefoonnummer;

        return $this;
    }
}
