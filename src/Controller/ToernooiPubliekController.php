<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\SpelerUitslagFormType;
use App\Form\UitslagFormData;
use App\Repository\CategorieRepository;
use App\Repository\CombinationsRepository;
use App\Repository\DeelnemerRepository;
use App\Repository\InschrijvingRepository;
use App\Repository\ToernooiRepository;
use App\Repository\WedstrijdRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class ToernooiPubliekController extends AbstractController
{
    public function __construct(
        private readonly CombinationsRepository $combinationsRepository,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly DeelnemerRepository $deelnemerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly ToernooiRepository $toernooiRepository,
        private readonly CategorieRepository $categorieRepository,
        private readonly WedstrijdRepository $wedstrijdRepository
    ) {
        if (!$this->requestStack->getSession()->has("gekozen_rol")) {
            $this->requestStack->getSession()->set("gekozen_rol", "speler");
        }
    }

    /**
     * @Route("/", name = "uitleg")
     */
    public function uitleg(): Response
    {
        return $this->render('home/uitleg_speler.html.twig');
    }

    /**
     * @Route("/uitleg_aanvraag", name = "uitleg_aanvraag")
     */
    public function uitleg_aanvraag(): Response
    {
        return $this->render('home/uitleg_aanvraag.html.twig');
    }

    /**
     * @Route("/uitleg_toernooi_admin", name = "uitleg_toernooi_admin")
     */
    public function uitleg_toernooi_admin(): Response
    {
        return $this->render('home/uitleg_toernooi_admin.html.twig');
    }

    /**
     * @Route("/home", name = "home")
     */
    public function home(): Response
    {
        return $this->render('home/home.html.twig');
    }

    /**
     * @Route("/access_denied", name = "access_denied")
     */
    public function access_denied(): Response
    {
        return $this->render('toernooi/access_denied.html.twig');
    }

    /**
     * @Route("/selectToernooi/{toernooiId}/{toernooiNaam}", name="selectToernooi")
     */
    public function selectToernooi(int $toernooiId, string $toernooiNaam): Response
    {
        $toernooiStatus = $this->toernooiRepository->find($toernooiId)->getToernooiStatus();
        $this->logger->info("selectToernooi: huidig toernooi_id: " . $toernooiId . ", toernooi_naam: " . $toernooiNaam);
        $this->requestStack->getSession()->set("huidig_toernooi_id", $toernooiId);
        $this->requestStack->getSession()->set("huidig_toernooi", $toernooiNaam);
        $this->requestStack->getSession()->set("toernooi_status", $toernooiStatus);
        // TODO: misschien aparte home pages voor speler, toernooi_admin en aanvrager maken
        return $this->json(['redirecturl' => $this->generateUrl('home')]);
    }

    /**
     * @Route("/login_met_rol/{rol}", name="login_met_rol")
     * login met rol aanvrager of toernooi_admin
     */
    public function loginMetRol(string $rol): Response
    {
        $this->logger->info("loginMetRol: rol: " . $rol);
        $this->requestStack->getSession()->remove("huidig_toernooi_id");
        $this->requestStack->getSession()->remove("huidig_toernooi");
        if ($rol == 'aanvrager') {
            return $this->json(['redirecturl' => $this->generateUrl('aanvrager_home')]);
        } else {
            if ($rol == 'admin_toernooi') {
                return $this->json(['redirecturl' => $this->generateUrl('admin_toernooien')]);
            }
        }
        // Log in als speler
        // TODO: productielogging toevoegen
        return $this->json(['redirecturl' => $this->generateUrl('app_login')]);
    }

    /**
     * @Route("/inschrijvingen", name = "inschrijvingen")
     */
    public function inschrijvingen(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $categorieen = $this->categorieRepository->findBy(['toernooi_id' => $toernooiId]);

        return $this->render('inschrijving/toon_inschrijvingen.html.twig', [
            'categorieen' => $categorieen,
        ]);
    }

    /**
     * @Route("/toon_inschrijvingen/{categorie}", name = "toon_inschrijvingen")
     */
    public function toon_inschrijvingen(string $categorie): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $inschrijvingen = $this->inschrijvingRepository->toonInschrijvingen($toernooiId, $categorie);
        $categorieen = $this->categorieRepository->findBy(['toernooi_id' => $toernooiId]);

        return $this->render('inschrijving/toon_inschrijvingen.html.twig', [
            'inschrijvingen' => json_encode($inschrijvingen),
            'categorieen' => $categorieen,
        ]);
    }

    /**
     * render_wedstrijden is een hulpfunctie voor de toon_*_wedstrijden functies
     */
    private function render_wedstrijden(int $toernooiId, string $wedstrijden): Response
    {
        $categorieen = $this->categorieRepository->findBy(['toernooi_id' => $toernooiId]);
        $toernooiDagen = $this->toernooiRepository->getToernooiDagen($toernooiId);

        return $this->render('wedstrijden/toon_wedstrijden.html.twig', [
            'wedstrijden' => $wedstrijden,
            'categorieen' => $categorieen,
            'toernooidagen' => $toernooiDagen,
        ]);
    }

    /**
     * @Route("/toon_wedstrijden", name = "toon_wedstrijden")
     */
    public function toon_wedstrijden(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $wedstrijden = $this->combinationsRepository->toonWedstrijden($toernooiId);
        return $this->render_wedstrijden($toernooiId, json_encode($wedstrijden));
    }

    /**
     * @Route("/mijn_wedstrijden", name = "mijn_wedstrijden")
     * @IsGranted("ROLE_USER")
     */
    public function mijn_wedstrijden(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $user = $this->getUser();
        $deelnemer = $this->deelnemerRepository->find($user->getId());
        $wedstrijden = $this->combinationsRepository->toonDeelnemerWedstrijden($toernooiId, $user->getId());
        return $this->render('wedstrijden/mijn_wedstrijden.html.twig', [
            'wedstrijden' => $wedstrijden,
            'naam' => $deelnemer->getNaam(),
            'toernooi' => $toernooi,
        ]);
    }

    /**
     * Wordt aangeroepen vanuit link in mail met geplande wedstrijden.
     * @Route("/mijn_wedstrijden_met_id/{toernooiId}", name = "mijn_wedstrijden_met_id")
     * @IsGranted("ROLE_USER")
     */
    public function mijn_wedstrijden_met_id(int $toernooiId): Response
    {
        // Selecteer het meegegeven toernooi_id:
        $toernooi = $this->toernooiRepository->find($toernooiId);
        if ($toernooi) {
            $this->requestStack->getSession()->set("huidig_toernooi", $toernooi->getToernooiNaam());
            $this->requestStack->getSession()->set("huidig_toernooi_id", $toernooiId);

            return $this->mijn_wedstrijden();
        } else {
            return $this->redirectToRoute('home');
            // TODO: log dit als security event
        }
    }

    /**
     * Laat de verliezend speler zelf de uitslag invoeren.
     * @Route("/speler_uitslag/{wedstrijd_id}", name="speler_uitslag")
     * @IsGranted("ROLE_USER")
     * @IsGranted("SPELEN", subject = 0)
     */
    public function spelerUitslag(Request $request, int $wedstrijd_id): Response
    {
        $wedstrijd = $this->wedstrijdRepository->find($wedstrijd_id);
        $uitslag = new UitslagFormData();
        $uitslag->setBaan($wedstrijd->getBaan());
        // De ingelogde user is de verliezer, vertaal dit naar het winnend team, 1 of 2:
        $verliezer = $this->getUser()->getId();
        $wedstrGeg = $this->combinationsRepository->getWedstrijdGegevens($wedstrijd_id);
        if (($wedstrGeg['speler1'] == $verliezer) || ($wedstrGeg['partner1'] == $verliezer)) {
            $uitslag->setWinnaar(2);
        } else {
            if (($wedstrGeg['speler2'] == $verliezer) || ($wedstrGeg['partner2'] == $verliezer)) {
                $uitslag->setWinnaar(1);
            } else {
                $this->logger->error(
                    "spelerUitslag(): ingelogde user: " . $this->getUser()->getEmail(
                    ) . " speelt niet in wedstrijd: " . $wedstrijd_id . "!"
                );
                return $this->redirectToRoute('home');
            }
        }
        // Een speler kan alleen de uitslag van een volledig gespeelde wedstrijd invoeren;
        // onderbroken wedstrijdstanden kunnen alleen door een toernooi admin worden ingevoerd.
        $uitslag->setWedstrijdStatus("gespeeld");
        $form = $this->createForm(SpelerUitslagFormType::class, $uitslag);

        $now = new DateTime();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $wedstrijd->copyUitslagData($formData);
            $wedstrijd->setEindtijd($now);

            $this->entityManager->persist($wedstrijd);
            $this->entityManager->flush();

            $this->addFlash("feedback", "Uitslag opgeslagen");
            $text = "Ach, het is maar een spelletje, toch?";
            return $this->render('wedstrijden/kop_op.html.twig', [
                'text' => $text,
            ]);
        }
        return $this->render('wedstrijden/speler_uitslag.html.twig', [
            'spelerUitslagForm' => $form->createView(),
            'datum' => $now,
            'categorie' => $wedstrGeg['categorie'],
            'team1_speler1' => $wedstrGeg['naam_speler1'],
            'team1_speler2' => $wedstrGeg['naam_partner1'],
            'team2_speler1' => $wedstrGeg['naam_speler2'],
            'team2_speler2' => $wedstrGeg['naam_partner2'],
        ]);
    }

    /**
     * @Route("/ranglijsten", name="ranglijsten")
     */
    public function toon_ranglijsten(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $ranglijsten = $this->wedstrijdRepository->getRanglijstGegevens($toernooiId);
        return $this->render('wedstrijden/ranglijsten.html.twig', [
            'ranglijsten' => $ranglijsten
        ]);
    }

    /**
     * @Route("/mijn_ranglijsten", name="mijn_ranglijsten")
     */
    public function toon_mijn_ranglijsten(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $user = $this->getUser();
        $ranglijsten = $this->wedstrijdRepository->getUserRanglijstGegevens($toernooiId, $user->getId());
        return $this->render('wedstrijden/ranglijsten.html.twig', [
            'ranglijsten' => $ranglijsten
        ]);
    }

}