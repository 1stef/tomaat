<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Toernooi;
use App\Entity\Wedstrijd;
use App\Form\UitslagFormData;
use App\Form\WedstrijdUitslagFormType;
use App\Repository\CategorieRepository;
use App\Repository\CombinationsRepository;
use App\Repository\GegevensRepository;
use App\Repository\ToernooiRepository;
use App\Repository\WedstrijdRepository;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class SpeelController extends AbstractController
{
    private int $toernooiId;
    private Toernooi $toernooi;


    public function __construct(
        private readonly CategorieRepository $categorieRepository,
        private readonly CombinationsRepository $combinationsRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GegevensRepository $gegevensRepository,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly ToernooiRepository $toernooiRepository,
        private readonly WedstrijdRepository $wedstrijdRepository
    ) {
    }

    /*
     * setHuidigToernooi() - De session is leading voor het huidige door de gebruiker geselecteerde toernooi.
     * Haal het huidig toernooi_id uit de session en zet toernooi_id en toernooi als class parameters
     */
    public function setHuidigToernooi(): void
    {
        $this->toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $this->toernooi = $this->toernooiRepository->find($this->toernooiId);
    }

    /**
     * @Route("/dashboard", name="dashboard")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function wedstrijdDashboard(): Response
    {
        $this->setHuidigToernooi();
        $dagnummer = $this->toernooi->getSpeeldag();    // dagnummer 0 is de eerste toernooidag
        $eersteDag = $this->toernooi->getEersteDag();
        $datum = $eersteDag->add(new DateInterval('P' . $dagnummer . 'D'));

        $wedstrijden = $this->combinationsRepository->toonDagWedstrijden($this->toernooiId, $dagnummer + 1);
        $aantal_banen = $this->gegevensRepository->findOneBy(['toernooi_id' => $this->toernooiId])->getAantalBanen();
        $baanbezetting = json_encode($this->wedstrijdRepository->getBaanBezetting($this->toernooiId));

        return $this->render('wedstrijden/dashboard.html.twig', [
            'wedstrijden' => json_encode($wedstrijden),
            'aantal_banen' => $aantal_banen,
            'baanbezetting' => $baanbezetting,
            'datum' => $datum,
        ]);
    }

    /**
     * @Route("/zet_aanwezig/{wedstrijd_id}/{speler}/{cat_type}/{aanwezig}", name="zet_aanwezig")
     * speler moet zijn: "1A", "1B", "2A", of "2B",
     * cat_type moet zijn: "enkel" of "dubbel",
     * aanwezig moet zijn true of false.
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function zetAanwezig(int $wedstrijd_id, string $speler, string $cat_type, bool $aanwezig): Response
    {
        $this->logger->info(
            "zetAanwezig() wedstrijd_id: " . $wedstrijd_id . " speler: " . $speler . " aanwezig: " . $aanwezig
        );
        $wedstrijd = $this->wedstrijdRepository->find($wedstrijd_id);
        if ($wedstrijd) {
            $wedstrijd_status = $wedstrijd->getWedstrijdStatus();
            if (!in_array($wedstrijd_status, ['gepland', 'wachtend'])) {
                // aanwezigheid veranderen als de wedstrijd al gestart of verder is moet niet kunnen
                return new Response();
            }
        }
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$wedstrijd) {
            $wedstrijd = new Wedstrijd();
            $wedstrijd->setWedstrijdId($wedstrijd_id);
            $wedstrijd->setToernooiId($toernooi_id);
        }
        switch ($speler) {
            case "1A":
                $wedstrijd->setAanwezig1A($aanwezig);
                break;
            case "1B":
                $wedstrijd->setAanwezig1B($aanwezig);
                break;
            case "2A":
                $wedstrijd->setAanwezig2A($aanwezig);
                break;
            case "2B":
                $wedstrijd->setAanwezig2B($aanwezig);
                break;
        }
        $wedstrijd->setWedstrijdStatus('gepland');
        if (($wedstrijd->getAanwezig1A() == 1) && ($wedstrijd->getAanwezig2A() == 1)) {
            if (($cat_type == "enkel") || (($wedstrijd->getAanwezig1B() == 1) && ($wedstrijd->getAanwezig2B() == 1))) {
                $wedstrijd->setWedstrijdStatus('wachtend');
                $now = new DateTime();
                $wedstrijd->setWachtstarttijd($now);
            }
        }
        $this->entityManager->persist($wedstrijd);
        $this->entityManager->flush();
        return new Response();
    }

    /**
     * @Route("/start_wedstrijd/{wedstrijd_id}/{baan}", name="start_wedstrijd")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function startWedstrijd(int $wedstrijd_id, int $baan): Response
    {
        $wedstrijd = $this->wedstrijdRepository->find($wedstrijd_id);
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$wedstrijd) {
            $wedstrijd = new Wedstrijd();
            $wedstrijd->setWedstrijdId($wedstrijd_id);
            $wedstrijd->setToernooiId($toernooi_id);
        }
        $wedstrijd->setWedstrijdStatus('spelend');
        $wedstrijd->setBaan($baan);
        $now = new DateTime();
        $wedstrijd->setStarttijd($now);
        $this->entityManager->persist($wedstrijd);
        $this->entityManager->flush();

        $this->addFlash("feedback", "Wedstrijd gestart");
        return $this->json(['redirecturl' => $this->generateUrl('dashboard')]);
    }

    /**
     * @Route("/wedstrijd_uitslag/{wedstrijd_id}", name="wedstrijd_uitslag")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function wedstrijdUitslag(Request $request, int $wedstrijd_id): Response
    {
        $wedstrijd = $this->wedstrijdRepository->find($wedstrijd_id);
        $uitslag = new UitslagFormData();
        $uitslag->setBaan($wedstrijd->getBaan());
        $form = $this->createForm(WedstrijdUitslagFormType::class, $uitslag);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $wedstrijd->copyUitslagData($formData);
            //$wedstrijd = $form->getData();
            $now = new DateTime();
            $wedstrijd->setEindtijd($now);

            $this->entityManager->persist($wedstrijd);
            $this->entityManager->flush();

            $this->addFlash("feedback", "Uitslag opgeslagen");
            return $this->redirectToRoute('dashboard');
        }
        $wedstrijdGegevens = $this->combinationsRepository->getWedstrijdGegevens($wedstrijd_id);
        $vandaag = new DateTime();
        return $this->render('toernooi/wedstrijd_uitslag.html.twig', [
            'wedstrijdUitslagForm' => $form->createView(),
            'datum' => $vandaag,
            'categorie' => $wedstrijdGegevens['categorie'],
            'team1_speler1' => $wedstrijdGegevens['naam_speler1'],
            'team1_speler2' => $wedstrijdGegevens['naam_partner1'],
            'team2_speler1' => $wedstrijdGegevens['naam_speler2'],
            'team2_speler2' => $wedstrijdGegevens['naam_partner2'],
        ]);
    }

    /**
     * @Route("/volgende_speeldag", name="volgende_speeldag")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function volgendeSpeeldag(): Response
    {
        $this->setHuidigToernooi();
        if ($this->toernooi->getSpeeldag() + 1 >= $this->toernooi->getAantalDagen()) {
            $this->addFlash("feedback", "Speeldag ongewijzigd, was al laatste speeldag.");
        } else {
            $this->toernooi->setSpeeldag($this->toernooi->getSpeeldag() + 1);
            $this->addFlash("feedback", "Speeldag vooruit gezet");
        }
        $this->entityManager->persist($this->toernooi);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('dashboard'));
    }

    /**
     * @Route("/vorige_speeldag", name="vorige_speeldag")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function vorigeSpeeldag(): Response
    {
        $this->setHuidigToernooi();
        if ($this->toernooi->getSpeeldag() <= 0) {
            $this->addFlash("feedback", "Speeldag ongewijzigd, was al eerste speeldag.");
        } else {
            $this->toernooi->setSpeeldag($this->toernooi->getSpeeldag() - 1);
            $this->addFlash("feedback", "Speeldag terug gezet");
        }
        $this->entityManager->persist($this->toernooi);
        $this->entityManager->flush();

        return $this->redirect($this->generateUrl('dashboard'));
    }

    /**
     * @Route("/zoek_wedstrijd", name="zoek_wedstrijd")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function zoekWedstrijd(): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $categorieen = $this->categorieRepository->findBy(['toernooi_id' => $toernooi_id]);
        $toernooidagen = $this->toernooiRepository->getToernooiDagen($toernooi_id);
        $wedstrijden = $this->combinationsRepository->toonWedstrijden($toernooi_id);

        return $this->render('wedstrijden/zoek_wedstrijd.html.twig', [
            'wedstrijden' => json_encode($wedstrijden),
            'categorieen' => $categorieen,
            'toernooidagen' => $toernooidagen,
        ]);
    }

    /*
     * @Route("/reserveer_tijdslot/{wedstrijd_id}/{oude_tijdslot}/{tijdslot_id}", name="reserveer_tijdslot")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     *
    public function reserveerTijdslot($wedstrijd_id, $oude_tijdslot, $tijdslot_id, ReserveringRepository $reserveringRepository,
                                      MySQLDB $mysqlDB): Response
    {
        // geef huidig toegekende tijdslot voor de wedstrijd vrij en zet nieuwe tijdslot bezet
        $mysqlDB->reserveerTijdslot($wedstrijd_id, $tijdslot_id);
        // check of er al een reservering voor dit tijdslot bestaat
        $res = $reserveringRepository->findOneBy(['wedstrijd_id' => $wedstrijd_id]);
        $this->logger->info("reserveerTijdslot ".print_r($res, true));
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        // zo niet, maak een nieuwe reservering aan
        if ($res == null){
            $res = new Reservering();
            $res->setToernooiId($toernooi_id);
            $res->setWedstrijdId($wedstrijd_id);
            if ($oude_tijdslot > 0) {
                $res->setOudeTijdslot($oude_tijdslot);
            }
        }
        $res->setResStatus('nieuw');
        $res->setTijdslotId($tijdslot_id);
        $res->setSpeler1Bevestigd(false);
        $res->setPartner1Bevestigd(false);
        $res->setSpeler2Bevestigd(false);
        $res->setPartner2Bevestigd(false);
        // sla de nieuwe of gewijzigde reservering op:
        $this->entityManager->persist($res);
        $this->entityManager->flush();

        $this->logger->info("reserveerTijdslot stap 2: ".print_r($res, true));

        return $this->redirectToRoute('toon_reserveringen');
    }
    */

    /*
     * @Route("/toon_reserveringen", name="toon_reserveringen")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     *
    public function toonReserveringen(MySQLDB $mysqlDB): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $herplan_reserveringen = $mysqlDB->getHerplanReserveringen($toernooi_id);
        $herplan_deelnemers = $mysqlDB->getHerplanDeelnemers($toernooi_id);

        // toon het overzicht van reserveringen
        return $this->render('wedstrijden/herplan_reserveringen.html.twig', [
            'herplan_reserveringen' => $herplan_reserveringen,
            'herplan_deelnemers' => $herplan_deelnemers,
        ]);
    }
    */

    /*
     * @Route("zet_verplaatsing_ok_nok/{reservering_id}/{speler}/{bevestiging}", name="zet_verplaatsing_ok_nok")
     * speler moet zijn: "speler1", "partner1", "speler2", of "partner2",
     * bevestiging moet zijn true of false.
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     *
    public function zet_verplaatsing_ok_nok($reservering_id, $speler, bool $bevestiging, ReserveringRepository $reserveringRepository): Response
    {
        $this->logger->info("zet_verplaatsing_ok_nok reservering_id: ".$reservering_id." speler: ".$speler." bevestiging: ".$bevestiging);
        $res = $reserveringRepository->find($reservering_id);
        switch($speler){
            case "speler1" : $res->setSpeler1Bevestigd($bevestiging);
                break;
            case "partner1" : $res->setPartner1Bevestigd($bevestiging);
                break;
            case "speler2" : $res->setSpeler2Bevestigd($bevestiging);
                break;
            case "partner2" : $res->setPartner2Bevestigd($bevestiging);
                break;
        }
        // sla de gewijzigde reservering op:
        $this->entityManager->persist($res);
        $this->entityManager->flush();

        $this->logger->info("zet_verplaatsing_ok_nok: ".print_r($res, true));
        return new Response();
    }
     */

    /*
     * @Route("/verplaats_definitief/{reservering_id}", name="verplaats_definitief")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     *
    public function verplaatsDefinitief($reservering_id, ReserveringRepository $reserveringRepository, MySQLDB $mysqlDB): Response
    {
        $res = $reserveringRepository->find($reservering_id);
        // geef het oude tijdslot vrij en voer het nieuwe tijdslot door:
        $mysqlDB->verplaatsDefinitief($res->getWedstrijdId(), $res->getTijdslotId());
        // zet de reservering op definief
        $res->setResStatus("definitief");
        // sla de nieuwe reservering op:
        $this->entityManager->persist($res);
        $this->entityManager->flush();
        $this->addFlash("feedback", "Wedstrijd verplaatst!");
        return $this->redirectToRoute('toon_reserveringen');
    }
     */

    /*
     * @Route("/ander_tijdstip/{reservering_id}", name="ander_tijdstip")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     *
    public function anderTijdstip($reservering_id, ReserveringRepository $reserveringRepository, MySQLDB $mysqlDB): Response
    {
        $res = $reserveringRepository->find($reservering_id);
        $wedstrijd_id = $res->getWedstrijdId();
        // geef het gereserveerde tijdslot vrij:
        $mysqlDB->geefTijdslotVrij($res->getTijdslotId());
        // zet status "anderTijdstip" in de tijdslotreservering:
        $res->setResStatus("anderTijdstip");
        $this->entityManager->persist($res);
        $this->entityManager->flush();
        // laat scherm zien om een ander tijdslot te reserveren
        $this->addFlash("feedback", "Herplan-reservering verwijderd. Kies een ander tijdstip");
        return $this->redirectToRoute('verplaats_wedstrijd', ['wedstrijd_id' => $wedstrijd_id]);
    }
     */

    /*
     * @Route("/verwijder_reservering/{reservering_id}", name="verwijder_reservering")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     *
    public function verwijderReservering($reservering_id, ReserveringRepository $reserveringRepository, MySQLDB $mysqlDB): Response
    {
        $res = $reserveringRepository->find($reservering_id);
        // geef het gereserveerde tijdslot vrij:
        $mysqlDB->geefTijdslotVrij($res->getTijdslotId());
        // zet status "verwijderd" in de tijdslotreservering:
        $res->setResStatus("verwijderd");
        $this->entityManager->persist($res);
        $this->entityManager->flush();
        $this->addFlash("feedback", "Wedstrijd definitief verwijderd");
        return $this->redirectToRoute('toon_reserveringen');
    }
     */

    /*
     * @route("/voegCombinatieToeEnZoekTijdslot/{categorie}/{inschrijving_id_1}/{inschrijving_id_2}", name="voeg_combinatie_toe")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     *
    public function voegCombinatieToeEnZoekTijdslot($categorie, $inschrijving_id_1, $inschrijving_id_2, MySQLDB $mysqlDB){
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        // TODO: check of categorie voor inschrijving_id_1 en inschrijving_id_2 hetzelfde zijn en geldig voor dit toernooi
        // Maak een nieuw combinations-record aan
        $combinations_id = $mysqlDB->AddCombination($toernooi_id, $categorie, $inschrijving_id_1, $inschrijving_id_2);
        if ($combinations_id > 0){
            // Roep verplaatsWedstrijd() aan met het id van de nieuwe combinations-record:
            return $this->wedstrijdWijzigingAdminController->verplaatsWedstrijd($combinations_id, $mysqlDB);
        } else if ($combinations_id == -1) {
            $this->addFlash("feedback", "Deze combinatie heeft al een wedstrijd");
            return $this->redirectToRoute('voeg_wedstrijd_toe');
        } else {
            $this->logger->alert("SECURITY ALERT: voegCombinatieToeEnZoekTijdslot probeerde illegale combination toe te voegen");
            $this->addFlash("feedback", "Fout");
            return $this->redirectToRoute('home');
        }
    }
     */
}


