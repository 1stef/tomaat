<?php
declare(strict_types = 1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Psr\Log\LoggerInterface;

/**
 * @ORM\Entity(repositoryClass=VerhinderingRepository::class)
 */
class Verhindering
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
    private int $bondsnr;

    /**
     * @ORM\Column(type="integer")
     */
    private int $dagnummer;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $celnummer;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private ?DateTimeInterface $begintijd;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private ?DateTimeInterface $eindtijd;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $hele_dag;

    /**
     * geen ORM annotations, dus niet gemapt op de database:
     */
    private string $speeltijden;
    private LoggerInterface $logger;

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

    public function getBondsnr(): ?int
    {
        return $this->bondsnr;
    }

    public function setBondsnr(int $bondsnr): self
    {
        $this->bondsnr = $bondsnr;

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
    public function getCelnummer(): ?int
    {
        return $this->celnummer;
    }

    public function setCelnummer(?int $celnummer): self
    {
        $this->celnummer = $celnummer;

        return $this;
    }

    public function getBegintijd(): ?DateTimeInterface
    {
        return $this->begintijd;
    }

    public function setBegintijd(?DateTimeInterface $begintijd): self
    {
        $this->begintijd = $begintijd;

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

    public function getHeleDag(): ?bool
    {
        return $this->hele_dag;
    }

    public function setHeleDag(?bool $hele_dag): self
    {
        $this->hele_dag = $hele_dag;

        return $this;
    }

    public function init($speeltijden, LoggerInterface $logger): void
    {
        $this->speeltijden = json_decode($speeltijden, true);
        $this->logger = $logger;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        //$this->logger->info("Verhindering validate(), verhindering: ".print_r($this, true));
        // date_create zonder datum stopt 1-1-1970 in het DateTime object
        $starttijd = date_create_from_format("H:i:s", $this->speeltijden[$this->dagnummer-1]['starttijd']);
        $eindtijd = date_create_from_format("H:i:s", $this->speeltijden[$this->dagnummer-1]['eindtijd']);
        // Hier moeten we dus ook een date_create zonder datum doen, anders gaat de vergelijking fout:
        $verh_begin = date_create_from_format("H:i", $this->begintijd->format("H:i"));
        $verh_eind = date_create_from_format("H:i", $this->eindtijd->format("H:i"));
        if ($verh_begin < $starttijd){
            $context->buildViolation('Starttijd verhindering kan deze dag niet vóór '.$starttijd->format("H:i").' zijn.')
            ->addViolation();
        }
        if ($verh_eind > $eindtijd){
            $context->buildViolation('Eindtijd verhindering kan deze dag niet na '.$eindtijd->format("H:i").' zijn.')
            ->addViolation();
        }
   }

}
