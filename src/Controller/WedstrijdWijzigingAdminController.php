<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\WedstrijdWijziging;
use App\Repository\CombinationsRepository;
use App\Repository\HerplanOptieRepository;
use App\Repository\InschrijvingRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\TijdslotRepository;
use App\Repository\ToernooiRepository;
use App\Repository\VerhinderingRepository;
use App\Repository\WedstrijdWijzigingRepository;
use App\Service\WedstrijdWijzigingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class WedstrijdWijzigingAdminController extends AbstractController
{
    public function __construct(
        private readonly CombinationsRepository $combinationsRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HerplanOptieRepository $herplanOptieRepository,
        private readonly InschrijvingRepository $inschrijvingRepository,
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
     * @Route("/toon_wedstrijd_wijzigingen", name="toon_wedstrijd_wijzigingen")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function toonWedstrijdWijzigingen(): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $wedstrijdWijzigingen = $this->wedstrijdWijzigingRepository->getWedstrijdWijzigingen($toernooi_id);

        // toon het overzicht van reserveringen
        return $this->render('wijzigingen/wedstrijd_wijzigingen.html.twig', [
            'wedstrijd_wijzigingen' => $wedstrijdWijzigingen,
        ]);
    }

    /**
     * @Route("/afzeggen_wedstrijd/{wedstrijd_id}", name="afzeggen_wedstrijd")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function afzeggenWedstrijd(int $wedstrijdId): Response
    {
        $wedstrijdGegevens = $this->combinationsRepository->getWedstrijdGegevens($wedstrijdId);
        // vraag op welke speler wil afzeggen:
        return $this->render('wijzigingen/vraag_indiener.html.twig', [
            'wedstrijd_gegevens' => $wedstrijdGegevens,
        ]);
    }

    /**
     * @Route("/verplaats_wedstrijd/{wedstrijd_id}", name="verplaats_wedstrijd")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function verplaatsWedstrijd(int $wedstrijd_id): Response
    {
        // haal de vrije tijdsloten op (tijdsloten voor verschillende banen gegroepeerd per starttijd):
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $wedstrijdGegevens = $this->combinationsRepository->getWedstrijdGegevens($wedstrijd_id);
        $speeltijden = $this->speeltijdenRepository->getSpeeltijden($toernooiId);
        $vrijeTijdsloten = $this->tijdslotRepository->getVrijeTijdsloten($toernooiId);
        // haal de bondsnummers voor de deelnemers aan deze wedstrijd op als string:
        $bondsnummers = $this->combinationsRepository->getBondsnummers($wedstrijd_id);
        // haal de andere geplande wedstrijden voor deze bondsnummers op:
        $geplandeWedstrijden = $this->combinationsRepository->getWedstrijden($toernooiId, $bondsnummers);
        // haal de verhinderingen voor deze bondsnummers op op:
        $verhinderingen = $this->verhinderingRepository->getAlleVerhinderingen($toernooiId, $bondsnummers);
        // presenteer vrije tijdsloten en per deelnemer de geplande wedstrijden en verhinderingen
        return $this->render('toernooi/verplaats_wedstrijd.html.twig', [
            'wedstrijd_gegevens_json' => json_encode($wedstrijdGegevens),
            'wedstrijd_gegevens' => $wedstrijdGegevens,
            'speeltijden' => json_encode($speeltijden),
            'vrije_tijdsloten' => json_encode($vrijeTijdsloten),
            'geplande_wedstrijden' => json_encode($geplandeWedstrijden),
            'verhinderingen' => json_encode($verhinderingen),
        ]);
    }

    /**
     * @Route("toggleAkkoord/{herplan_optie_id}/{speler}/{akkoord}", name="toggleAkkoord")
     * speler is de user_id van de speler,
     * akkoord moet zijn true of false.
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function toggleAkkoord(int $herplanOptieId, int $speler, bool $akkoord): Response
    {
        $this->logger->info(
            "toggleAkkoord herplan_optie_id: " . $herplanOptieId . " speler: " . $speler . " akkoord: " . $akkoord
        );
        $herplanOptie = $this->herplanOptieRepository->find($herplanOptieId);
        $this->logger->info("toggleAkkoord: herplan_optie: " . print_r($herplanOptie, true));
        $wedstrijdWijziging = $this->wedstrijdWijzigingRepository->find($herplanOptie->GetWedstrijdWijzigingId());
        $this->logger->info("toggleAkkoord: wedstrijd_wijziging: " . print_r($wedstrijdWijziging, true));
        $iedereenAkkoord = $this->wedstrijdWijzigingService->setAkkoord(
            $wedstrijdWijziging,
            $herplanOptie,
            $speler,
            $akkoord
        );
        return new Response();
    }

    /**
     * @Route("/maak_afzegging/{wedstrijd_id}/{indiener}", name="maak_afzegging")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function maakAfzegging(int $wedstrijd_id, int $indiener): Response
    {
        $params = $this->wedstrijdWijzigingService->maak_wedstrijd_wijziging(
            $wedstrijd_id,
            null,
            $indiener,
            "afzeggen"
        );
        return $this->redirectToRoute('wizard_wijzig_wedstrijd_4', $params);
    }

    /**
     * @Route("/maak_verplaatsing/{wedstrijd_id}/{nieuwe_tijdslot}/{indiener}", name="maak_verplaatsing")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function maakVerplaatsing(int $wedstrijd_id, int $nieuwe_tijdslot, int $indiener): Response
    {
        $params = $this->wedstrijdWijzigingService->maak_wedstrijd_wijziging(
            $wedstrijd_id,
            $nieuwe_tijdslot,
            $indiener,
            "verplaatsen"
        );
        return $this->redirectToRoute('wizard_wijzig_wedstrijd_4', $params);
    }

    /**
     * @Route("/maak_extra_wedstrijden", name="maak_extra_wedstrijden")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function maak_extra_wedstrijden(): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        $wedstrijden = $this->inschrijvingRepository->getExtraWedstrijden($toernooi_id);
        $index = 0;
        while (array_key_exists($index, $wedstrijden)) {
            // Kijk of er een tijdslot te vinden is voor $wedstrijden[$index]
            // voeg eerst de combinatie toe aan combinaties
            $combinations_id = $this->combinationsRepository->AddCombination(
                $toernooi_id,
                $wedstrijden[$index]['categorie'],
                $wedstrijden[$index]['inschrijving1'],
                $wedstrijden[$index]['inschrijving2']
            );
            if ($combinations_id > 0) {
                // maak hier een wedstrijd wijziging mee aan
                $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens($combinations_id);
                $wedstrijd_wijziging = new WedstrijdWijziging();
                $wedstrijd_wijziging->setToernooiId($toernooi_id);
                $wedstrijd_wijziging->setIndiener(0);
                $wedstrijd_wijziging->setSpeler1($wedstrijd_gegevens['speler1']);
                $wedstrijd_wijziging->setPartner1($wedstrijd_gegevens['partner1']);
                $wedstrijd_wijziging->setSpeler2($wedstrijd_gegevens['speler2']);
                $wedstrijd_wijziging->setPartner2($wedstrijd_gegevens['partner2']);
                $wedstrijd_wijziging->setWijzigingStatus("nieuw");
                $wedstrijd_wijziging->setActie("toevoegen");
                $wedstrijd_wijziging->setIndienerVeranderd(false);
                $wedstrijd_wijziging->setWedstrijdId($combinations_id);
                $wedstrijd_wijziging->setCatType($wedstrijden[$index]['cat_type']);
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();

                $herplanOpties = $this->wedstrijdWijzigingService->getHerplanOpties(
                    $wedstrijd_wijziging,
                    $toernooi,
                    false
                );
                if (count($herplanOpties) > 0 && $herplanOpties[0]->getNaamMetWedstrijd() == null) {
                    // Er is een tijdslot zonder verhindering of dubbele wedstrijd
                    // Koppel de herplan_optie aan de wedstrijd_wijziging
                    $wedstrijd_wijziging->setHerplanOptieId($herplanOpties[0]->getId());
                    // Zet het tijdslot in de wedstrijd_wijziging (zowel voor oud als nieuw)
                    $wedstrijd_wijziging->setTijdslotOud($herplanOpties[0]->getTijdslot());
                    $wedstrijd_wijziging->setTijdslotNieuw($herplanOpties[0]->getTijdslot());
                    // Zet het tijdslot in combinations voor de nieuwe wedstrijd
                    $combinations = $this->combinationsRepository->find($combinations_id);
                    $combinations->setTijdslot($herplanOpties[0]->getTijdslot());
                    //$tijdslot_gegevens = $this->mysqlDB->getTijdslotGegevens($herplanOpties[0]->getTijdslot());
                    $tijdslot = $this->tijdslotRepository->find($herplanOpties[0]->getTijdslot());
                    $this->entityManager->persist($wedstrijd_wijziging);
                    $this->entityManager->persist($combinations);
                    $this->entityManager->flush();
                    // Toon de extra wedstrijd en vraag de toernooi administrator of hij deze wil toevoegen:
                    return $this->render(
                        "wijzigingen/extra_wedstrijd.html.twig",
                        [
                            'toernooi' => $toernooi,
                            'wedstrijd_gegevens' => $wedstrijd_gegevens,
                            'tijdslot' => $tijdslot,
                            'wedstrijd_wijziging_id' => $wedstrijd_wijziging->getId()
                        ]
                    );
                } else {
                    // verwijder herplan_opties en wedstrijd_wijziging
                    $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);
                    $this->entityManager->remove($wedstrijd_wijziging);
                    $this->entityManager->flush();
                    // probeer een herplan optie voor de volgende wedstrijd te vinden
                }
            }
            $index++;
        }
        // presenteer wedstrijd wijzigingen:
        return $this->redirectToRoute("toon_wedstrijd_wijzigingen");
    }

    /**
     * @Route("/verzend_extra_wedstrijd/{wedstrijd_wijziging_id}/{actie}", name="verzend_extra_wedstrijd")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function verzend_extra_wedstrijd(int $wedstrijd_wijziging_id, string $actie): Response
    {
        $wedstrijd_wijziging = $this->wedstrijdWijzigingRepository->find($wedstrijd_wijziging_id);
        switch ($actie) {
            case "verzend":
                // Maak een bericht aan voor de deelnemers en toernooileiding en verzend het:
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    "Extra wedstrijd aangeboden",
                    "toevoegen",
                    0
                );
                // zet de verzendtijd en sla het nieuwe bericht op
                $bericht->setVerzendTijdNow();
                $this->entityManager->persist($bericht);
                // verstuur de mails naar alle deelnemers met wijziging
                $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);
                $wedstrijd_wijziging->setWijzigingStatus("verstuurd");
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();
                return $this->maak_extra_wedstrijden();
            case "overslaan":
                $herplan_optie = $this->herplanOptieRepository->find($wedstrijd_wijziging->getHerplanOptieId());
                $this->entityManager->remove($herplan_optie);
                $this->entityManager->remove($wedstrijd_wijziging);
                $this->entityManager->flush();
                return $this->maak_extra_wedstrijden();
            case "stop":
                $herplan_optie = $this->herplanOptieRepository->find($wedstrijd_wijziging->getHerplanOptieId());
                $this->entityManager->remove($herplan_optie);
                $this->entityManager->remove($wedstrijd_wijziging);
                $this->entityManager->flush();
                return $this->redirectToRoute("toon_wedstrijd_wijzigingen");
            case "automatisch":
                // Maak een bericht aan voor de deelnemers en toernooileiding en verzend het:
                $bericht = $this->wedstrijdWijzigingService->maakBericht(
                    $wedstrijd_wijziging,
                    "Extra wedstrijd aangeboden",
                    "toevoegen",
                    0
                );
                // zet de verzendtijd en sla het nieuwe bericht op
                $bericht->setVerzendTijdNow();
                $this->entityManager->persist($bericht);
                // verstuur de mails naar alle deelnemers met wijziging
                $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);
                $wedstrijd_wijziging->setWijzigingStatus("verstuurd");
                $this->entityManager->persist($wedstrijd_wijziging);
                $this->entityManager->flush();
                return $this->redirectToRoute("extra_wedstrijden_automatisch");
            default:
                // mag niet voorkomen    
                return $this->redirectToRoute("toon_wedstrijd_wijzigingen");
        }
    }

    /**
     * @Route("/extra_wedstrijden_automatisch", name="extra_wedstrijden_automatisch")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function extra_wedstrijden_automatisch(): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooi_id);
        $wedstrijden = $this->inschrijvingRepository->getExtraWedstrijden($toernooi_id);
        $index = 0;
        while (array_key_exists($index, $wedstrijden)) {
            // Kijk of er een tijdslot te vinden is voor $wedstrijden[$index]
            // voeg eerst de combinatie toe aan combinaties
            $combinations_id = $this->combinationsRepository->AddCombination(
                $toernooi_id,
                $wedstrijden[$index]['categorie'],
                $wedstrijden[$index]['inschrijving1'],
                $wedstrijden[$index]['inschrijving2']
            );
            if ($combinations_id > 0) {
                // maak hier een wedstrijd wijziging mee aan
                $wedstrijd_gegevens = $this->combinationsRepository->getWedstrijdGegevens($combinations_id);
                $wedstrijd_wijziging = new WedstrijdWijziging();
                $wedstrijd_wijziging->setToernooiId($toernooi_id);
                //$wedstrijd_wijziging->setIndiener($this->getUser()->getId());
                $wedstrijd_wijziging->setSpeler1($wedstrijd_gegevens['speler1']);
                $wedstrijd_wijziging->setPartner1($wedstrijd_gegevens['partner1']);
                $wedstrijd_wijziging->setSpeler2($wedstrijd_gegevens['speler2']);
                $wedstrijd_wijziging->setPartner2($wedstrijd_gegevens['partner2']);
                $wedstrijd_wijziging->setWijzigingStatus("nieuw");
                $wedstrijd_wijziging->setActie("toevoegen");
                $wedstrijd_wijziging->setIndienerVeranderd(false);
                $wedstrijd_wijziging->setWedstrijdId($combinations_id);
                $wedstrijd_wijziging->setCatType($wedstrijden[$index]['categorie']);

                $herplanOpties = $this->wedstrijdWijzigingService->getHerplanOpties(
                    $wedstrijd_wijziging,
                    $toernooi,
                    false
                );
                if (count($herplanOpties) > 0 && $herplanOpties[0]->getNaamMetWedstrijd() == null) {
                    // Er is een tijdslot zonder verhindering of dubbele wedstrijd
                    // Koppel de herplan_optie aan de wedstrijd_wijziging
                    $wedstrijd_wijziging->setHerplanOptieId($herplanOpties[0]->getId());
                    // Zet het tijdslot in de wedstrijd_wijziging (zowel voor oud als nieuw)
                    $wedstrijd_wijziging->setTijdslotOud($herplanOpties[0]->getTijdslot());
                    $wedstrijd_wijziging->setTijdslotNieuw($herplanOpties[0]->getTijdslot());
                    // Zet het tijdslot in combinations voor de nieuwe wedstrijd
                    $combinations = $this->combinationsRepository->find($combinations_id);
                    $combinations->setTijdslot($herplanOpties[0]->getTijdslot());
                    //$tijdslot_gegevens = $this->mysqlDB->getTijdslotGegevens($herplanOpties[0]->getTijdslot());
                    $this->entityManager->persist($wedstrijd_wijziging);
                    $this->entityManager->persist($combinations);
                    $this->entityManager->flush();

                    // Maak een bericht aan voor de deelnemers en toernooileiding en verzend het:
                    $bericht = $this->wedstrijdWijzigingService->maakBericht(
                        $wedstrijd_wijziging,
                        "Extra wedstrijd aangeboden",
                        "toevoegen",
                        0
                    );
                    // zet de verzendtijd en sla het nieuwe bericht op
                    $bericht->setVerzendTijdNow();
                    $this->entityManager->persist($bericht);
                    // verstuur de mails naar alle deelnemers met wijziging
                    $this->wedstrijdWijzigingService->verstuur_berichtmails($bericht);
                    $wedstrijd_wijziging->setWijzigingStatus("verstuurd");
                    $this->entityManager->persist($wedstrijd_wijziging);
                    $this->entityManager->flush();

                    return $this->extra_wedstrijden_automatisch();
                } else {
                    // verwijder herplan_opties en wedstrijd_wijziging
                    $this->wedstrijdWijzigingService->verwijderHerplanOpties($wedstrijd_wijziging, 0);
                    $this->entityManager->remove($wedstrijd_wijziging);
                    $this->entityManager->flush();
                    // zoek een herplan optie voor de volgende wedstrijd
                }
            }
            $index++;
        }
        // presenteer tenslotte alle wedstrijd wijzigingen:
        return $this->redirectToRoute("toon_wedstrijd_wijzigingen");
    }

}