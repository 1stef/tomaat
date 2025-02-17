<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ToernooiRepository")
 */
class Toernooi
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Vul een unieke toernooinaam in")
     */
    private string $toernooi_naam;

    /**
     * @ORM\Column(type="integer")
     */
    private int $admin_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $toernooi_status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $vereniging;

    /**
     * @ORM\Column(type="date")
     * @Assert\Range(min="now", minMessage="Geef een toekomstige datum op",)
     */
    private DateTimeInterface $eerste_dag;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(min=1, max=30, notInRangeMessage="Het aantal toernooidagen moet tussen 1 en 30 zijn")
     */
    private int $aantal_dagen;

    /**
     * @ORM\Column(type="integer")
     */
    private int $speeldag;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $aanvrager_naam;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $aanvrager_tel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $aanvrager_email;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private string $opmerkingen;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $blokkeer_mails;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToernooiNaam(): ?string
    {
        return $this->toernooi_naam;
    }

    public function setToernooiNaam(string $toernooi_naam): self
    {
        $this->toernooi_naam = $toernooi_naam;

        return $this;
    }

    public function getAdminId(): ?int
    {
        return $this->admin_id;
    }

    public function setAdminId(int $admin_id): self
    {
        $this->admin_id = $admin_id;

        return $this;
    }

    public function getToernooiStatus(): ?string
    {
        return $this->toernooi_status;
    }

    public function setToernooiStatus(string $toernooi_status): self
    {
        $this->toernooi_status = $toernooi_status;

        return $this;
    }
    
    public function getVereniging(): ?string
    {
        return $this->vereniging;
    }

    public function setVereniging(string $vereniging): self
    {
        $this->vereniging = $vereniging;

        return $this;
    }

    public function getEersteDag(): ?DateTimeInterface
    {
        return $this->eerste_dag;
    }

    public function setEersteDag(DateTimeInterface $eerste_dag): self
    {
        $this->eerste_dag = $eerste_dag;

        return $this;
    }

    public function getAantalDagen(): ?int
    {
        return $this->aantal_dagen;
    }

    public function setAantalDagen(int $aantal_dagen): self
    {
        $this->aantal_dagen = $aantal_dagen;

        return $this;
    }

    public function getSpeeldag(): ?int
    {
        return $this->speeldag;
    }

    public function setSpeeldag(int $speeldag): self
    {
        $this->speeldag = $speeldag;

        return $this;
    }

    public function getAanvragerNaam(): ?string
    {
        return $this->aanvrager_naam;
    }

    public function setAanvragerNaam(string $aanvrager_naam): self
    {
        $this->aanvrager_naam = $aanvrager_naam;

        return $this;
    }

    public function getAanvragerTel(): ?string
    {
        return $this->aanvrager_tel;
    }

    public function setAanvragerTel(string $aanvrager_tel): self
    {
        $this->aanvrager_tel = $aanvrager_tel;

        return $this;
    }

    public function getAanvragerEmail(): ?string
    {
        return $this->aanvrager_email;
    }

    public function setAanvragerEmail(string $aanvrager_email): self
    {
        $this->aanvrager_email = $aanvrager_email;

        return $this;
    }
    public function getOpmerkingen(): ?string
    {
        return $this->opmerkingen;
    }

    public function setOpmerkingen(?string $opmerkingen): self
    {
        $this->opmerkingen = $opmerkingen;

        return $this;
    }

    public function getBlokkeerMails(): ?bool
    {
        return $this->blokkeer_mails;
    }

    public function setBlokkeerMails(?bool $blokkeer_mails): self
    {
        $this->blokkeer_mails = $blokkeer_mails;

        return $this;
    }

}
