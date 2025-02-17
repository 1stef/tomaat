<?php
declare(strict_types = 1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SpeeltijdenRepository")
 */
class Speeltijden
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
     * @ORM\Column(type="integer")
     */
    private int $dagnummer;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(min=15, max=240, notInRangeMessage="De wedstrijdduur moet tussen 15 en 240 min zijn")
     */
    private int $wedstrijd_duur;

    /**
     * @ORM\Column(type="time")
     */
    private DateTimeInterface $starttijd;

    /**
     * @ORM\Column(type="time")
     */
    private DateTimeInterface $eindtijd;

    /**
     * Creeer een leeg Speeltijden object:
     */ 
    public function __construct(int $toernooi_id, int $dagnummer){
        $this->toernooi_id = $toernooi_id;
        $this->dagnummer = $dagnummer;
        $this->wedstrijd_duur = 75;
        $this->starttijd = date_create_from_format('H:i', '09:00');
        $this->eindtijd = date_create_from_format('H:i', '23:00');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

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

    public function getDagnummer(): ?int
    {
        return $this->dagnummer;
    }

    public function setDagnummer(int $dagnummer): self
    {
        $this->dagnummer = $dagnummer;

        return $this;
    }

    public function getWedstrijdDuur(): ?int
    {
        return $this->wedstrijd_duur;
    }

    public function setWedstrijdDuur(int $wedstrijd_duur): self
    {
        $this->wedstrijd_duur = $wedstrijd_duur;

        return $this;
    }

    public function getStarttijd(): DateTimeInterface
    {
        return $this->starttijd;
    }

    public function setStarttijd(DateTimeInterface $starttijd): self
    {
        $this->starttijd = $starttijd;

        return $this;
    }

    public function getEindtijd(): DateTimeInterface
    {
        return $this->eindtijd;
    }

    public function setEindtijd(DateTimeInterface $eindtijd): self
    {
        $this->eindtijd = $eindtijd;

        return $this;
    }

}
