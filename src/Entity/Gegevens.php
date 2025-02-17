<?php
declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GegevensRepository")
 */
class Gegevens
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private int $toernooi_id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(min=1, max=50, notInRangeMessage="Het aantal banen moet tussen 1 en 50 zijn")
     */
    private int $aantal_banen;

    /** 
     * Maak een Gegevens object aan met een toernooi_id:
     */
    public function __construct(int $toernooiId) {
        $this->toernooi_id = $toernooiId;
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

    public function getAantalBanen(): ?int
    {
        return $this->aantal_banen;
    }

    public function setAantalBanen(?int $aantal_banen): self
    {
        $this->aantal_banen = $aantal_banen;

        return $this;
    }
}
