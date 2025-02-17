<?php
declare(strict_types = 1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WedstrijdRepository")
 */
class Wedstrijd
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private int $wedstrijd_id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $toernooi_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $baan;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private DateTimeInterface $starttijd;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private DateTimeInterface $eindtijd;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $set1_team1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $set1_team2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $set2_team1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $set2_team2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $set3_team1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $set3_team2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $winnaar;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $opgave;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $wedstrijd_status;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $aanwezig_1A;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $aanwezig_1B;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $aanwezig_2A;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $aanwezig_2B;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTimeInterface $wachtstarttijd;

    public function getWedstrijdId(): ?int
    {
        return $this->wedstrijd_id;
    }

    public function setWedstrijdId(int $wedstrijd_id): self
    {
        $this->wedstrijd_id = $wedstrijd_id;

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

    public function getBaan(): ?int
    {
        return $this->baan;
    }

    public function setBaan(?int $baan): self
    {
        $this->baan = $baan;

        return $this;
    }

    public function getStarttijd(): ?DateTimeInterface
    {
        return $this->starttijd;
    }

    public function setStarttijd(?DateTimeInterface $starttijd): self
    {
        $this->starttijd = $starttijd;

        return $this;
    }

    public function getEindtijd(): ?DateTimeInterface
    {
        return $this->eindtijd;
    }

    public function setEindtijd(?DateTimeInterface $eindtijd): self
    {
        $this->eindtijd = $eindtijd;

        return $this;
    }

    public function getSet1Team1(): ?int
    {
        return $this->set1_team1;
    }

    public function setSet1Team1(?int $set1_team1): self
    {
        $this->set1_team1 = $set1_team1;

        return $this;
    }

    public function getSet1Team2(): ?int
    {
        return $this->set1_team2;
    }

    public function setSet1Team2(?int $set1_team2): self
    {
        $this->set1_team2 = $set1_team2;

        return $this;
    }

    public function getSet2Team1(): ?int
    {
        return $this->set2_team1;
    }

    public function setSet2Team1(?int $set2_team1): self
    {
        $this->set2_team1 = $set2_team1;

        return $this;
    }

    public function getSet2Team2(): ?int
    {
        return $this->set2_team2;
    }

    public function setSet2Team2(?int $set2_team2): self
    {
        $this->set2_team2 = $set2_team2;

        return $this;
    }

    public function getSet3Team1(): ?int
    {
        return $this->set3_team1;
    }

    public function setSet3Team1(?int $set3_team1): self
    {
        $this->set3_team1 = $set3_team1;

        return $this;
    }

    public function getSet3Team2(): ?int
    {
        return $this->set3_team2;
    }

    public function setSet3Team2(?int $set3_team2): self
    {
        $this->set3_team2 = $set3_team2;

        return $this;
    }

    public function getWinnaar(): ?int
    {
        return $this->winnaar;
    }

    public function setWinnaar(?int $winnaar): self
    {
        $this->winnaar = $winnaar;

        return $this;
    }

    public function getOpgave(): ?int
    {
        return $this->opgave;
    }

    public function setOpgave(?int $opgave): self
    {
        $this->opgave = $opgave;

        return $this;
    }

    public function getWedstrijdStatus(): ?string
    {
        return $this->wedstrijd_status;
    }

    public function setWedstrijdStatus(?string $wedstrijd_status): self
    {
        $this->wedstrijd_status = $wedstrijd_status;

        return $this;
    }

    public function getAanwezig1A(): ?bool
    {
        return $this->aanwezig_1A;
    }

    public function setAanwezig1A(?bool $aanwezig_1A): self
    {
        $this->aanwezig_1A = $aanwezig_1A;

        return $this;
    }

    public function getAanwezig1B(): ?bool
    {
        return $this->aanwezig_1B;
    }

    public function setAanwezig1B(?bool $aanwezig_1B): self
    {
        $this->aanwezig_1B = $aanwezig_1B;

        return $this;
    }

    public function getAanwezig2A(): ?bool
    {
        return $this->aanwezig_2A;
    }

    public function setAanwezig2A(?bool $aanwezig_2A): self
    {
        $this->aanwezig_2A = $aanwezig_2A;

        return $this;
    }

    public function getAanwezig2B(): ?bool
    {
        return $this->aanwezig_2B;
    }

    public function setAanwezig2B(?bool $aanwezig_2B): self
    {
        $this->aanwezig_2B = $aanwezig_2B;

        return $this;
    }

    public function getWachtstarttijd(): ?DateTimeInterface
    {
        return $this->wachtstarttijd;
    }

    public function setWachtstarttijd(?DateTimeInterface $wachtstarttijd): self
    {
        $this->wachtstarttijd = $wachtstarttijd;

        return $this;
    }

    public function copyUitslagData($formdata): void
    {
        $this->set1_team1 = $formdata->getSet1Team1();
        $this->set1_team2 = $formdata->getSet1Team2();
        $this->set2_team1 = $formdata->getSet2Team1();
        $this->set2_team2 = $formdata->getSet2Team2();
        $this->set3_team1 = $formdata->getSet3Team1();
        $this->set3_team2 = $formdata->getSet3Team2();
        $this->winnaar = $formdata->getWinnaar();
        $this->opgave = $formdata->getOpgave();
        $this->wedstrijd_status = $formdata->getWedstrijdStatus();
    }
}
