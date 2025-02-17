<?php
declare(strict_types=1);

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=BerichtRepository::class)
 */
class Bericht
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
    private int $afzender;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $ontvanger_1 = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $ontvanger_2 = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $ontvanger_3 = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $ontvanger_4 = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $cc_toernooileiding;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $alle_deelnemers;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private string $berichttype;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $titel;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     * @Assert\NotBlank(message="Vul een toelichting in")
     */
    private ?string $tekst;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public ?int $wedstrijd_wijziging_id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $verzend_tijd;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $gelezen;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $van_toernooileiding;

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

    public function getAfzender(): ?int
    {
        return $this->afzender;
    }

    public function setAfzender(int $afzender): self
    {
        $this->afzender = $afzender;

        return $this;
    }

    public function getOntvanger1(): ?int
    {
        return $this->ontvanger_1;
    }

    public function setOntvanger1(?int $ontvanger_1): self
    {
        $this->ontvanger_1 = $ontvanger_1;

        return $this;
    }

    public function getOntvanger2(): ?int
    {
        return $this->ontvanger_2;
    }

    public function setOntvanger2(?int $ontvanger_2): self
    {
        $this->ontvanger_2 = $ontvanger_2;

        return $this;
    }

    public function getOntvanger3(): ?int
    {
        return $this->ontvanger_3;
    }

    public function setOntvanger3(?int $ontvanger_3): self
    {
        $this->ontvanger_3 = $ontvanger_3;

        return $this;
    }

    public function getCcToernooileiding(): ?bool
    {
        return $this->cc_toernooileiding;
    }

    public function setCcToernooileiding(bool $cc_toernooileiding): self
    {
        $this->cc_toernooileiding = $cc_toernooileiding;

        return $this;
    }

    public function getAlleDeelnemers(): ?bool
    {
        return $this->alle_deelnemers;
    }

    public function setAlleDeelnemers(bool $alle_deelnemers): self
    {
        $this->alle_deelnemers = $alle_deelnemers;

        return $this;
    }

    public function getBerichttype(): ?string
    {
        return $this->berichttype;
    }

    public function setBerichttype(string $berichttype): self
    {
        $this->berichttype = $berichttype;

        return $this;
    }

    public function getTitel(): ?string
    {
        return $this->titel;
    }

    public function setTitel(?string $titel): self
    {
        $this->titel = $titel;

        return $this;
    }

    public function getTekst(): ?string
    {
        return $this->tekst;
    }

    public function setTekst(?string $tekst): self
    {
        $this->tekst = $tekst;

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

    public function getVerzendTijd(): ?DateTimeInterface
    {
        return $this->verzend_tijd;
    }

    public function setVerzendTijd(?DateTimeInterface $verzend_tijd): self
    {
        $this->verzend_tijd = $verzend_tijd;

        return $this;
    }

    public function setVerzendTijdNow(): self
    {
        $this->verzend_tijd = new DateTime("now");

        return $this;
    }

    public function getGelezen(): ?bool
    {
        return $this->gelezen;
    }

    public function setGelezen(?bool $gelezen): self
    {
        $this->gelezen = $gelezen;

        return $this;
    }

    public function getVanToernooileiding(): ?bool
    {
        return $this->van_toernooileiding;
    }

    public function setVanToernooileiding(bool $van_toernooileiding): self
    {
        $this->van_toernooileiding = $van_toernooileiding;

        return $this;
    }

    public function getOntvanger4(): ?int
    {
        return $this->ontvanger_4;
    }

    public function setOntvanger4(?int $ontvanger_4): self
    {
        $this->ontvanger_4 = $ontvanger_4;

        return $this;
    }
}
