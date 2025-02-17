<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gegevens;
use App\Entity\Speeltijden;
use App\Form\GegevensFormType;
use App\Form\SpeeltijdenFormData;
use App\Form\SpeeltijdenFormType;
use App\Repository\CategorieRepository;
use App\Repository\GegevensRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\ToernooiRepository;
use App\Service\TijdslotService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ToernooiGegevensController extends AbstractController
{
    public function __construct(
        private readonly CategorieRepository $categorieRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GegevensRepository $gegevensRepository,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly SpeeltijdenRepository $speeltijdenRepository,
        private readonly TijdslotService $tijdslotService,
        private readonly ToernooiRepository $toernooiRepository
    ) {
    }

    /**
     * @Route("/toernooigegevens", name="toernooigegevens")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("VOORBEREIDEN", subject = 0))
     */
    public function edit(Request $request): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $this->logger->info("Edit toernooigegevens, huidig_toernooi_id: " . $toernooiId);
        if (!$toernooiId) {
            $this->addFlash('feedback', 'Kies eerst een toernooi!');
            return $this->redirectToRoute('toernooien');
        }
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $categorieen = $this->categorieRepository->listCategories($toernooiId);

        $gegevens = $this->gegevensRepository->find($toernooiId);
        if (!$gegevens) {
            $gegevens = new Gegevens($toernooiId);
        }
        //$this->logger->info("Edit toernooigegevens, gegevens: ".print_r($gegevens, true));

        // Maak ook het subformulier voor speeltijden aan:
        $speeltijdenFormData = new SpeeltijdenFormData($toernooiId);

        $speeldagen = $this->speeltijdenRepository->findByToernooiId($toernooiId);

        $aantalDagen = $toernooi->getAantalDagen();
        for ($i = 0; $i < $aantalDagen; $i++) {
            if ($speeldagen && (count($speeldagen) == $aantalDagen)) {
                $speeltijden = $speeldagen[$i];
            } else {
                $speeltijden = new Speeltijden($toernooiId, $i + 1);
            }
            $speeltijdenFormData->getSpeeltijden()->add($speeltijden);
        }

        //$this->logger->info("speeltijdenFormData: ".print_r($speeltijdenFormData, true));
        $speeltijdenForm = $this->createForm(SpeeltijdenFormType::class, $speeltijdenFormData);

        // Maak het toernooigegevens formulier aan:
        $form = $this->createForm(GegevensFormType::class, $gegevens);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info("Edit toernooigegevens, handling valid request");
            $gegevens = $form->getData();

            $this->entityManager->persist($gegevens);
            $this->entityManager->flush();

            return $this->redirectToRoute('home');
        }

        $speeltijdenForm->handleRequest($request);
        $speeltijdenValid = true;
        if ($speeltijdenForm->isSubmitted()) {
            $speeltijdenValid = $speeltijdenForm->isValid();
            if ($speeltijdenValid) {
                $this->logger->info("speeltijden opslaan");
                $formData = $speeltijdenForm->getData();

                foreach ($formData->getSpeeltijden() as $speeldag) {
                    $this->entityManager->persist($speeldag);
                    // $this->logger->info("speeltijden opslaan".print_r($speeldag, true));
                }
                $this->entityManager->flush();
                return $this->redirectToRoute('toernooigegevens');
            }
        }

        return $this->render('toernooi/gegevens.html.twig', [
            'gegevensForm' => $form->createView(),
            'toernooi' => $toernooi,
            'categorieen' => $categorieen,
            'speeltijdenForm' => $speeltijdenForm->createView(),
            'speeltijdenValid' => $speeltijdenValid,
        ]);
    }

    /**
     * @Route("/start_inschrijving", name="start_inschrijving")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("VOORBEREIDEN", subject = 0))
     * Zet de inschrijving open voor het publiek.
     * Controleer eerst of de toernooigegevens, categorieen, speeltijden en tijdsloten ingevuld
     * en consistent zijn voor het huidige toernooi.
     * Zet dan de status op 'inschrijven'.
     */
    public function startInschrijving(ValidatorInterface $validator): Response
    {
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $result = array();
        if ($toernooi->getToernooiStatus() != "voorbereiden inschrijving") {
            $result[0] = "Het aangevraagde toernooi moet geaccepteerd zijn. Check met de aanvrager, neem evt. contact op met support via de website.";
        } else {
            $gegevens = $this->gegevensRepository->find($toernooiId);
            if (!$gegevens) {
                $result[0] = "Vul eerst de toernooigegevens in";
            } else {
                $errors = $validator->validate($gegevens);
                if (count($errors) > 0) {
                    $result[0] = "Zorg dat de toernooigegevens correct zijn ingevuld";
                }
            }
            $categorieen = $this->categorieRepository->findOneBy(['toernooi_id' => $toernooiId]);
            if (empty($categorieen)) {
                // voeg fout toe aan de result array:
                $result[] = "Voer de inschrijvings-categorieën in (minstens één), via het formulier voor teernooigegevens";
            }
            $speeldagen = $this->speeltijdenRepository->findByToernooiId($toernooiId);
            if (count($speeldagen) != $toernooi->getAantalDagen()) {
                // voeg fout toe aan de result array:
                $result[] = "Voer de speeltijden per toernooidag in, via het formulier voor toernooigegevens";
            }
        }
        if (count($result) == 0) {
            // Maak de tijdsloten aan, voor het geval dat nog niet gebeurd was:
            if ($this->tijdslotService->maakTijdsloten($toernooi)) {
                $result[0] = "OK";
                // Zet nu de toernooi status op "inschrijven":
                $toernooi->setToernooiStatus("inschrijven");
                $this->requestStack->getSession()->set("toernooi_status", "inschrijven");
                $this->entityManager->persist($toernooi);
                $this->entityManager->flush();
            } else {
                $result[] = "Fout bij maken Tijdsloten, meld dit s.v.p. aan support@toernooiopmaat.nl";
            }
        }

        // TODO: maak start_inschrijving_result.html.twig, waarin het resultaat van de check
        // getoond wordt en de juiste menus getoond
        return $this->render('toernooi/start_inschrijving_result.html.twig', [
            'result' => $result,
            'toernooi_status' => $toernooi->getToernooiStatus(),
        ]);
    }

    /**
     * @Route("/zet_status_terug", name="zet_status_terug")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * zet_status_terug() zet de status terug naar de status vóór de huidige status in de database
     */
    public function zetStatusTerug(): Response
    {
        $toernooiId = $this->requestStack->getSession()->get("huidig_toernooi_id");
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $toernooiStatus = $toernooi->getToernooiStatus();
        switch ($toernooiStatus) {
            case "inschrijven":
                $this->requestStack->getSession()->set("toernooi_status", "voorbereiden inschrijving");
                $toernooi->setToernooiStatus("voorbereiden inschrijving");
                break;
            case "plannen":
                $this->requestStack->getSession()->set("toernooi_status", "inschrijven");
                $toernooi->setToernooiStatus("inschrijven");
                break;
            case "spelen":
                $this->requestStack->getSession()->set("toernooi_status", "plannen");
                $toernooi->setToernooiStatus("plannen");
                break;
            case "afgesloten":
                $this->requestStack->getSession()->set("toernooi_status", "spelen");
                $toernooi->setToernooiStatus("spelen");
                break;
            default:
                $this->logger->info("zet_status_terug aangeroepen vanuit verkeerde status: " . $toernooiStatus);
        }
        $this->entityManager->persist($toernooi);
        $this->entityManager->flush();
        $toernooiStatus = $toernooi->getToernooiStatus();
        $this->addFlash("feedback", "Status teruggezet naar " . $toernooiStatus);
        return $this->json(['redirecturl' => $this->generateUrl('home')]);
    }
}