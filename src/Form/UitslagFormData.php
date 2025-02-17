<?php

declare(strict_types=1);

namespace App\Form;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class UitslagFormData
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $baan;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $set1_team1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $set1_team2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $set2_team1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $set2_team2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $set3_team1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $set3_team2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $winnaar;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private int $opgave;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private string $wedstrijd_status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

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

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if (is_numeric($this->set1_team1) && is_numeric($this->set1_team2) &&
            is_numeric($this->set2_team1) && is_numeric($this->set2_team2)) {
            $team1_wint_set1 = $this->set1_team1 > $this->set1_team2;
            $team1_wint_set2 = $this->set2_team1 > $this->set2_team2;
            error_log(
                "UitslagFormData, validate(), team1_wint_set1: " . $team1_wint_set1 . ",
                                 team1_wint_set1: " . $team1_wint_set2 . "winnaar: " . $this->winnaar,
                3,
                "C:\Users\stefs\Documents\log"
            );
            if ($team1_wint_set1 == $team1_wint_set2) {
                if ($team1_wint_set1 && ($this->winnaar == 2) ||
                    !$team1_wint_set1 && ($this->winnaar == 1)) {
                    $context->buildViolation('De winnaar klopt niet met de ingevulde setstanden!')
                        ->addViolation();
                }
            } else {
                if (is_numeric($this->set3_team1) && is_numeric($this->set3_team2)) {
                    $team1_wint_set3 = $this->set3_team1 > $this->set3_team2;
                    if ($team1_wint_set3 && ($this->winnaar == 2) ||
                        !$team1_wint_set3 && ($this->winnaar == 1)) {
                        $context->buildViolation('De winnaar klopt niet met de ingevulde setstanden!')
                            ->addViolation();
                    }
                } else {
                    $context->buildViolation('Vul de standen van set 3 in')
                        ->addViolation();
                }
            }
        } else {
            $context->buildViolation('Vul de standen van set 1 en 2 in')
                ->addViolation();
        }
    }

}
