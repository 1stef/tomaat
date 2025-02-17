<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Deelnemer;
use App\Entity\Verhindering;
use App\Form\DeelnemerAccountType;
use App\Form\InschrijvingFormData;
use App\Form\InschrijvingType;
use App\Form\VerhinderingFormType;
use App\Repository\DeelnemerRepository;
use App\Repository\InschrijvingRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\ToernooiRepository;
use App\Repository\VerhinderingRepository;
use App\Service\InschrijvingService;
use App\Service\SharedServices;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class InschrijvingenController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly DeelnemerRepository $deelnemerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly InschrijvingService $inschrijvingService,
        private readonly ToernooiRepository $toernooiRepository,
        private readonly SpeeltijdenRepository $speeltijdenRepository,
        private readonly VerhinderingRepository $verhinderingRepository
    ) {
    }

    /**
     * @Route("/toernooien", name="toernooien")
     */
    public function toernooien(): Response
    {
        $toernooien = $this->toernooiRepository->getToernooien();

        return $this->render(
            'toernooien/toernooien.html.twig',
            ['toernooien' => $toernooien]
        );
    }

    /**
     * @Route("/selecteerEnSchrijfIn/{toernooiId}", name="selecteerEnSchrijfIn")
     * @IsGranted("ROLE_USER")
     */
    public function selecteerEnSchrijfIn(int $toernooiId, SharedServices $sharedServices): Response
    {
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $sharedServices->setToernooi($toernooi);
        return $this->redirect($this->generateUrl('schrijfIn'));
    }

    /**
     * @Route("/schrijfIn", name="schrijfIn")
     * @IsGranted("ROLE_USER")
     * @IsGranted("INSCHRIJVEN", subject = 0))
     */
    public function inschrijving(Request $request): Response
    {
        // Bepaal het deelnemer object voor de ingelogde gebruiker:
        $user = $this->getUser();
        $user_printed = print_r($user, true);
        $this->logger->info("inschrijving, this->getUser(): " . $user_printed);
        $error_response = new Response('ERROR', Response::HTTP_OK);
        if (!$user) {
            return $error_response;
        }

        $deelnemer = $this->deelnemerRepository->find($user->getId());
        if (!$deelnemer) {
            $deelnemer = new Deelnemer($user->getId());
            return $this->redirectToRoute('deelnemer_account', ['actie' => 'schrijfIn']);
        }
        $this->logger->info("inschrijving, deelnemer: " . print_r($deelnemer, true));

        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $this->logger->info("inschrijving, huidig_toernooi_id: " . $toernooiId);
        if (!$toernooiId) {
            return $error_response;
        }

        $inschrijvingen = $this->inschrijvingRepository->findBy(
            ['toernooi_id' => $toernooiId, 'deelnemerA' => $deelnemer->bondsnummer]
        );
        $inschrijvingFormData = new InschrijvingFormData();
        $inschrijvingFormData->init($toernooiId, $deelnemer->bondsnummer, $inschrijvingen);
        $this->logger->info("inschrijving, inschrijvingFormData: " . print_r($inschrijvingFormData, true));
        // create the form again with initialised inschrijvingFormData:
        $form = $this->createForm(InschrijvingType::class, $inschrijvingFormData);

        $accountForm = $this->createForm(
            DeelnemerAccountType::class,
            $deelnemer,
            [
                'action' => $this->generateUrl('update_account'),
                'method' => 'POST',
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inschrijvingFormData = $form->getData();
            $this->logger->info("inschrijving, submit inschrijvingFormData: " . print_r($inschrijvingFormData, true));

            // sla de nieuwe of aangepaste inschrijvingen op
            $this->inschrijvingService->persistInschrijvingen($inschrijvingFormData);

            $this->addFlash('feedback', 'Inschrijving opgeslagen. Verhinderingen?');
            return $this->redirectToRoute('wijzig_verhinderingen', ['wedstrijd_wijziging_id' => 0]);
        }

        return $this->render(
            'inschrijving/inschrijving.html.twig',
            [
                'inschrijvingForm' => $form->createView(),
                'accountData' => $accountForm->createView(),
            ]
        );
    }

    /**
     * @Route("/nieuweVerhindering/{wedstrijd_wijziging_id}", name="nieuweVerhindering")
     * @IsGranted("ROLE_USER")
     * @IsGranted("INSCHRIJVEN_SPELEN", subject = 0))
     *
     */
    public function nieuweVerhindering(int $wedstrijd_wijziging_id, Request $request): Response
    {
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->logger->alert(
                "SECURITY ALERT: nieuweVerhindering aangeroepen zonder geselecteerd toernooi door user: " . $this->getUser(
                )->getEmail()
            );
            return $this->redirectToRoute('home');
        }
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $verhindering = new Verhindering();
        $verhindering->setToernooiId($toernooiId);
        $deelnemer = $this->deelnemerRepository->find($this->getUser()->getId());
        $verhindering->setBondsnr($deelnemer->getBondsnummer());
        $speeltijden = json_encode($this->speeltijdenRepository->getSpeeltijden($toernooiId));
        $verhindering->init($speeltijden, $this->logger);

        $verhinderingForm = $this->createForm(VerhinderingFormType::class, $verhindering);
        $verhinderingForm->handleRequest($request);
        if ($verhinderingForm->isSubmitted() && $verhinderingForm->isValid()) {
            $verhinderingForm->clearErrors();
            $verhindering = $verhinderingForm->getData();
            $this->entityManager->persist($verhindering);
            $this->entityManager->flush();

            return $this->redirectToRoute('wijzig_verhinderingen', ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id]
            );
        }
        return $this->render('inschrijving/nieuwe_verhindering.html.twig', [
            'verhinderingForm' => $verhinderingForm->createView(),
            'toernooi' => $toernooi,
            'speeltijden' => $speeltijden,
            'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id
        ]);
    }

    /**
     * @Route("/verhindering/{id}/{wedstrijd_wijziging_id}", name="verhindering")
     * @IsGranted("ROLE_USER")
     * @IsGranted("INSCHRIJVEN_SPELEN", subject = 0))
     */
    public function wijzigVerhindering(int $id, int $wedstrijd_wijziging_id, Request $request): Response
    {
        $this->logger->info("wijzig verhindering, id: " . $id);
        $verhindering = $this->verhinderingRepository->find($id);
        $this->logger->info("verhinderingFormulier, verhindering: " . print_r($verhindering, true));
        if (!$verhindering) {
            $this->logger->alert(
                "SECURITY ALERT: wijzig verhindering aangeroepen voor ongeldig id: " . $id . " door user: " . $this->getUser(
                )->getEmail()
            );
            return $this->redirectToRoute('home');
        }
        $this->logger->info("wijzig verhindering, verhindering: " . print_r($verhindering, true));
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $speeltijden = json_encode($this->speeltijdenRepository->getSpeeltijden($toernooiId));
        $verhindering->init($speeltijden, $this->logger);

        $verhinderingForm = $this->createForm(VerhinderingFormType::class, $verhindering);
        $verhinderingForm->handleRequest($request);
        if ($verhinderingForm->isSubmitted() && $verhinderingForm->isValid()) {
            $verhinderingForm->clearErrors();
            $verhindering = $verhinderingForm->getData();
            //$this->logger->info("inschrijving, persist verhindering: ".print_r($verhindering, true));

            $this->entityManager->persist($verhindering);
            $this->entityManager->flush();

            return $this->redirectToRoute('wijzig_verhinderingen', ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id]
            );
        }
        return $this->render('inschrijving/verhindering.html.twig', [
            'verhinderingForm' => $verhinderingForm->createView(),
            'toernooi' => $toernooi,
            'speeltijden' => $speeltijden,
            'wedstrijd_wijziging_id' => $wedstrijd_wijziging_id,
        ]);
    }

    /**
     * @Route("/verwijderVerhindering/{id}/{wedstrijd_wijziging_id}", name="verwijderVerhindering")
     * @IsGranted("ROLE_USER")
     * @IsGranted("INSCHRIJVEN_SPELEN", subject = 0))
     */
    public function verwijderVerhindering(int $id, int $wedstrijd_wijziging_id): Response
    {
        $verhindering = $this->verhinderingRepository->find($id);
        if ($verhindering) {
            // check of deze verhindering echt van de ingelogde gebruiker is:
            $deelnemer = $this->deelnemerRepository->findOneBy(array('bondsnummer' => $verhindering->getBondsnr()));
            if ($deelnemer->getUserId() == $this->getUser()->getId()) {
                $this->entityManager->remove($verhindering);
                $this->entityManager->flush();
            } else {
                $this->logger->alert(
                    "SECURITY ALERT: wijzig verhindering aangeroepen voor ongeldig id: " . $id . " door user: " . $this->getUser(
                    )->getEmail()
                );
                return $this->redirectToRoute('home');
            }
        } else {
            $this->logger->alert(
                "SECURITY ALERT: wijzig verhindering aangeroepen voor id dat niet van de gebruiker is: " . $id . " door user: " . $this->getUser(
                )->getEmail()
            );
            return $this->redirectToRoute('home');
        }
        return $this->redirectToRoute('wijzig_verhinderingen', ['wedstrijd_wijziging_id' => $wedstrijd_wijziging_id]);
    }

    /**
     * @Route("/sluit_inschrijving", name="sluit_inschrijving")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0)
     * @IsGranted("INSCHRIJVEN", subject = 0)
     */
    public function sluitInschrijving(): Response
    {
        $toernooiId = $this->requestStack->getSession()->get("huidig_toernooi_id");
        if (is_numeric($toernooiId)) {
            $toernooi = $this->toernooiRepository->find($toernooiId);
            $toernooi->setToernooiStatus("plannen");
            $this->entityManager->persist($toernooi);
            $this->entityManager->flush();
            $this->requestStack->getSession()->set("toernooi_status", "plannen");
            $this->addFlash("feedback", "De inschrijving is nu gesloten. Toernooistatus: plannen");
        }
        return $this->redirectToRoute('home');
    }

    /** @Route("/deelnemer_contact", name="deelnemer_contact")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0)
     */
    public function deelnemerContact(): Response
    {
        $toernooiId = $this->requestStack->getSession()->get("huidig_toernooi_id");
        if (!$toernooiId) {
            // moet niet mogelijk zijn bij IsGranted("ROLE_ADMIN_TOERNOOI")
            $this->logger->alert(
                "SECURITY ALERT: deelnemer_contact aangeroepen zonder geselecteerd toernooi door user: " . $this->getUser(
                )->getEmail()
            );
            return $this->redirectToRoute('home');
        }
        $toernooi_naam = $this->requestStack->getSession()->get("huidig_toernooi");
        $contact_gegevens = $this->deelnemerRepository->getContactGegevens($toernooiId);
        return $this->render('/toernooi/deelnemer_contact.html.twig', [
            'toernooi_naam' => $toernooi_naam,
            'contact_gegevens' => $contact_gegevens,
        ]);
    }

}