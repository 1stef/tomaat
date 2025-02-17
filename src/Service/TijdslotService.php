<?php
declare(strict_types = 1);

namespace App\Service;

use App\Entity\Tijdslot;
use App\Entity\Toernooi;
use App\Repository\GegevensRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\TijdslotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TijdslotService
{
    public function __construct(
        private readonly entityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly SpeeltijdenRepository $speeltijdenRepository,
        private readonly GegevensRepository $gegevensRepository,
        private readonly TijdslotRepository $tijdslotsRepository)
    {
    }

    public function maakTijdsloten(Toernooi $toernooi): array | false
    {
        $toernooi_id = $toernooi->getId();
        $speeltijden = $this->speeltijdenRepository->findBy(['toernooi_id' => $toernooi_id]);
        $this->logger->info("maakTijdsloten, getSpeeltijden, toernooi_id=" . $toernooi_id);
        if (!empty($speeltijden)) {
            $aantalDagen = count($speeltijden);
            $gegevens = $this->gegevensRepository->findOneBy(['toernooi_id' => $toernooi_id]);
            if (empty($gegevens)) {
                $this->logger->info("maakTijdsloten, geen toernooigegevens voor toernooi_id: " . $toernooi_id);
                return false;
            } else {
                if ($aantalDagen != $toernooi->getAantalDagen()) {
                    $this->logger->info("maakTijdsloten, aantal dagen in speeltijden en toernooigegevens verschillend voor toernooi_id: " . $toernooi_id);
                    return false;
                }
            }
            // verwijder eerst de eventuele oude tijdsloten voor dit toernooi
            $this->tijdslotsRepository->verwijderToernooiTijdsloten($toernooi_id);
            for ($dagNummer = 1; $dagNummer <= $aantalDagen; $dagNummer++) {
                $start = $speeltijden[$dagNummer - 1]->getStarttijd()->getTimestamp();
                $eind = $speeltijden[$dagNummer - 1]->getEindtijd()->getTimestamp();
                $aantalMinuten = ($eind - $start) / 60;
                $aantalTijdsloten = floor($aantalMinuten / $speeltijden[$dagNummer - 1]->getWedstrijdDuur());

                $this->logger->info("maakTijdsloten, dagnummer: " . $dagNummer . " aantal minuten: " . $aantalMinuten . " aantal tijdsloten: " . $aantalTijdsloten);
                for ($baanNummer = 1; $baanNummer <= $gegevens->getAantalBanen(); $baanNummer++) {
                    for ($slotNummer = 1; $slotNummer <= $aantalTijdsloten; $slotNummer++) {
                        $startOffset = ($slotNummer - 1) * $speeltijden[$dagNummer - 1]->getWedstrijdDuur();
                        $eindOffset = ($slotNummer) * $speeltijden[$dagNummer - 1]->getWedstrijdDuur();
                        $startTijd = date("H:i", $start + 60 * $startOffset);
                        $eindTijd = date("H:i", $start + 60 * $eindOffset);
                        $tijdslot = new Tijdslot();
                        $tijdslot->setToernooiId($toernooi_id)->
                            setDagnummer($dagNummer)->
                            setBaannummer($baanNummer)->
                            setSlotnummer($slotNummer)->
                            setStarttijd($startTijd)->
                            setEindtijd($eindTijd)->
                            setVrij(true);
                        $this->entityManager->persist($tijdslot);
                    }
                }
                $this->entityManager->flush();
            }
            return $this->tijdslotsRepository->toonTijdsloten($toernooi_id);
        }
        $this->logger->info("maakTijdsloten, geen speeltijden voor toernooi_id: " . $toernooi_id);
        return (false);
    }
    
}