<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HerplanOptie;
use App\Entity\WedstrijdWijziging;
use App\Form\ToelichtingType;
use App\Repository\BerichtRepository;
use App\Repository\CombinationsRepository;
use App\Repository\DeelnemerRepository;
use App\Repository\HerplanOptieRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\TijdslotRepository;
use App\Repository\ToernooiRepository;
use App\Repository\VerhinderingRepository;
use App\Repository\WedstrijdWijzigingRepository;
use App\Service\WedstrijdWijzigingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class WedstrijdWijzigingSpelerController extends AbstractController
{
    public function __construct(
        private readonly BerichtRepository $berichtRepository,
        private readonly CombinationsRepository $combinationsRepository,
        private readonly DeelnemerRepository $deelnemerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HerplanOptieRepository $herplanOptieRepository,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly SpeeltijdenRepository $speeltijdenRepository,
        private readonly ToernooiRepository $toernooiRepository,
        private readonly TijdslotRepository $tijdslotRepository,
        private readonly VerhinderingRepository $verhinderingRepository,
        private readonly WedstrijdWijzigingRepository $wedstrijdWijzigingRepository,
        private readonly WedstrijdWijzigingService $wedstrijdWijzigingService
    ) {
    }

    /**
     * @Route("/wijzig_verhinderingen/{wedstrijd_wijziging_id}", name="wijzig_verhinderingen")
     * @IsGranted("ROLE_USER")
     * @IsGranted("INSCHRIJVEN_SPELEN")
     *
     * Laat de deelnemer verhinderingen wijzigen bij het inschrijven en als het toernooi gestart is en de wedstrijden gepland zijn
     */
    public function wijzig_verhinderingen(int $wedstrijd_wijziging_id): Response
    {
        // Bepaal het huidige toernooi_id (moet bekend zijn als aan is_granted('INSCHRIJVEN_SPELEN') is voldaan)
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi_id = ($toernooi_id == null ? 0 : $toernooi_id);
        $user = $this->getUser();
        $deelnemer = $this->deelnemerRepository->find($user->getId());
        if (!$deelnemer) {
            $this->addFlash('feedback', 'U bent geen deelnemer aan dit toernooi');
            return $this->redirectToRoute('home');
        }
        // TODO: controleer of de user ook ingeschreven is voor dit toernooi
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        $speeltijden = json_encode($this->speeltijdenRepository->getSpeeltijden($toernooi_id));
        $verhinderingen = $this->verhinderingRepository->getVerhinderingen($toernooi_id, $deelnemer->bondsnummer);

        return $this->render(
            'inschrijving/wijzig_verhinderingen.html.twig',
            [
                'speeltijden' => $speeltijden,
                'verhinderingen' => $verhinderingen,
                'toernooi' => $toernooi,
                'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id
            ]
        );
    }

    /**
     * @Route("/wizard_wijzig_wedstrijd_1", name="wizard_wijzig_wedstrijd_1")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * Laat de deelnemer wedstrijden wijzigen als het toernooi gestart is en de wedstrijden gepland zijn
     */
    public function wizard_wijzig_wedstrijd_1(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooi_id) {
            // Dit kan alleen als de gebruiker de url in de browser heeft ingevoerd
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        $user = $this->getUser();
        $deelnemerNaam = $this->deelnemerRepository->find($user->getId())->getNaam();
        // TODO: toon alleen wedstrijden die nog verplaatst kunnen worden, dus later gepland dan vandaag
        $wedstrijden = $this->combinationsRepository->toonDeelnemerWedstrijden($toernooi_id, $user->getId());
        setlocale(LC_ALL, 'nld_nld');
        return $this->render('wijzigingen/wijzig_wedstrijd.html.twig', [
            'wedstrijden' => $wedstrijden,
            'naam' => $deelnemerNaam,
            'toernooi' => $toernooi,
        ]);
    }

    /**
     * @Route("/wizard_wijzig_wedstrijd_2/{wedstrijd_id}/{actie}", name="wizard_wijzig_wedstrijd_2")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * De gebruiker heeft een wedstrijd en een actie geselecteerd.
     * Maak een wedstrijd_wijziging aan en vraag om de verhinderingen aan te passen.
     */
    public function wizard_wijzig_wedstrijd_2(int $wedstrijd_id, string $actie): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        // de gebruiker heeft een wedstrijd en een actie geselecteerd of gecanceld
        if (($actie == "verplaatsen") || ($actie == "afzeggen")) {
            // TODO: check of wedstrijd_id geldig is
            // maak een wedstrijd_wijziging aan met dit wedstrijd_id
            $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens($wedstrijd_id);
            //$tijdslot_gegevens = $mysqlDB->getTijdslotGegevens($wedstrijd_gegevens['tijdslot']);
            $tijdslot = $this->tijdslotRepository->find($wedstrijd_gegevens['tijdslot']);
            $wedstrijd_wijziging = new WedstrijdWijziging();
            $wedstrijd_wijziging->setToernooiId($toernooi_id);
            $wedstrijd_wijziging->setIndiener($this->getUser()->getId());
            $wedstrijd_wijziging->setSpeler1($wedstrijd_gegevens['speler1']);
            $wedstrijd_wijziging->setPartner1($wedstrijd_gegevens['partner1']);
            $wedstrijd_wijziging->setSpeler2($wedstrijd_gegevens['speler2']);
            $wedstrijd_wijziging->setPartner2($wedstrijd_gegevens['partner2']);
            $wedstrijd_wijziging->setWijzigingStatus("nieuw");
            $wedstrijd_wijziging->setIndienerVeranderd(false);
            $wedstrijd_wijziging->setActie($actie);
            $wedstrijd_wijziging->setWedstrijdId($wedstrijd_id);
            $wedstrijd_wijziging->setCatType($wedstrijd_gegevens['cat_type']);
            $wedstrijd_wijziging->setTijdslotOud($wedstrijd_gegevens['tijdslot']);
            $this->entityManager->persist($wedstrijd_wijziging);
            $this->entityManager->flush();
        }
        switch ($actie) {
            case "verplaatsen":
                // vraag de gebruiker om de verhinderingen aan te passen
                return $this->render(
                    'wijzigingen/verhinderingen_aanpassen.html.twig',
                    [
                        'wedstrijd_gegevens' => $wedstrijd_gegevens,
                        'tijdslot' => $tijdslot,
                        'toernooi' => $toernooi,
                        'wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId()
                    ]
                );
                break;
            case "afzeggen":
                // vraag de gebruiker om de afzegging te bevestigen
                return $this->render(
                    'wijzigingen/bevestig_afzeggen.html.twig',
                    [
                        'wedstrijd_gegevens' => $wedstrijd_gegevens,
                        'tijdslot' => $tijdslot,
                        'toernooi' => $toernooi,
                        'wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId(),
                        'indiener' => ""
                    ]
                );
                break;
            case "niet verplaatsen":
                $this->addFlash('feedback', 'Geplande wedstrijden niet gewijzigd');
                return $this->redirectToRoute('home');
                break;
            default:
                // TODO: log ongeldige actie in url
        }
    }

    /**
     * @Route("/wizard_wijzig_wedstrijd_verh_ok/{wedstrijd_wijziging_id}", name="wizard_wijzig_wedstrijd_verh_ok")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * Laat de deelnemer wedstrijden wijzigen als het toernooi gestart is en de wedstrijden gepland zijn
     * De verhinderingen zijn aangepast; bepaal de herplan-opties en vraag om bevestiging
     */
    public function wizard_wijzig_wedstrijd_verh_ok(int $wedstrijd_wijziging_id): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        // verhinderingen zijn aangepast;
        // kijk of er een wedstrijd_wijziging is meegegeven:
        if ($wedstrijd_wijziging_id > 0) {
            $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->find($wedstrijd_wijziging_id);
            if ($wedstrijd_wijziging->getWijzigingStatus() == "definitief") {
                $this->addFlash('feedback', 'Deze wedstrijd wijziging is al definitief');
                return $this->redirectToRoute("mijn_wedstrijden");
            }
            $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens(
                $wedstrijd_wijziging->getWedstrijdId()
            );
            //$tijdslot_gegevens = $mysqlDB->getTijdslotGegevens($wedstrijd_gegevens['tijdslot']);
            $tijdslot = $this->tijdslotRepository->find($wedstrijd_gegevens['tijdslot']);

            if ($wedstrijd_wijziging->getIndiener() == $this->getUser()->getId()) {
                // De ingelogde user heeft het initiatief genomen voor een verplaatsing. Bepaal de herplan-optie(s)
                // en vraag bevestiging voor verplaatsen of afzeggen wedstrijd
                $indiener_veranderd = $wedstrijd_wijziging->getIndienerVeranderd();
                $wedstrijd_wijziging->setWijzigingStatus("verhinderingen_ok");
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();
                // bepaal alternatieve tijdsloten
                $herplan_opties = $this->wedstrijdWijzigingService->getHerplanOpties(
                    $wedstrijd_wijziging,
                    $toernooi,
                    true
                );
                if (count($herplan_opties) == 0) {
                    // Geen herplan-opties; vraag bevestiging voor afzeggen wedstrijd
                    return $this->render(
                        'wijzigingen/bevestig_verpl_afzeggen.html.twig',
                        [
                            'wedstrijd_gegevens' => $wedstrijd_gegevens,
                            'tijdslot' => $tijdslot,
                            'toernooi' => $toernooi,
                            'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
                            'indiener_veranderd' => $indiener_veranderd
                        ]
                    );
                } else {
                    if ($herplan_opties[0]->getNaamMetWedstrijd() == null) {
                        // Er is één herplan-optie zonder verhinderingen of dubbele wedstrijden
                        $wedstrijd_wijziging->setHerplanOptieId($herplan_opties[0]->getId());
                        $this->entityManager->persist($wedstrijd_wijziging);
                        $this->entityManager->flush();
                        // Vraag bevestiging voor verplaatsen wedstrijd naar dit tijdslot
                        //$herplan_gegevens = $mysqlDB->getTijdslotGegevens($herplan_opties[0]->getTijdslot());
                        $herplan_gegevens = $this->tijdslotRepository->find($herplan_opties[0]->getTijdslot());
                        return $this->render(
                            'wijzigingen/bevestig_verplaatsen.html.twig',
                            [
                                'wedstrijd_gegevens' => $wedstrijd_gegevens,
                                'tijdslot' => $tijdslot,
                                'toernooi' => $toernooi,
                                'herplan_gegevens' => $herplan_gegevens,
                                'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
                                'indiener_veranderd' => $indiener_veranderd
                            ]
                        );
                    } else {
                        // Er zijn één of meer herplan-opties met dubbele wedstrijden voor iemand
                        // Maak een array van alleen herplan_opties waarbij de indiener dubbele wedstrijden heeft:
                        $herplan_opties_indiener = array_filter($herplan_opties, function ($herplan_optie) {
                            return $herplan_optie->getUserIdMetWedstrijd() == $this->getUser()->getId();
                        });
                        if (count($herplan_opties_indiener) > 0) {
                            // Laat de indiener één herplan_optie kiezen waarbij de indiener een dubbele wedstrijd heeft
                            return $this->render(
                                'wijzigingen/kies_optie_verplaatsen.html.twig',
                                [
                                    'wedstrijd_gegevens' => $wedstrijd_gegevens,
                                    'tijdslot' => $tijdslot,
                                    'toernooi' => $toernooi,
                                    'herplan_opties' => $herplan_opties_indiener,
                                    'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
                                    'indiener_veranderd' => $indiener_veranderd
                                ]
                            );
                        } else {
                            // Iemand anders moet een dubbele wedstrijd spelen. Kies de eerste herplan_optie en vraag de indiener en
                            // de andere spelers akkoord. De speler die een dubbele wedstrijd moet speler, kan evt. een ander tijdstip
                            // vragen, waarop hij/zij waarschijnlijk alle zijn/haar opties voor dubbele wedstrijden krijgt.
                            $wedstrijd_wijziging->setHerplanOptieId($herplan_opties[0]->getId());
                            $wedstrijd_wijziging->setTijdslotNieuw($herplan_opties[0]->getTijdslot());
                            $this->entityManager->persist($wedstrijd_wijziging);
                            $this->entityManager->flush();
                            // Vraag bevestiging voor verplaatsen wedstrijd naar dit tijdslot
                            //$herplan_gegevens = $mysqlDB->getTijdslotGegevens($herplan_opties[0]->getTijdslot());
                            $herplan_gegevens = $this->tijdslotRepository->find($herplan_opties[0]->getTijdslot());
                            return $this->render(
                                'wijzigingen/bevestig_verplaatsen.html.twig',
                                [
                                    'wedstrijd_gegevens' => $wedstrijd_gegevens,
                                    'tijdslot' => $tijdslot,
                                    'toernooi' => $toernooi,
                                    'herplan_gegevens' => $herplan_gegevens,
                                    'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
                                    'naam_met_wedstrijd' => $herplan_opties[0]->getNaamMetWedstrijd(),
                                    'indiener_veranderd' => $indiener_veranderd
                                ]
                            );
                        }
                    }
                }
            }
        }
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/wizard_wijzig_wedstrijd_reageer/{wedstrijd_wijziging_id}", name="wizard_wijzig_wedstrijd_reageer")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * Laat de deelnemer wedstrijden wijzigen als het toernooi gestart is en de wedstrijden gepland zijn
     * De ingelogde user heeft een wedstrijdwijziging bericht gekregen en wil reageren
     */
    public function wizard_wijzig_wedstrijd_reageer(int $wedstrijd_wijziging_id): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        $user_id = $this->getUser()->getId();
        $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->find($wedstrijd_wijziging_id);
        if (!$wedstrijd_wijziging) {
            $this->addFlash("feedback", "error");
            return $this->redirectToRoute("home");
        }
        if (!$wedstrijd_wijziging->hasUser($user_id)) {
            $this->addFlash("feedback", "error");
            return $this->redirectToRoute("home");
        }
        $herplan_optie = $this->herplanOptieRepository->find($wedstrijd_wijziging->getHerplanOptieId());
        if (($wedstrijd_wijziging->getSpeler1() == $user_id && $herplan_optie->getSpeler1Akkoord() == 1) ||
            ($wedstrijd_wijziging->getPartner1() == $user_id && $herplan_optie->getPartner1Akkoord() == 1) ||
            ($wedstrijd_wijziging->getSpeler2() == $user_id && $herplan_optie->getSpeler2Akkoord() == 1) ||
            ($wedstrijd_wijziging->getPartner2() == $user_id && $herplan_optie->getPartner2Akkoord() == 1)) {
            $this->addFlash("feedback", "U heeft al gereageerd op deze wedstrijd wijziging");
            return $this->redirectToRoute("mijn_wedstrijden");
        }
        $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens(
            $wedstrijd_wijziging->getWedstrijdId()
        );
        //$tijdslot_gegevens = $mysqlDB->getTijdslotGegevens($wedstrijd_wijziging->getTijdslotOud());
        $tijdslot = $this->tijdslotRepository->find($wedstrijd_wijziging->getTijdslotOud());

        $deelnemer = $this->deelnemerRepository->find($wedstrijd_wijziging->getIndiener());
        switch ($wedstrijd_wijziging->getActie()) {
            case "verplaatsen":
                //$herplan_gegevens = $mysqlDB->getTijdslotGegevens($herplan_optie->getTijdslot());
                $herplan_gegevens = $this->tijdslotRepository->find($herplan_optie->getTijdslot());
                // Vraag bevestiging voor verplaatsen wedstrijd naar dit tijdslot:
                return $this->render(
                    'wijzigingen/bevestig_verplaatsen_ontv.html.twig',
                    [
                        'wedstrijd_gegevens' => $wedstrijd_gegevens,
                        'tijdslot' => $tijdslot,
                        'toernooi' => $toernooi,
                        'herplan_gegevens' => $herplan_gegevens,
                        'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
                        'indiener' => $deelnemer->getNaam(),
                        'user_id_met_wedstrijd' => $herplan_optie->getUserIdMetWedstrijd()
                    ]
                );
                break;
            case "afzeggen":
                // Vraag bevestiging voor afzeggen wedstrijd:
                return $this->render(
                    'wijzigingen/bevestig_afzeggen_ontv.html.twig',
                    [
                        'wedstrijd_gegevens' => $wedstrijd_gegevens,
                        'tijdslot' => $tijdslot,
                        'toernooi' => $toernooi,
                        'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
                        'indiener' => $deelnemer->getNaam()
                    ]
                );
                break;
            case "toevoegen":
                //$herplan_gegevens = $mysqlDB->getTijdslotGegevens($herplan_optie->getTijdslot());
                $herplan_gegevens = $this->tijdslotRepository->find($herplan_optie->getTijdslot());
                // Vraag bevestiging voor de aangeboden extra wedstrijd:
                return $this->render(
                    'wijzigingen/bevestig_toevoegen_ontv.html.twig',
                    [
                        'wedstrijd_gegevens' => $wedstrijd_gegevens,
                        'tijdslot' => $tijdslot,
                        'toernooi' => $toernooi,
                        'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id
                    ]
                );
                break;
            default:
        }
    }

    /**
     * @Route("wizard_wijzig_wedstrijd_3/{wedstrijd_wijziging_id}/{actie}", name="wizard_wijzig_wedstrijd_3")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * De deelnemer heeft akkoord gegeven op een herplan-optie, of wil een andere herplan-optie,
     * of wil toch niet verplaatsen.
     * Bij verplaatsen, maak een bericht aan voor de andere deelnemers en de toernooileiding en
     * laat de deelnemer een toelichting geven waarom een wedstrijd verplaatst of afgezegd moet worden
     */
    public function wizard_wijzig_wedstrijd_3(int $wedstrijd_wijziging_id, string $actie)
    {
        $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->find($wedstrijd_wijziging_id);
        switch ($actie) {
            case "verplaatsen":
                // Er is hier akkoord gegeven op één specifieke herplan_optie; haal deze op:
                $herplan_optie = $this->herplanOptieRepository->find($wedstrijd_wijziging->getHerplanOptieId());

                // Leg het akkoord van de huidige user op deze optie vast:
                $iedereen_akkoord = $this->wedstrijdWijzigingService->setAkkoord(
                    $wedstrijd_wijziging,
                    $herplan_optie,
                    $this->getUser()->getId(),
                    true
                );

                // Maak een bericht voor de andere spelers en de toernooileiding aan:
                $titel = "Wedstrijd moet verplaatst worden";
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-verplaatsen",
                    $this->getUser()->getId()
                );

                return $this->redirectToRoute(
                    'wizard_wijzig_wedstrijd_4',
                    ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id, 'bericht_id' => $bericht->getId()]
                );
                break;
            case "ander tijdstip":
                // Verwijder de herplan-optie(s):
                $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);

                // Om een ander tijdstip te krijgen, moet eerst een verhindering toegevoegd worden:
                return $this->redirectToRoute(
                    'wijzig_verhinderingen',
                    ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id]
                );
                break;
            case "afzeggen":
                // Maak een herplan_optie zonder tijdslot aan om de ontvangstbevestigingen te kunnen registreren
                $herplan_optie = new HerplanOptie();
                $herplan_optie->setWedstrijdWijzigingId($wedstrijd_wijziging_id);
                $herplan_optie->setToernooiId($this->requestStack->getSession()->get('huidig_toernooi_id'));
                $this->entityManager->persist($herplan_optie);
                $this->entityManager->flush(); // Om MySQL een id voor $herplan_optie te laten genereren
                $wedstrijd_wijziging->setHerplanOptieId($herplan_optie->getId());
                $wedstrijd_wijziging->setActie("afzeggen");
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();
                $this->logger->info("www_3, afzeggen: " . print_r($wedstrijd_wijziging, true));

                // Leg het akkoord van de huidige user op deze optie vast:
                $iedereen_akkoord = $this->wedstrijdWijzigingService->setAkkoord(
                    $wedstrijd_wijziging,
                    $herplan_optie,
                    $this->getUser()->getId(),
                    true
                );

                // Maak een bericht voor de andere spelers en de toernooileiding aan:
                $titel = "Wedstrijd moet afgezegd worden";
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-afzeggen",
                    $this->getUser()->getId()
                );
                return $this->redirectToRoute(
                    'wizard_wijzig_wedstrijd_4',
                    ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id, 'bericht_id' => $bericht->getId()]
                );
                break;
            case "niet verplaatsen":
                // Verwijder de herplan-optie(s):
                $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);
            // geen break: verdere acties zelfde als voor "niet afzeggen":
            case "niet afzeggen":
                // Verwijder wedstrijd_wijziging
                $this->entityManager->remove($wedstrijd_wijziging);
                $this->entityManager->flush();
                $this->addFlash('feedback', 'Geplande wedstrijden niet gewijzigd');
                return $this->redirectToRoute('mijn_wedstrijden');
                break;
            default:
                // TODO: log ongeldige actie in url
        }
    }

    /**
     * @Route("wizard_wijzig_wedstrijd_3_ontvanger/{wedstrijd_wijziging_id}/{actie}", name="wizard_wijzig_wedstrijd_3_ontvanger")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * De ontvanger van een wedstrijd_wijziging heeft akkoord gegeven op een herplan-optie, of wil afzeggen,
     * of wil een andere herplan-optie.
     * Maak een bericht aan voor de andere deelnemers en de toernooileiding en bij afzeggen,
     * laat de deelnemer een toelichting geven waarom een wedstrijd verplaatst of afgezegd moet worden
     */
    public function wizard_wijzig_wedstrijd_3_ontvanger(int $wedstrijd_wijziging_id, string $actie): Response
    {
        $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->find($wedstrijd_wijziging_id);
        // Er is hier een reactie gegeven op één specifieke herplan_optie; haal deze op:
        $herplan_optie = $this->herplanOptieRepository->find($wedstrijd_wijziging->getHerplanOptieId());
        switch ($actie) {
            case "akkoord":
                // Leg het akkoord van de huidige user op deze optie vast:
                $iedereen_akkoord = $this->wedstrijdWijzigingService->setAkkoord(
                    $wedstrijd_wijziging,
                    $herplan_optie,
                    $this->getUser()->getId(),
                    true
                );

                // Maak een bericht voor de andere spelers en de toernooileiding aan:
                if ($iedereen_akkoord) {
                    $titel = "Alle spelers akkoord met verplaatsen wedstrijd";
                    $this->AddFlash("feedback", "Wedstrijd definitief verplaatst");
                } else {
                    $titel = "Speler akkoord met verplaatsen wedstrijd";
                }
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-ontv-akkoord",
                    $this->getUser()->getId()
                );
                // zet de verzendtijd en sla het nieuwe bericht op
                $bericht->setVerzendTijdNow();
                $this->entityManager->persist($bericht);
                $this->entityManager->flush();
                // verstuur de mails naar alle deelnemers met verplaatsing of wijziging
                $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);

                return $this->redirectToRoute('mijn_wedstrijden');

                break;
            case "ander tijdstip":
                // De ontvanger gaat een ander tijdstip zoeken en wordt daarom de indiener van de veranderde wedstrijd_wijziging
                $wedstrijd_wijziging->setIndiener($this->getuser()->getId());
                $wedstrijd_wijziging->setIndienerVeranderd(true);
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();

                $titel = "Speler zoekt ander tijdstip voor verplaatsen wedstrijd";
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-ontv-ander-tijdstip",
                    $this->getUser()->getId()
                );
                // zet de verzendtijd en sla het nieuwe bericht op
                $bericht->setVerzendTijdNow();
                $this->entityManager->persist($bericht);
                $this->entityManager->flush();
                // verstuur de mails naar alle deelnemers met verplaatsing of wijziging
                $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);

                // Verwijder de herplan-optie(s):
                $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);

                // vraag de gebruiker om eerst de verhinderingen aan te passen
                $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens(
                    $wedstrijd_wijziging->getWedstrijdId()
                );
                //$tijdslot_gegevens = $mysqlDB->getTijdslotGegevens($wedstrijd_gegevens['tijdslot']);
                $tijdslot = $this->tijdslotRepository->find($wedstrijd_gegevens['tijdslot']);


                $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
                $toernooi = $this->toernooiRepository->find($toernooi_id);
                return $this->render(
                    'wijzigingen/verhinderingen_aanpassen.html.twig',
                    [
                        'wedstrijd_gegevens' => $wedstrijd_gegevens,
                        'tijdslot' => $tijdslot,
                        'toernooi' => $toernooi,
                        'wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId()
                    ]
                );
                break;
            case "afzeggen":
                // De wedstrijd_wijziging wordt veranderd in "afzeggen" en de ontvanger wordt de indiener
                // van de veranderde wedstrijd_wijziging:
                $wedstrijd_wijziging->setActie("afzeggen");
                $wedstrijd_wijziging->setIndiener($this->getuser()->getId());
                $wedstrijd_wijziging->setIndienerVeranderd(true);

                // Maak een bericht voor de andere spelers en de toernooileiding aan:
                $titel = "Verplaatsen niet akkoord; wedstrijd moet afgezegd worden";
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-ontv-afzeggen",
                    $this->getUser()->getId()
                );

                // Verwijder de echte herplan-optie(s):
                $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);

                // Maak een herplan_optie zonder tijdslot aan om de ontvangstbevestigingen te kunnen registreren
                $herplan_optie = new HerplanOptie();
                $herplan_optie->setWedstrijdWijzigingId($wedstrijd_wijziging_id);
                $herplan_optie->setToernooiId($this->requestStack->getSession()->get('huidig_toernooi_id'));
                // Leg het akkoord van de huidige user op deze optie vast:
                $iedereen_akkoord = $this->wedstrijdWijzigingService->setAkkoord(
                    $wedstrijd_wijziging,
                    $herplan_optie,
                    $this->getUser()->getId(),
                    true
                );
                $this->entityManager->persist($herplan_optie);
                $this->entityManager->flush(); // Om MySQL een id voor $herplan_optie te laten genereren
                $wedstrijd_wijziging->setHerplanOptieId($herplan_optie->getId());
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();

                return $this->redirectToRoute(
                    'wizard_wijzig_wedstrijd_4',
                    ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id, 'bericht_id' => $bericht->getId()]
                );
                break;
            case "bevestig":
                // Leg het akkoord van de huidige user op deze optie vast:
                $iedereen_akkoord = $this->wedstrijdWijzigingService->setAkkoord(
                    $wedstrijd_wijziging,
                    $herplan_optie,
                    $this->getUser()->getId(),
                    true
                );

                // Maak een bericht voor de andere spelers en de toernooileiding aan:
                if ($iedereen_akkoord) {
                    $titel = "Alle spelers hebben afzeggen wedstrijd bevestigd";
                    $this->AddFlash("feedback", "Wedstrijd definitief afgezegd");
                } else {
                    $titel = "Speler heeft afzeggen wedstrijd bevestigd";
                }
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-ontv-bevestig-afzeggen",
                    $this->getUser()->getId()
                );
                // zet de verzendtijd en sla het nieuwe bericht op
                $bericht->setVerzendTijdNow();
                $this->entityManager->persist($bericht);
                $this->entityManager->flush();
                // verstuur de mails naar alle deelnemers met verplaatsing of wijziging
                $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);

                return $this->redirectToRoute('mijn_wedstrijden');
                break;
            case "toevoegen_akkoord":
                // Leg het akkoord van de huidige user op deze optie vast:
                $iedereen_akkoord = $this->wedstrijdWijzigingService->setAkkoord(
                    $wedstrijd_wijziging,
                    $herplan_optie,
                    $this->getUser()->getId(),
                    true
                );

                // Maak een bericht voor de andere spelers en de toernooileiding aan:
                if ($iedereen_akkoord) {
                    $titel = "Alle spelers akkoord met extra wedstrijd";
                    $this->AddFlash("feedback", "Extra wedstrijd definitief ingepland");
                } else {
                    $titel = "Speler akkoord met extra wedstrijd";
                }
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-ontv-toevoegen-akkoord",
                    $this->getUser()->getId()
                );
                // zet de verzendtijd en sla het nieuwe bericht op
                $bericht->setVerzendTijdNow();
                $this->entityManager->persist($bericht);
                $this->entityManager->flush();
                // verstuur de mails naar alle deelnemers met verplaatsing of wijziging
                $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);

                return $this->redirectToRoute('mijn_wedstrijden');
                break;
            case "toevoegen ander tijdstip":
                // De ontvanger gaat een ander tijdstip zoeken en wordt daarom de indiener van de veranderde wedstrijd_wijziging
                $wedstrijd_wijziging->setIndiener($this->getuser()->getId());
                $wedstrijd_wijziging->setIndienerVeranderd(true);
                $wedstrijd_wijziging->setWijzigingStatus("nieuw");
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();

                $titel = "Speler zoekt ander tijdstip voor extra wedstrijd";
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-ontv-toevoegen-ander-tijdstip",
                    $this->getUser()->getId()
                );
                // zet de verzendtijd en sla het nieuwe bericht op
                $bericht->setVerzendTijdNow();
                $this->entityManager->persist($bericht);
                $this->entityManager->flush();
                // verstuur de mails naar alle deelnemers met verplaatsing of wijziging
                $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);

                // Verwijder de herplan-optie(s):
                $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);

                // vraag de gebruiker om eerst de verhinderingen aan te passen
                $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens(
                    $wedstrijd_wijziging->getWedstrijdId()
                );
                //$tijdslot_gegevens = $mysqlDB->getTijdslotGegevens($wedstrijd_gegevens['tijdslot']);
                $tijdslot = $this->tijdslotRepository->find($wedstrijd_gegevens['tijdslot']);
                $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
                $toernooi = $toernooiRepository->find($toernooi_id);
                return $this->render(
                    'wijzigingen/verhinderingen_aanpassen.html.twig',
                    [
                        'wedstrijd_gegevens' => $wedstrijd_gegevens,
                        'tijdslot' => $tijdslot,
                        'toernooi' => $toernooi,
                        'wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId()
                    ]
                );
                break;
            case "toevoegen niet akkoord":
                // De wedstrijd_wijziging wordt veranderd in "afzeggen" en de ontvanger wordt de indiener
                // van de veranderde wedstrijd_wijziging:
                $wedstrijd_wijziging->setActie("afzeggen");
                $wedstrijd_wijziging->setIndiener($this->getuser()->getId());
                $wedstrijd_wijziging->setIndienerVeranderd(true);

                // Maak een bericht voor de andere spelers en de toernooileiding aan:
                $titel = "Extra wedstrijd niet akkoord; wedstrijd wordt verwijderd";
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    $titel,
                    "ww-ontv-toevoegen-niet-akkoord",
                    $this->getUser()->getId()
                );

                // Verwijder de echte herplan-optie(s):
                $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);

                // Maak een herplan_optie zonder tijdslot aan om de ontvangstbevestigingen te kunnen registreren
                $herplan_optie = new HerplanOptie();
                $herplan_optie->setWedstrijdWijzigingId($wedstrijd_wijziging_id);
                $herplan_optie->setToernooiId($this->requestStack->getSession()->get('huidig_toernooi_id'));
                // Leg het akkoord van de huidige user op deze optie vast:
                $iedereen_akkoord = $this->wedstrijdWijzigingService->setAkkoord(
                    $wedstrijd_wijziging,
                    $herplan_optie,
                    $this->getUser()->getId(),
                    true
                );
                $this->entityManager->persist($herplan_optie);
                $this->entityManager->flush(); // Om MySQL een id voor $herplan_optie te laten genereren
                $wedstrijd_wijziging->setHerplanOptieId($herplan_optie->getId());
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();

                return $this->redirectToRoute(
                    'wizard_wijzig_wedstrijd_4',
                    ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id, 'bericht_id' => $bericht->getId()]
                );
                break;
            default:
                // TODO: log ongeldige actie in url
        }
    }

    /**
     * @Route("wizard_wijzig_wedstrijd_3a/{wedstrijd_wijziging_id}/{herplan_optie_id}", name="wizard_wijzig_wedstrijd_3a")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * De deelnemer heeft een herplan-optie met dubbele wedstrijd voor verplaatsen geselecteerd.
     * Dit wordt verder afgehandeld als een herplan-optie zonder dubbele wedstrijd door
     * wizard_wijzig_wedstrijd_3 aan te roepen.
     */
    public function wizard_wijzig_wedstrijd_3a(int $wedstrijd_wijziging_id, int $herplan_optie_id)
    {
        $herplan_optie = $this->herplanOptieRepository->find($herplan_optie_id);
        $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->find($wedstrijd_wijziging_id);
        $wedstrijd_wijziging->setHerplanOptieId($herplan_optie_id);
        $wedstrijd_wijziging->setTijdslotNieuw($herplan_optie->getTijdslot());
        $herplan_optie->setWedstrijdWijzigingId($wedstrijd_wijziging_id);
        $this->entityManager->flush();
        $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, $herplan_optie_id);
        return $this->wizard_wijzig_wedstrijd_3(
            $wedstrijd_wijziging_id,
            "verplaatsen"
        );
    }

    /**
     * @Route("/wizard_wijzig_wedstrijd_4/{wedstrijd_wijziging_id}/{bericht_id}", name="wizard_wijzig_wedstrijd_4")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * Presenteer het formulier om een toelichting voor de gevraagde wedstrijd wijziging te geven
     * en verzorg de afhandeling van het formulier, met keuzes "Verstuur" of "Niet wijzigen"
     */
    public function wizard_wijzig_wedstrijd_4(Request $request, int $wedstrijd_wijziging_id, int $bericht_id): Response
    {
        $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->find($wedstrijd_wijziging_id);
        $wedstrijd_id = $wedstrijd_wijziging->getWedstrijdId();
        $bericht = $this->berichtRepository->find($bericht_id);
        $form = $this->createForm(ToelichtingType::class, $bericht);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $bericht = $form->getData();

            if ($request->request->get('niet_wijzigen')) {
                // De "Niet wijzigen" knop in het formulier is geklikt
                // TODO: Verwijder eerst de herplan-opties voor deze wedstrijd_wijziging
                // verwijder wedstrijd_wijziging
                $this->entityManager->remove($wedstrijd_wijziging);
                $this->entityManager->flush();
                $this->addFlash('feedback', 'Geplande wedstrijden niet gewijzigd');
                return $this->redirectToRoute('mijn_wedstrijden');
            }

            // zet de verzendtijd en sla het nieuwe bericht op
            $bericht->setVerzendTijdNow();
            $this->entityManager->persist($bericht);
            $this->entityManager->flush();
            // verstuur de mails naar alle deelnemers  met verplaatsing of wijziging
            $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);

            $wedstrijd_wijziging->setWijzigingStatus("verstuurd");
            $this->entityManager->persist($wedstrijd_wijziging);

            // geef het oude tijdslot vrij
            //$mysqlDB->geefTijdslotVrij($wedstrijd_wijziging->getTijdslotOud());
            $tijdslotOud = $this->tijdslotRepository->find($wedstrijd_wijziging->getTijdslotOud());
            $tijdslotOud->setVrij(true);
            $this->entityManager->persist($tijdslotOud);
            if ($wedstrijd_wijziging->getActie() == "verplaatsen") {
                // bij verplaatsing, plan het nieuwe tijdslot in
                //$mysqlDB->verplaatsDefinitief($wedstrijd_id, $wedstrijd_wijziging->getTijdslotNieuw());
                $combination = $this->combinationsRepository->find($wedstrijd_id);
                $combination->setTijdslot($wedstrijd_wijziging->getTijdslotNieuw());
                $tijdslotNieuw = $this->tijdslotRepository->find($wedstrijd_wijziging->getTijdslotNieuw());
                $tijdslotNieuw->setVrij(false);
                $this->entityManager->persist($tijdslotNieuw);
            } else {
                // bij afzeggen wordt het tijdslot voor de wedstrijd nog aangehouden totdat iedereen bevestigd heeft
            }
            $this->entityManager->flush();
            if ($wedstrijd_wijziging->getIndiener() == $this->getUser()->getId()) {
                return $this->redirectToRoute('mijn_wedstrijden');
            } else {
                return $this->redirectToRoute('toon_wedstrijd_wijzigingen');
            }
        }
        return $this->render('wijzigingen/toelichting.html.twig', [
            'form' => $form->createView(),
            'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
            'bericht_id' => $bericht_id
        ]);
    }

    /**
     * @Route("/terugtrekken", name="terugtrekken")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     *
     * Laat de deelnemer zich terugtrekken als het toernooi gestart is en de wedstrijden gepland zijn
     */
    public function terugtrekken(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooi_id) {
            // Dit kan alleen als de gebruiker de url in de browser heeft ingevoerd
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $user = $this->getUser();
        // TODO: implementeer de terugtrekken functie!
        return new Response();
    }

}