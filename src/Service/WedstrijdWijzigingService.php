<?php
declare(strict_types = 1);

namespace App\Service;

use App\Entity\Bericht;
use App\Entity\HerplanOptie;
use App\Entity\Toernooi;
use App\Entity\WedstrijdWijziging;
use App\Repository\CombinationsRepository;
use App\Repository\DeelnemerRepository;
use App\Repository\HerplanOptieRepository;
use App\Repository\InschrijvingRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\TijdslotRepository;
use App\Repository\ToernooiRepository;
use App\Repository\UserRepository;
use App\Repository\VerhinderingRepository;
use App\Repository\WedstrijdWijzigingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class WedstrijdWijzigingService
{
    public function __construct(
        private readonly CombinationsRepository       $combinationsRepository,
        private readonly DeelnemerRepository          $deelnemerRepository,
        private readonly EntityManagerInterface       $entityManager,
        private readonly HerplanOptieRepository       $herplanOptieRepository,
        private readonly InschrijvingRepository       $inschrijvingRepository,
        private readonly LoggerInterface              $logger,
        private readonly MailerInterface              $mailer,
        private readonly RequestStack                 $requestStack,
        private readonly SpeeltijdenRepository        $speeltijdenRepository,
        private readonly ToernooiRepository           $toernooiRepository,
        private readonly TijdslotRepository           $tijdslotRepository,
        private readonly UserRepository               $userRepository,
        private readonly VerhinderingRepository       $verhinderingRepository,
        private readonly WedstrijdWijzigingRepository $wedstrijdWijzigingRepository)
    {
    }

    /**
     * verwijderHerplanOpties() is een hulpfunctie voor wizard_wijzig_wedstrijd_3() en wizard_wijzig_wedstrijd_3_ontvanger().
     * De functie verwijdert de herplan-opties van een wedstrijd_wijziging, behalve de herplan-optie met id $bewaar_optie_id.
     * Zet $bewaar_optie_id op 0 om alle herplan-opties voor de wedstrijd_wijziging te verwijderen.
     */
    public function verwijderHerplanOpties(WedstrijdWijziging $wedstrijd_wijziging, int $bewaar_optie_id): void
    {
        $herplan_opties = $this->herplanOptieRepository->findBy(['wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId()]);
        foreach ($herplan_opties as $herplan_optie) {
            if ($herplan_optie->getId() != $bewaar_optie_id) {
                $this->entityManager->remove($herplan_optie);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * maakBericht() is een hulpfunctie voor wizard_wijzig_wedstrijd_3() en wizard_wijzig_wedstrijd_3_ontvanger()
     * Het maakt een nieuw bericht aan voor de andere spelers en toernooileiding en vult de meeste velden.
     * De titel moet nog gezet worden
     * Geeft het nieuwe bericht terug.
     * $afzender is de user-id, of 0 als de toernooileiding de afzender is.
     */
    public function maakBericht(
        WedstrijdWijziging $wedstrijdWijziging,
        string             $titel,
        string             $type,
        int                $afzender): Bericht
    {
        $bericht = new Bericht();
        $bericht->setTitel($titel);
        $bericht->setToernooiId($this->requestStack->getSession()->get('huidig_toernooi_id'));
        $bericht->setWedstrijdWijzigingId($wedstrijdWijziging->getId());
        $bericht->setAfzender($afzender);
        // Om de ontvangers te bepalen, haal de user_ids van de deelnemers aan deze wedstrijd op:
        $deelnemers = $this->combinationsRepository->getUserIds($wedstrijdWijziging->getWedstrijdId());
        // verwijder de user_id van de afzender
        if (($key = array_search(['user_id' => $afzender], $deelnemers)) !== false) {
            unset($deelnemers[$key]);
            $deelnemers = array_values($deelnemers);
        }
        if (count($deelnemers) > 0) {
            $bericht->setOntvanger1($deelnemers[0]['user_id']);
        }
        if (count($deelnemers) > 1) {
            $bericht->setOntvanger2($deelnemers[1]['user_id']);
        }
        if (count($deelnemers) > 2) {
            $bericht->setOntvanger3($deelnemers[2]['user_id']);
        }
        // Alleen voor bericht over toevoegen wedstrijd:
        if (count($deelnemers) > 3) {
            $bericht->setOntvanger4($deelnemers[3]['user_id']);
        }
        $bericht->setCcToernooileiding(true);
        $bericht->setVanToernooileiding($afzender == 0);
        $bericht->setAlleDeelnemers(false);
        $bericht->setBerichttype($type);
        // sla het nieuwe bericht op
        $this->entityManager->persist($bericht);
        $this->entityManager->flush();

        return $bericht;
    }

    /**
     * setAkoord() is een hulpfunctie voor wizard_wijzig_wedstrijd_3() en wizard_wijzig_wedstrijd_3_ontvanger()
     */
    public function setAkkoord(
        WedstrijdWijziging $wedstrijdWijziging,
        HerplanOptie $herplan_optie,
        int $user_id,
        bool $akkoord): bool
    {
        $this->logger->info("setAkkoord");

        switch ($user_id) {
            case $wedstrijdWijziging->getSpeler1():
                $herplan_optie->setSpeler1Akkoord($akkoord);
                break;
            case $wedstrijdWijziging->getPartner1():
                $herplan_optie->setPartner1Akkoord($akkoord);
                break;
            case $wedstrijdWijziging->getSpeler2():
                $herplan_optie->setSpeler2Akkoord($akkoord);
                break;
            case $wedstrijdWijziging->getPartner2():
                $herplan_optie->setPartner2Akkoord($akkoord);
                break;
            default:
                // mag niet voorkomen
        }
        $this->entityManager->persist($herplan_optie);
        $this->entityManager->flush();

        // de return value geeft aan of iedere speler nu akkoord is:
        $iedereen_akkoord = false;
        if ($wedstrijdWijziging->getCatType() == "enkel") {
            if ($herplan_optie->getSpeler1Akkoord() && $herplan_optie->getSpeler2Akkoord()) {
                $iedereen_akkoord = true;
            }
        } else {
            if ($herplan_optie->getSpeler1Akkoord() && $herplan_optie->getSpeler2Akkoord() &&
                $herplan_optie->getPartner1Akkoord() && $herplan_optie->getPartner2Akkoord()) {
                $iedereen_akkoord = true;
            }
        }
        if ($iedereen_akkoord) {
            $wedstrijdWijziging->setWijzigingStatus("definitief");
            if ($wedstrijdWijziging->getActie() == "afzeggen") {
                // bij afzeggen, zet het tijdslot op NULL voor deze wedstrijd
                //$this->mysqlDB->verplaatsDefinitief($wedstrijdWijziging->getWedstrijdId(), "NULL");
                $combination = $this->combinationsRepository->find($wedstrijdWijziging->getWedstrijdId());
                $combination->setTijdslot(null);
                $this->entityManager->persist($combination);
                $tijdslotOud = $this->tijdslotRepository->find($wedstrijdWijziging->getTijdslotOud());
                $tijdslotOud->setVrij(true);
                $this->entityManager->persist($tijdslotOud);
            }
        } else {
            $wedstrijdWijziging->setWijzigingStatus("verstuurd");
        }
        $this->entityManager->persist($wedstrijdWijziging);
        $this->entityManager->flush();
        return $iedereen_akkoord;
    }

    /**
     * @return HerplanOptie[]
     */
    public function getHerplanOpties(
        WedstrijdWijziging $wedstrijdWijziging,
        Toernooi $toernooi,
        bool $ookDubbeleWedstrijden): array
    {
        $toernooiId = $toernooi->getId();
        $eersteDagnummer = max(1, floor((time() - $toernooi->getEersteDag()->getTimestamp()) / (60 * 60 * 24)) + 2);
        $this->logger->info("getHerplanOpties, eerste_dagnummer: " . $eersteDagnummer);
        $vrijeTijdsloten = $this->tijdslotRepository->getVrijeTijdsloten($toernooiId);
        // haal de bondsnummers voor de deelnemers aan deze wedstrijd op als string:
        $bondsnummers = $this->combinationsRepository->getBondsnummers($wedstrijdWijziging->getWedstrijdId());
        // haal de andere geplande wedstrijden voor deze bondsnummers op:
        $geplandeWedstrijden = $this->combinationsRepository->getWedstrijden($toernooiId, $bondsnummers);
        // haal de verhinderingen voor deze bondsnummers op op:
        $verhinderingen = $this->verhinderingRepository->getAlleVerhinderingen($toernooiId, $bondsnummers);
        // Haal de wedstrijdgegevens op
        $this->verwijderHerplanOpties($wedstrijdWijziging, 0);
        $dubbeleWedstrijden = [];
        $herplanOpties = [];
        //$this->logger->info("getHerplanOpties, vrije tijdsloten: ".print_r($vrijeTijdsloten));
        foreach ($vrijeTijdsloten as $tijdslot) {
            if ($tijdslot['dagnummer'] >= $eersteDagnummer) {
                $volgendeTijdslot = false;
                if (!$volgendeTijdslot) {
                    // We herplannen nooit over een verhindering heen:
                    foreach ($verhinderingen as $verhindering) {
                        $tijdslotStart = strtotime($tijdslot['starttijd']);
                        $tijdslotEind = strtotime($tijdslot['eindtijd']);
                        $verhinderingBegin = strtotime($verhindering['begintijd']);
                        $verhinderingEind = strtotime($verhindering['eindtijd']);
                        if ($verhindering['dagnummer'] == $tijdslot['dagnummer']) {
                            $this->logger->info("getHerplanOpties(), vd, td, verh_st, verh_ei, tijdsl_st, tijdsl_ei: " . $verhindering['dagnummer'] . ", " . $tijdslot['dagnummer'] . ", " . $verhinderingBegin .
                                ", " . $verhinderingEind . ", " . $tijdslotStart . ", " . $tijdslotEind . "!");
                            if (($tijdslotStart >= $verhinderingBegin && $tijdslotStart < $verhinderingEind) ||
                                ($tijdslotEind > $verhinderingBegin && $tijdslotEind <= $verhinderingEind)) {
                                // dit tijdslot valt samen met een verhindering
                                $volgendeTijdslot = true;
                                $this->logger->info("getHerplanOpties(): verhindering valt samen met tijdslot");
                                break;
                            }
                        }
                    }
                }
                if (!$volgendeTijdslot) {
                    foreach ($geplandeWedstrijden as $geplandeWedstrijd) {
                        // We herplannen nooit over een geplande wedstrijd voor één van de deelnemers heen:
                        if (($geplandeWedstrijd['dagnummer'] == $tijdslot['dagnummer']) &&
                            ($geplandeWedstrijd['starttijd'] == $tijdslot['starttijd'])) {
                            // ga naar volgende tijdslot
                            $volgendeTijdslot = true;
                            break;
                        }
                    }
                }
                if (!$volgendeTijdslot) {
                    foreach ($geplandeWedstrijden as $geplandeWedstrijd) {
                        // Als het niet anders kan, bieden we wel opties aan waarbij er meer dan 1 wedstrijd per dag gespeeld wordt:
                        if (($geplandeWedstrijd['wedstrijd_id'] != $wedstrijdWijziging->getWedstrijdId()) &&
                            ($geplandeWedstrijd['dagnummer'] == $tijdslot['dagnummer'])) {
                            $dubbeleWedstrijden[] =
                                [
                                    'vrij_tijdslot' => $tijdslot['eerste_tijdslot_id'],
                                    'naam' => $geplandeWedstrijd['naam'],
                                    'user_id' => $geplandeWedstrijd['user_id']
                                ];
                            $this->logger->info("getHerplanOpties(), geplande_wedstrijd: " . print_r($geplandeWedstrijd, true));
                            $this->logger->info("getHerplanOpties(), tijdslot: " . print_r($tijdslot, true));
                            // ga naar volgende tijdslot
                            $volgendeTijdslot = true;
                        }
                    }
                }
                if (!$volgendeTijdslot) {
                    // dit is een vrij tijdslot zonder geplande wedstrijd op dezelfde dag of verhinderingen
                    // TODO: presenteer dit vrije tijdslot als beste optie voor verplaatsen
                    $wedstrijdWijziging->setTijdslotNieuw($tijdslot['eerste_tijdslot_id']);
                    $herplanOptie = new HerplanOptie();
                    $wedstrijdWijziging->setHerplanOptieId($herplanOptie->getId());
                    $herplanOptie->setWedstrijdWijzigingId($wedstrijdWijziging->getId());
                    $herplanOptie->setToernooiId($toernooi->getId());
                    $herplanOptie->setTijdslot($tijdslot['eerste_tijdslot_id']);
                    //$tijdslot_gegevens = $mysqlDB->getTijdslotGegevens($tijdslot['eerste_tijdslot_id']);
                    //$herplanOptie->setDagnummer($tijdslot_gegevens['dagnummer']);
                    //$herplanOptie->setStarttijd($tijdslot_gegevens['starttijd']);
                    $tijdslot = $this->tijdslotRepository->find($tijdslot['eerste_tijdslot_id']);
                    $herplanOptie->setDagnummer($tijdslot->getDagnummer());
                    $herplanOptie->setStarttijd($tijdslot->getStarttijd());
                    $this->entityManager->persist($wedstrijdWijziging);
                    $this->entityManager->persist($herplanOptie);
                    $this->entityManager->flush();
                    $herplanOpties[] = $herplanOptie;

                    return $herplanOpties;
                }
            }
        }
        // Hier aangekomen is geen vrij tijdslot zonder geplande wedstrijd op dezelfde dag of verhinderingen gevonden.
        if (!empty($dubbeleWedstrijden) && $ookDubbeleWedstrijden) {
            $this->logger->info("dubbele wedstrijden: " . print_r($dubbeleWedstrijden, true));
            // Verwijder de tijdsloten waarbij meer dan 1 speler al een wedstrijd speelt op die dag,
            // dus de tijdsloten die meer dan 1x voorkomen in de array dubbele_wedstrijden:
            $aantal = count($dubbeleWedstrijden);
            $ontdubbeld = [];
            for ($i = 0; $i < $aantal; $i++) {
                if ($i + 1 == $aantal) {
                    $ontdubbeld[] = $dubbeleWedstrijden[$i];
                } elseif ($dubbeleWedstrijden[$i]['vrij_tijdslot'] != $dubbeleWedstrijden[$i + 1]['vrij_tijdslot']) {
                    $ontdubbeld[] = $dubbeleWedstrijden[$i];
                } else {
                    while ($i + 1 < $aantal) {
                        if ($dubbeleWedstrijden[$i]['vrij_tijdslot'] == $dubbeleWedstrijden[$i + 1]['vrij_tijdslot']) {
                            $i++;
                        } else {
                            break;
                        }
                    }
                }
            }
            $this->logger->info("dubbele wedstrijden voor 1 speler: " . print_r($ontdubbeld, true));
            // Maak een herplan_optie aan voor iedere dubbele wedstrijd:
            foreach ($ontdubbeld as $dubbele_wedstrijd) {
                $herplanOptie = new HerplanOptie();
                $herplanOptie->setWedstrijdWijzigingId($wedstrijdWijziging->getId());
                $herplanOptie->setToernooiId($toernooi->getId());
                $herplanOptie->setTijdslot($dubbele_wedstrijd['vrij_tijdslot']);
                //$tijdslot_gegevens = $mysqlDB->getTijdslotGegevens($dubbele_wedstrijd['vrij_tijdslot']);
                //$herplanOptie->setDagnummer($tijdslot_gegevens['dagnummer']);
                //$herplanOptie->setStarttijd($tijdslot_gegevens['starttijd']);
                $tijdslot = $this->tijdslotRepository->find($dubbele_wedstrijd['vrij_tijdslot']);
                $herplanOptie->setDagnummer($tijdslot->getDagnummer());
                $herplanOptie->setStarttijd($tijdslot->getStarttijd());
                $herplanOptie->setNaamMetWedstrijd($dubbele_wedstrijd['naam']);
                $herplanOptie->setUserIdMetWedstrijd($dubbele_wedstrijd['user_id']);
                $this->entityManager->persist($herplanOptie);
                $herplanOpties[] = $herplanOptie;
            }
            $this->entityManager->flush();
        }
        return $herplanOpties;
    }

    /**
     * Dit is de functie die de echte mails uitstuurt naar de ontvangers van een bericht
     */
    public function verstuur_berichtmails(Bericht $bericht): void
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi_naam = $this->requestStack->getSession()->get('huidig_toernooi');
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        $blokkeer_mails = ($toernooi->getBlokkeerMails() == 1);
        if ($blokkeer_mails) {
            $this->requestStack->getSession()->getFlashBag()->add('feedback', "Geen mails verstuurd, dit toernooi is aangemerkt als test-toernooi");
            return;
        }
        if (!empty($_ENV['SEND_MAILS']) && $_ENV['SEND_MAILS'] == "off") {
            $this->requestStack->getSession()->getFlashBag()->add('feedback', "Geen mails verstuurd vanuit development omgeving");
            return;
        }
        $ontvangers = [];
        if ($bericht->getOntvanger1() > 0) {
            $user_id = $bericht->getOntvanger1();
            $ontvangers[] = ['email' => $this->userRepository->find($user_id)->getEmail(),
                'naam' => $this->deelnemerRepository->find($user_id)->getNaam()];
        }
        if ($bericht->getOntvanger2() > 0) {
            $user_id = $bericht->getOntvanger2();
            $ontvangers[] = ['email' => $this->userRepository->find($user_id)->getEmail(),
                'naam' => $this->deelnemerRepository->find($user_id)->getNaam()];
        }
        if ($bericht->getOntvanger3() > 0) {
            $user_id = $bericht->getOntvanger3();
            $ontvangers[] = ['email' => $this->userRepository->find($user_id)->getEmail(),
                'naam' => $this->deelnemerRepository->find($user_id)->getNaam()];
        }
        if ($bericht->getOntvanger4() > 0) {
            $user_id = $bericht->getOntvanger4();
            $ontvangers[] = ['email' => $this->userRepository->find($user_id)->getEmail(),
                'naam' => $this->deelnemerRepository->find($user_id)->getNaam()];
        }
        if ($bericht->getVanToernooileiding()) {
            $afzender = 'toernooileiding';
        } else {
            $afzender = $this->deelnemerRepository->find($bericht->getAfzender())->getNaam();
        }
        foreach ($ontvangers as $ontvanger) {
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@toernooiopmaat.nl', 'Wijziging Toernooi Op Maat wedstrijd'))
                ->to(new Address($ontvanger['email']))
                ->subject($bericht->getTitel())
                ->htmlTemplate('berichten/bericht_mail.html.twig')
                ->context([
                    'naam' => $ontvanger['naam'],
                    'afzender' => $afzender,
                    'titel' => $bericht->getTitel(),
                    'bericht_id' => $bericht->getId(),
                    'toernooi_naam' => $toernooi_naam,
                ]);
            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                // some error prevented the email sending; display an
                // error message or try to resend the message
                $this->logger->error("verstuur_berichtmails: fout bij verzenden berichtmail: ", $e->getMessage());
            }
            $this->logger->info("verstuur_berichtmails: mail verzonden aan: " . $ontvanger['email']);
        }
    }

    /**
     * De interne functie voor maak_afzegging() en maak_verplaatsing().
     * Stelt de toernooiadministrator in staat om een verplaatsing of afzegging in te voeren voor een deelnemer
     * Return waarde: array ['wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId(), 'bericht_id' => $bericht->getId()]
     */
    public function maak_wedstrijd_wijziging(
        int $wedstrijd_id,
        ?int $nieuwe_tijdslot,
        int $indiener,
        string $actie): array
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');

        // Kijk eerst of er al een wedstrijd_wijziging met herplan_optie is voor deze wedstrijd en zo ja,
        // verwijder zowel de wedstrijd_wijziging als de herplan_optie
        $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->findOneBy(['wedstrijd_id' => $wedstrijd_id, 'wijziging_status' => 'verstuurd']);
        if ($wedstrijd_wijziging) {
            $herplan_optie = $this->herplanOptieRepository->find($wedstrijd_wijziging->getHerplanOptieId());
            if ($herplan_optie) {
                $this->entityManager->remove($herplan_optie);
            }
            $this->entityManager->remove($wedstrijd_wijziging);
            $this->entityManager->flush();
        }

        // maak een wedstrijd_wijziging aan met dit wedstrijd_id
        $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens($wedstrijd_id);
        $wedstrijd_wijziging = new WedstrijdWijziging();
        $wedstrijd_wijziging->setToernooiId($toernooi_id);
        $wedstrijd_wijziging->setIndiener($indiener);
        $wedstrijd_wijziging->setSpeler1($wedstrijd_gegevens['speler1']);
        $wedstrijd_wijziging->setPartner1($wedstrijd_gegevens['partner1']);
        $wedstrijd_wijziging->setSpeler2($wedstrijd_gegevens['speler2']);
        $wedstrijd_wijziging->setPartner2($wedstrijd_gegevens['partner2']);
        $wedstrijd_wijziging->setWijzigingStatus("nieuw");
        $wedstrijd_wijziging->setActie($actie);
        $wedstrijd_wijziging->setIndienerVeranderd(false);
        $wedstrijd_wijziging->setWedstrijdId($wedstrijd_id);
        $wedstrijd_wijziging->setCatType($wedstrijd_gegevens['cat_type']);
        $wedstrijd_wijziging->setTijdslotOud($wedstrijd_gegevens['tijdslot']);
        if ($actie == "verplaatsen") {
            $wedstrijd_wijziging->setTijdslotNieuw($nieuwe_tijdslot);
        }
        $this->entityManager->persist($wedstrijd_wijziging);
        $this->entityManager->flush();

        // maak een herplan-optie voor deze wedstrijdwijziging (verplaatsen wedstrijd) aan:
        $herplan_optie = new HerplanOptie();
        $herplan_optie->setWedstrijdWijzigingId($wedstrijd_wijziging->getId());
        $herplan_optie->setToernooiId($toernooi_id);
        if ($actie == "verplaatsen") {
            $herplan_optie->setTijdslot($nieuwe_tijdslot);
            //$tijdslot_gegevens = $this->mysqlDB->getTijdslotGegevens($nieuwe_tijdslot);
            //$herplan_optie->setDagnummer($tijdslot_gegevens['dagnummer']);
            //$herplan_optie->setStarttijd($tijdslot_gegevens['starttijd']);
            $tijdslot = $this->tijdslotRepository->find($nieuwe_tijdslot);
            $herplan_optie->setDagnummer($tijdslot->getDagnummer());
            $herplan_optie->setStarttijd($tijdslot->getStarttijd());

        }
        $this->entityManager->persist($herplan_optie);
        $this->entityManager->flush();

        $wedstrijd_wijziging->setHerplanOptieId($herplan_optie->getId());
        $this->entityManager->persist($wedstrijd_wijziging);

        $iedereen_akkoord = $this->setAkkoord($wedstrijd_wijziging, $herplan_optie, $indiener, true);

        // Maak een bericht aan voor de spelers en toernooileiding
        if ($actie == "verplaatsen") {
            $titel = "Wedstrijd moet verplaatst worden";
            $bericht = $this->maakBericht($wedstrijd_wijziging, $titel, "ww-verplaatsen", 0);
        } else {
            $titel = "Wedstrijd moet afgezegd worden";
            $bericht = $this->maakBericht($wedstrijd_wijziging, $titel, "ww-afzeggen", 0);
        }
        $bericht->setAfzender($indiener);
        $this->entityManager->persist($bericht);
        $this->entityManager->flush();

        // Geef de parameters terug voor om een toelichting op te kunnen vragen en het bericht te kunnen versturen:
        return ['wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId(), 'bericht_id' => $bericht->getId()];
    }

}