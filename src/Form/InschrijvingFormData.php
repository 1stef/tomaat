<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class InschrijvingFormData
{
    /**
     * @Assert\Type("integer")
     */
    public int $toernooi_id;

    /**
     * @Assert\Type("integer")
     */
    public int $deelnemerA;

    /**
     * @Assert\Type("integer")
     */
    public int $inschrijving_id_1;

    /**
     * @Assert\NotBlank(message="Kies minimaal één categorie om in te schrijven")
     * @Assert\Type("string")
     */
    public string $categorie_1;

    /**
     * @Assert\Type("string")
     */
    public string $cat_type_1;

    /**
     * @Assert\Type("integer")
     */
    public int $deelnemerB_1;

    /**
     * @Assert\Type("integer")
     */
    public int $aantal_1;

    /**
     * @Assert\Type("integer")
     */
    public int $inschrijving_id_2;

    /**
     * @Assert\Type("string")
     */
    public string $categorie_2;

    /**
     * @Assert\Type("string")
     */
    public string $cat_type_2;

    /**
     * @Assert\Type("integer")
     */
    public int $deelnemerB_2;

    /**
     * @Assert\Type("integer")
     */
    public int $aantal_2;

    /**
     * @Assert\Type("integer")
     */
    public int $inschrijving_id_3;

    /**
     * @Assert\Type("string")
     */
    public string $categorie_3;

    /**
     * @Assert\Type("string")
     */
    public string $cat_type_3;

    /**
     * @Assert\Type("integer")
     */
    public int $deelnemerB_3;

    /**
     * @Assert\Type("integer")
     */
    public int $aantal_3;

    /**
     * Constructor
     */
    public function __construct()
    {
        // no initialisations here.
    }

    /**
     * Initialising after construction:
     */
    public function init(int $toernooiId, int $bondsnummer, array $inschrijvingen): void
    {
        $this->toernooi_id = $toernooiId;
        $this->deelnemerA = $bondsnummer;
        if ($bondsnummer == null) {
            return;
        }
        $len = count($inschrijvingen);
        if ($len >= 1) {
            $this->inschrijving_id_1 = $inschrijvingen[0]->getId();
            $this->categorie_1 = $inschrijvingen[0]->getCategorie();
            $this->cat_type_1 = $inschrijvingen[0]->getCatType();
            if ($inschrijvingen[0]->getDeelnemerB()) {
                $this->deelnemerB_1 = $inschrijvingen[0]->getDeelnemerB();
            }
            $this->aantal_1 = $inschrijvingen[0]->getAantal();
        }
        if ($len >= 2) {
            $this->inschrijving_id_2 = $inschrijvingen[1]->getId();
            $this->categorie_2 = $inschrijvingen[1]->getCategorie();
            $this->cat_type_2 = $inschrijvingen[1]->getCatType();
            if ($inschrijvingen[1]->getDeelnemerB()) {
                $this->deelnemerB_2 = $inschrijvingen[1]->getDeelnemerB();
            }
            $this->aantal_2 = $inschrijvingen[1]->getAantal();
        }
        if ($len >= 3) {
            $this->inschrijving_id_3 = $inschrijvingen[2]->getId();
            $this->categorie_3 = $inschrijvingen[2]->getCategorie();
            $this->cat_type_3 = $inschrijvingen[2]->getCatType();
            if ($inschrijvingen[2]->getDeelnemerB()) {
                $this->deelnemerB_3 = $inschrijvingen[2]->getDeelnemerB();
            }
            $this->aantal_3 = $inschrijvingen[2]->getAantal();
        }
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        // Controleer of er helemaal geen categorie is gekozen:
        if ($this->categorie_1 == "geen categorie" && $this->categorie_2 == "geen categorie" && $this->categorie_3 == "geen categorie") {
            $context->buildViolation('U heeft geen inschrijfcategorie gekozen')
                ->addViolation();
        }
        if (isset($this->categorie_1) && ($this->categorie_1 != "geen categorie")) {
            if (!is_numeric($this->aantal_1)) {
                $context->buildViolation('Vul het gewenste aantal wedstrijden in')
                    ->atPath('aantal_1')
                    ->addViolation();
            } else {
                if ($this->aantal_1 < 1) {
                    $context->buildViolation('Het gewenste aantal wedstrijden moet minimaal 1 zijn')
                        ->atPath('aantal_1')
                        ->addViolation();
                }
            }
            if (($this->cat_type_1 == "dubbel") && !is_numeric($this->deelnemerB_1)) {
                $context->buildViolation('Vul het bondsnummer voor de dubbelpartner in')
                    ->atPath('deelnemerB_1')
                    ->addViolation();
            }
        }
        if (isset($this->categorie_2) && ($this->categorie_2 != "geen categorie")) {
            if (!is_numeric($this->aantal_2)) {
                $context->buildViolation('Vul het gewenste aantal wedstrijden in')
                    ->atPath('aantal_2')
                    ->addViolation();
            } else {
                if ($this->aantal_2 < 1) {
                    $context->buildViolation('Het gewenste aantal wedstrijden moet minimaal 1 zijn')
                        ->atPath('aantal_2')
                        ->addViolation();
                }
            }
            if (($this->cat_type_2 == "dubbel") && !is_numeric($this->deelnemerB_2)) {
                $context->buildViolation('Vul het bondsnummer voor de dubbelpartner in')
                    ->atPath('deelnemerB_2')
                    ->addViolation();
            }
        }
        if (isset($this->categorie_3) && ($this->categorie_3 != "geen categorie")) {
            if (!is_numeric($this->aantal_3)) {
                $context->buildViolation('Vul het gewenste aantal wedstrijden in')
                    ->atPath('aantal_3')
                    ->addViolation();
            } else {
                if ($this->aantal_3 < 1) {
                    $context->buildViolation('Het gewenste aantal wedstrijden moet minimaal 1 zijn')
                        ->atPath('aantal_3')
                        ->addViolation();
                }
            }
            if (($this->cat_type_3 == "dubbel") && !is_numeric($this->deelnemerB_3)) {
                $context->buildViolation('Vul het bondsnummer voor de dubbelpartner in')
                    ->atPath('deelnemerB_3')
                    ->addViolation();
            }
        }
        // Controleer of niet 2x voor dezelfde categorie wordt ingeschreven
        if (($this->categorie_1 == $this->categorie_2) && ($this->categorie_1 != "geen categorie")) {
            $context->buildViolation('U kunt maar 1 keer voor een categorie inschrijven')
                ->addViolation();
        }
        if (($this->categorie_1 == $this->categorie_3) && ($this->categorie_1 != "geen categorie")) {
            $context->buildViolation('U kunt maar 1 keer voor een categorie inschrijven')
                ->addViolation();
        }
        if (($this->categorie_2 == $this->categorie_3) && ($this->categorie_2 != "geen categorie")) {
            $context->buildViolation('U kunt maar 1 keer voor een categorie inschrijven')
                ->addViolation();
        }
    }

}