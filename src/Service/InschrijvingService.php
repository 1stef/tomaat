<?php
declare(strict_types = 1);

namespace App\Service;

use App\Entity\Inschrijving;
use App\Form\InschrijvingFormData;
use App\Repository\InschrijvingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InschrijvingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly LoggerInterface $logger)
    {
    }

    /**
     * Sla de nieuwe of aangepaste inschrijvingen in het inschrijvingen formulier op in de database.
     */
    public function persistInschrijvingen(InschrijvingFormData $formData): void
    {
        $this->logger->info("InschrijvingService, persist()");
        if (isset($formData->categorie_1) || isset($formData->inschrijving_id_1)) {
            $this->createUpdateInschrijving($formData->inschrijving_id_1, $formData->toernooi_id, $formData->deelnemerA,
                $formData->categorie_1, $formData->cat_type_1, $formData->deelnemerB_1, $formData->aantal_1);
        }
        if (isset($formData->categorie_2) || isset($formData->inschrijving_id_2)) {
            $this->createUpdateInschrijving($formData->inschrijving_id_2, $formData->toernooi_id, $formData->deelnemerA,
                $formData->categorie_2, $formData->cat_type_2, $formData->deelnemerB_2, $formData->aantal_2);
        }
        if (isset($formData->categorie_3) || isset($formData->inschrijving_id_3)) {
            $this->createUpdateInschrijving($formData->inschrijving_id_3, $formData->toernooi_id, $formData->deelnemerA,
                $formData->categorie_3, $formData->cat_type_2, $formData->deelnemerB_3, $formData->aantal_3);
        }
    }


    /**
     * createUpdateInschrijving - sla een nieuwe inschrijving op of update hem, als hij al bestaat
     *
     */
    public function createUpdateInschrijving(
        int $id,
        int $toernooi_id,
        int $bondsnummer,
        string $categorie,
        string $cat_type,
        int $deelnemerB,
        int $aantal): void
    {
        if (empty($id)) {
            if ($categorie == 'geen categorie') {
                // gebruiker heeft op 'Kies categorie' geklikt, maar niets ingevuld
                return;
            }
            $inschrijving = new Inschrijving($toernooi_id, $bondsnummer);
        } else {
            $inschrijving = $this->inschrijvingRepository->find($id);
            if ($categorie == 'geen categorie') {
                $this->entityManager->remove($inschrijving);
                return;
            }
            $inschrijving->setDeelnemerA($bondsnummer);
        }
        $inschrijving->setCategorie($categorie);
        $inschrijving->setCatType($cat_type);
        $inschrijving->setDeelnemerB($deelnemerB);  // kan null zijn bij enkelpartij
        $inschrijving->setAantal($aantal);
        $this->entityManager->persist($inschrijving);
        $this->entityManager->flush();
    }

}