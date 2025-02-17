<?php
declare(strict_types = 1);

namespace App\Service;

use App\Entity\Combinations;
use App\Entity\Toernooi;
use App\Repository\CategorieRepository;
use App\Repository\CombinationsRepository;
use App\Repository\DeelnemerRepository;
use App\Repository\GegevensRepository;
use App\Repository\InschrijvingRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\TijdslotRepository;
use App\Repository\VerhinderingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PlanningService
{
    public function __construct(
        private readonly CategorieRepository    $categorieRepository,
        private readonly CombinationsRepository $combinationsRepository,
        private readonly DeelnemerRepository    $deelnemerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GegevensRepository     $gegevensRepository,
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly LoggerInterface        $logger,
        private readonly SpeeltijdenRepository  $speeltijdenRepository,
        private readonly TijdslotRepository     $tijdslotRepository,
        private readonly VerhinderingRepository $verhinderingRepository)
    {
    }

    /**
     * planWedstrijden - Verdeel alle wedstrijden over de beschikbare tijdsloten.
     */
    function planWedstrijden(int $toernooiId, Toernooi $toernooi): void
    {
        if (empty($this->gegevensRepository->find($toernooiId))) {
            $this->logger->error('Wedstrijden niet gepland: geen toernooigegevens gevonden');
            return;
        }

        // Verwijder eerst mogelijke eerdere planresultaten voor dit toernooi:
        $this->combinationsRepository->clearCombinations($toernooiId);
        $this->tijdslotRepository->clearTijdsloten($toernooiId);

        // Vervang de eventuele oude combinaties door nieuw berekende combinaties:
        $this->makeAllCategoriesCombinations($toernooiId);

        // Haal de combinaties op, de combinaties met de hoogste plan_ronde achteraan:
        $combinations = $this->combinationsRepository->getCombinations($toernooiId);
        $this->logger->info("planWedstrijden, combinations: " . json_encode($combinations));

        // Haal de bondsnummers per inschrijving op:
        $inschrijvingen = $this->inschrijvingRepository->getBondsnummers($toernooiId);

        // Haal de beschikbare tijdsloten op:
        $tijdsloten = $this->tijdslotRepository->getTijdsloten($toernooiId);
        $this->logger->info("planWedstrijden, tijdsloten: " . json_encode($tijdsloten));

        // Maak de verhinderde tijdsloten array:
        $verhinderdeTijdsloten = $this->maakVerhinderdeTijdsloten($toernooiId);

        // Het aantal mogelijke wedstrijden is het minimum van het aantal combinaties en het aantal beschikbare tijdsloten:
        $aantalWedstrijden = min(count($combinations), count($tijdsloten));
        $aantalDagen = $toernooi->getAantalDagen();
        $deelnemerToewijzingen = array_fill(1, $aantalDagen, []);

        $shuffledIndices = $this->createShuffledIndices($combinations);

        for ($i = 0; $i < $aantalWedstrijden; $i++) {
            $this->logger->info("planWedstrijden, wedstrijd nr: " . $i);
            // kies een dag waarop geen of anders zo min mogelijk deelnemers al ingepland zijn
            $dag = rand(1, $aantalDagen); // begin te zoeken op een willekeurige toernooidag
            $tijdslot = -1; // initialiseer op "geen tijdslot gevonden"
            for ($cnt = 0; $cnt < $aantalDagen; $cnt++) {
                $this->logger->info("planWedstrijden, dag: " . $dag);
                $deelnemer1 = $combinations[$shuffledIndices[$i]['index']]['deelnemer1'];
                $deelnemer2 = $combinations[$shuffledIndices[$i]['index']]['deelnemer2'];
                $key1 = array_search($deelnemer1, array_column($inschrijvingen, 'id'));
                $key2 = array_search($deelnemer2, array_column($inschrijvingen, 'id'));
                $bondsnummer1A = $inschrijvingen[$key1]['deelnemerA'];
                $bondsnummer1B = $inschrijvingen[$key1]['deelnemerB'];
                $bondsnummer2A = $inschrijvingen[$key2]['deelnemerA'];
                $bondsnummer2B = $inschrijvingen[$key2]['deelnemerB'];
                if ((!in_array($bondsnummer1A, $deelnemerToewijzingen[$dag])) &&
                    (!in_array($bondsnummer1B, $deelnemerToewijzingen[$dag])) &&
                    (!in_array($bondsnummer2A, $deelnemerToewijzingen[$dag])) &&
                    (!in_array($bondsnummer2B, $deelnemerToewijzingen[$dag]))) {
                    // zoek het eerste vrije tijdslot die dag:
                    $tijdslot = $this->searchForId($toernooiId, $verhinderdeTijdsloten, $dag, $tijdsloten, $deelnemer1, $deelnemer2);
                    if ($tijdslot != -1) {
                        $id = $tijdsloten[$tijdslot]['id'];
                        break;  // deze dag en dit tijdslot is goed, stap uit de for-loop
                    }
                    // niet gevonden, dan naar de volgende dag..
                }
                $dag++;
                if ($dag > $aantalDagen) {
                    $dag = 1;
                }
            }
            if ($tijdslot == -1) {
                $this->logger->info("planWedstrijden, tijdslot = null");
                // TODO: Als we geen tijdslot gevonden hebben, kijken we of er een dag is met zo min mogelijk deelnemers ingepland
                // en zoeken we een begintijd die anders is dan de geplande begintijd(en)
            } else {
                // Als we een tijdslot gevonden hebben:
                $this->logger->info("planWedstrijden, tijdslot: " . $id);
                // voeg het tijdslot toe aan $combinations[$shuffledIndices[$i]['index']
                $combinations[$shuffledIndices[$i]['index']]['tijdslot'] = $id;
                // markeer het tijdslot als bezet
                $tijdsloten[$tijdslot]['vrij'] = 0;
                // voeg alle (deelnemer + dag) combinaties toe aan $deelnemerToewijzingen
                if (is_numeric($bondsnummer1B)) {
                    array_push($deelnemerToewijzingen[$dag], $bondsnummer1A, $bondsnummer1B, $bondsnummer2A, $bondsnummer2B);
                } else {
                    array_push($deelnemerToewijzingen[$dag], $bondsnummer1A, $bondsnummer2A);
                }
                // Werk in de database de tabel combinations bij met het gevonden tijdsloten voor deze geplande wedstrijd
                $combinationsId = $combinations[$shuffledIndices[$i]['index']]['id'];
                $combination = $this->combinationsRepository->find($combinationsId);
                $combination->setTijdslot($id);
                $this->entityManager->persist($combination);

                // Werk in de database de tabel tijdslot bij voor het nu bezette tijdslot:
                $tijdslotObject = $this->tijdslotRepository->find($id);
                $tijdslotObject->setVrij(false);
                $this->entityManager->persist($tijdslotObject);
                $this->entityManager->flush();
            }
        }
        $this->logger->info("planWedstrijden, tijdsloten achteraf: " . json_encode($tijdsloten));
    }

    /*
     * Hulpfunctie om het eerste vrije tijdslot op een dag te vinden:
     */
    private function searchForId(
        int $toernooiId,
        array $verhinderdeTijdsloten,
        int $dag,
        array $array,
        int $deelnemer1,
        int $deelnemer2): int
    {
        foreach ($array as $key => $val) {
            //$this->logger->info("searchForId dag: ".$dag."value: ".$value." key: ".$key);
            if (($val['dagnummer'] == $dag) && ($val['vrij'] == 1)) {
                // Check ook of  de combinaties: tijdslot, deelnemer1
                // en tijdslot, deelnemer2 niet voorkomen in de verhinderingen:
                if (!in_array(['toernooi_id' => $toernooiId, 'inschrijving_id' => $deelnemer1,
                        'dagnummer' => $dag, 'slotnummer' => $val['slotnummer']],
                        $verhinderdeTijdsloten) &&
                    !in_array(['toernooi_id' => $toernooiId, 'inschrijving_id' => $deelnemer2,
                        'dagnummer' => $dag, 'slotnummer' => $val['slotnummer']],
                        $verhinderdeTijdsloten)) {
                    return $key;
                }
            }
        }
        return -1;
    }

    /*
     * Hulpfunctie voor PlanWedstrijden():
     * Create a shuffled array of the indices of array combinations
     * Why do we do this:
     * Combinations is built up category by category, but if not all matches can be planned,
     * we want all categories to have the same chance on missing matches.
     */
    private function createShuffledIndices(array $combinations): array
    {
        $shuffledIndices = array();
        for ($i = 0; $i < count($combinations); $i++) {
            $shuffledIndices[] = ['index' => $i, 'plan_ronde' => $combinations[$i]['plan_ronde']];
        }
        shuffle($shuffledIndices);
        // sorteer shuffled_indices weer op plan_ronde:
        // closure voor hulp-functie om usort te doen:
        $mycmp = function ($a, $b) {
            if ($a['plan_ronde'] == $b['plan_ronde']) {
                return 0;
            }
            return ($a['plan_ronde'] > $b['plan_ronde']) ? +1 : -1;
        };
        usort($shuffledIndices, $mycmp);
        return $shuffledIndices;
    }

    public function makeAllCategoriesCombinations(int $toernooiId): void
    {
        // Zet eerst de effectieve ranking van alle enkel- en dubbelinschrijvingen
        $this->setEffectieveRankings($toernooiId);
        $categories = $this->categorieRepository->findBy(['toernooi_id' => $toernooiId]);
        foreach ($categories as $category):
            $this->makeAllCombinations($toernooiId, $category->getCat());
        endforeach;
    }

    /**
     * setEffectieveRankings - zet de effectieve rankings voor iedere inschrijving voor een toernooi.
     * Markeer ook steeds 1 van 2 bij elkaar horende dubbelinschrijvingen als niet actief, evenals niet gematchte dubbelinschrijvingen
     * Wordt aangeroepen in makeAllCategoriesCombinations
     */
    public function setEffectieveRankings(int $toernooiId): void
    {
        // haal alle inschrijvingen voor het toernooi op
        $inschrijvingen = $this->inschrijvingRepository->alleInschrijvingen($toernooiId);

        // Bepaal (opnieuw) welke inschrijvingen actief zijn:
        $houd_lijst = [];
        for ($i = 0; $i < count($inschrijvingen); $i++) {
            $inschrijvingen[$i]['actief'] = true;  // default wel actief
            if (($inschrijvingen[$i]['cat_type'] == "dubbel") && !in_array($i, $houd_lijst)) {
                // markeer de eerste van 2 dubbelinschrijvingen en inschrijvingen zonder partner inschrijving
                $inschrijvingen[$i]['actief'] = false;  // markeer als niet actief
                // zoek de tegen-inschrijving en voeg die aan de houd_lijst toe
                for ($j = $i + 1; $j < count($inschrijvingen); $j++) {
                    if ($inschrijvingen[$i]['categorie'] == $inschrijvingen[$j]['categorie'] &&
                        $inschrijvingen[$i]['deelnemerA'] == $inschrijvingen[$j]['deelnemerB'] &&
                        $inschrijvingen[$i]['deelnemerB'] == $inschrijvingen[$j]['deelnemerA']) {
                        $houd_lijst[] = $j;
                    }
                }
            }
        }

        foreach ($inschrijvingen as $inschrijvingRow) {
            $inschrijving_id = $inschrijvingRow['id'];
            $bondsNrDeelnemerB = $inschrijvingRow['deelnemerB'];
            if (!is_numeric($bondsNrDeelnemerB)) {
                // ranking_effectief wordt ranking_enkel:
                $ranking_effectief = $inschrijvingRow['ranking_enkel'];
            } else {
                // ranking_effectief wordt gemiddelde van ranking_dubbel voor deelnemerA en deelnemerB:
                $deelnemerB = $this->deelnemerRepository->findOneBy(['bondsnummer' => $bondsNrDeelnemerB]);
                if ($deelnemerB && is_numeric($deelnemerB->getRankingDubbel())) {
                    $ranking_effectief = ($inschrijvingRow['ranking_dubbel'] + $deelnemerB->getRankingDubbel()) / 2;
                } else {
                    $ranking_effectief = 0;
                }
            }
            // update de waarde voor ranking_effectief en zet de waarde van de boolean actief in de database:
            $inschrijving = $this->inschrijvingRepository->find($inschrijving_id);
            $inschrijving->setActief($inschrijvingRow['actief']);
            $inschrijving->setRankingEffectief($ranking_effectief);
            $this->entityManager->persist($inschrijving);
            $this->entityManager->flush();
        }

    }

    /**
     * makeAllCombinations - Computes the combinations of opponents for a singles or doubles category.
     **/
    public function makeAllCombinations(int $toernooiId, string $category): void
    {
        $inschrijvingen = $this->inschrijvingRepository->getCategorieInschrijvingen($toernooiId, $category);
        $aantalInschrijvingen = count($inschrijvingen);

        $newOrder = [];
        for ($i = 1; $i <= ceil($aantalInschrijvingen / 2); $i++) {
            $id = $inschrijvingen[2 * $i - 2]["id"];
            $aantal = $inschrijvingen[2 * $i - 2]["aantal"];
            $spelerOfTeam = new SpelerOfTeam($id, $aantal);
            $this->logger->info("makeAllCombinations spelerOfTeam: " . json_encode($spelerOfTeam));
            $newOrder[] = $spelerOfTeam;
        }
        for ($i = floor($aantalInschrijvingen / 2); $i >= 1; $i--) {
            $id = $inschrijvingen[2 * $i - 1]["id"];
            $aantal = $inschrijvingen[2 * $i - 1]["aantal"];
            $spelerOfTeam = new SpelerOfTeam($id, $aantal);
            $this->logger->info("makeAllCombinations spelerOfTeam: " . json_encode($spelerOfTeam));
            $newOrder[] = $spelerOfTeam;
        }

        /**
         *  Probeer een combinatie te maken voor alle gewenste partijen voor alle deelnemers:
         */
        $this->combinationsRepository->deleteCombinations($toernooiId, $category);

        $maxAantal = 6;    // het maximum aantal partijen per deelnemer in 1 categorie

        if ($aantalInschrijvingen > 1) {    // omdat anders 1 inschrijving in een categorie tegen zichzelf gaat spelen
            for ($planRonde = 1; $planRonde <= $maxAantal; $planRonde++) {
                for ($i = 0; $i < $aantalInschrijvingen; $i++) {
                    $this->logger->info("deelnemer: " . $i . ", inschrijving-id: " . $newOrder[$i]->getId() . " planronde: " . $planRonde);
                    // check of deelnemer $i nog meer wedstrijden wil spelen:
                    if (($newOrder[$i]->getAantal() > $newOrder[$i]->getAantalGepland()) && ($newOrder[$i]->getAantalGepland() < $planRonde)) {
                        $offset = 0;
                        while ((2 * $offset) < $aantalInschrijvingen) {
                            // het aantal gecheckte tegenstanders is 2 * de offset;
                            // als alle tegenstanders gecheckt zijn, stop dan voor deze deelnemer
                            $offset++;
                            // kijk vooruit of er nog een tegenstander is die nog meer wedstrijden wil spelen
                            $opponent = $newOrder[($i + $offset) % $aantalInschrijvingen];
                            $this->logger->info("opponent: " . (($i + $offset) % $aantalInschrijvingen) . ", " . $opponent->getId());
                            if ($this->check_combination($toernooiId, $newOrder[$i], $opponent, $category, $planRonde)) {
                                break; // stap uit de while loop
                            }
                            // kijk achteruit of er nog een tegenstander is die nog meer wedstrijden wil spelen:
                            // zorg dat een negatieve modulo remainder positief gemaakt wordt
                            $opponent = $newOrder[(($i - $offset) % $aantalInschrijvingen + $aantalInschrijvingen) % $aantalInschrijvingen];
                            $this->logger->info("opponent: " . ((($i - $offset) % $aantalInschrijvingen + $aantalInschrijvingen) % $aantalInschrijvingen) . ", " . $opponent->getId());
                            if ($this->check_combination($toernooiId, $newOrder[$i], $opponent, $category, $planRonde)) {
                                break; // stap uit de while loop
                            }
                        }
                    }
                }
            }
        }
    }

    /*
     * check_combination() is een hulp-functie voor het algoritme in makeAllCombinations():
     */
    private function check_combination(
        int          $toernooiId,
        SpelerOfTeam $spelerOfTeam,
        SpelerOfTeam $opponent,
        string       $category,
        int          $planRonde): bool
    {
        if ($opponent->getAantal() > $opponent->getAantalGepland()) {
            if (!in_array($opponent->getId(), $spelerOfTeam->getOpponents())) {
                // Dit is een geschikte tegenstander: schrijf een combinatie weg in de database:
                $spelerOfTeam1 = $spelerOfTeam->getId();
                $spelerOfTeam2 = $opponent->getId();
                $spelerOfTeam->incrementAantalGepland();
                $opponent->incrementAantalGepland();
                $spelerOfTeam->addOpponent($spelerOfTeam2);
                $opponent->addOpponent($spelerOfTeam1);

                $combination = new Combinations();
                $combination->setToernooiId($toernooiId);
                $combination->setCategorie($category);
                $combination->setDeelnemer1($spelerOfTeam1);
                $combination->setDeelnemer2($spelerOfTeam2);
                $combination->setPlanRonde($planRonde);
                $this->entityManager->persist($combination);
                $this->entityManager->flush();
                return true;
            }
        }
        return false;
    }

    /**
     * maakVerhinderdeTijdsloten wordt aangeroepen aan het begin van planWedstrijden.
     * Voor alle verhinderingen voor een toernooi wordt bekeken welk(e) tijdslot(en) hierdoor verhinderd zijn
     * voor iedere deelnemer. Voor alle inschrijvingen in dit toernooi worden dan
     * verhinderdeTijdsloten aangemaakt.
     */
    public function maakVerhinderdeTijdsloten(int $toernooiId): array
    {
        // Met een LEFT OUTER JOIN worden alle verhinderingen opgehaald en voor iedere verhindering worden
        // alle inschrijving-id's er bij gezocht die hetzelfde bondsnummer bevatten.
        // Voor iedere verhindering/inschrijving-id combinatie worden één of meer verhinderde tijdsloten aangemaakt

        $verhinderingen = $this->verhinderingRepository->verhinderingenMetInschrijving($toernooiId);

        $speeltijden = $this->speeltijdenRepository->findByToernooiId($toernooiId);

        $verhinderdeTijdsloten = [];

        // Loop alle verhindering/inschrijving combinaties af om verhinderde tijdsloten te maken:
        for ($i = 0; $i < count($verhinderingen); $i++) {
            // vertaal dag en verhindering naar wedstrijd slotnummers
            $dag = $verhinderingen[$i]['dagnummer'];
            $inschrijvingId = $verhinderingen[$i]['inschrijving_id'];
            $speeltijdenStart = round($speeltijden[$dag - 1]->getStarttijd()->getTimestamp() / 60);
            $speeltijdenEind = round($speeltijden[$dag - 1]->getEindtijd()->getTimestamp() / 60);

            $verhStart = round(strtotime($verhinderingen[$i]['begintijd']) / 60);
            $verhEind = round(strtotime($verhinderingen[$i]['eindtijd']) / 60);

            // Het eerste tijdslot heeft waarde 1
            $tijdslot = floor((max($verhStart, $speeltijdenStart) - $speeltijdenStart) / $speeltijden[$dag - 1]->getWedstrijdDuur() + 1);
            $tijdslotBegin = $speeltijdenStart + ($tijdslot - 1) * $speeltijden[$dag - 1]->getWedstrijdDuur();
            $tijdslotEind = $tijdslotBegin + $speeltijden[$dag - 1]->getWedstrijdDuur();

            while (($verhEind > $tijdslotBegin) && ($verhStart < $tijdslotEind) &&
                ($verhStart < $speeltijdenEind) && ($tijdslotEind <= $speeltijdenEind)) {
                $verhinderdeTijdsloten = $this->pushVerhinderdTijdslot($verhinderdeTijdsloten, $toernooiId, $inschrijvingId, $dag, $tijdslot);
                $tijdslot++;
                $tijdslotBegin += $speeltijden[$dag - 1]->getWedstrijdDuur();
                $tijdslotEind += $speeltijden[$dag - 1]->getWedstrijdDuur();
            }
        }
        $this->logger->info("verhinderdeTijdsloten: " . print_r($verhinderdeTijdsloten, true));

        return $verhinderdeTijdsloten;
    }

    // Hulpfunctie om een array met verhinderde tijdsloten op te bouwen:
    private function pushVerhinderdTijdslot(
        $verhinderdeTijdsloten,
        $toernooiId,
        $inschrijvingId,
        $dagNummer,
        $slotNummer): array
    {
        $verhTijdslot =
            ['toernooi_id' => $toernooiId, 'inschrijving_id' => $inschrijvingId,
            'dagnummer' => $dagNummer, 'slotnummer' => $slotNummer];

        // Als de vorige verhindering al in dit tijdslot lag, niet nogmaals toevoegen:
        if (end($verhinderdeTijdsloten) != $verhTijdslot) {
            $verhinderdeTijdsloten[] = $verhTijdslot;
        }
        return $verhinderdeTijdsloten;
    }
}