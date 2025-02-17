<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Verhindering;
use App\Form\AanvraagFormType;
use App\Repository\InschrijvingRepository;
use App\Repository\SpeeltijdenRepository;
use App\Repository\ToernooiRepository;
use App\Repository\VerhinderingRepository;
use App\Service\SharedServices;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Functies voor de Admin
 */
class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly RequestStack $requestStack,
        private readonly ToernooiRepository $toernooiRepository,
        private readonly SpeeltijdenRepository $speeltijdenRepository,
        private readonly VerhinderingRepository $verhinderingRepository
    ) {
    }

    /**
     * @Route("/admin_toernooien", name="admin_toernooien")
     * @IsGranted("ROLE_USER")
     */
    public function adminToernooien(SharedServices $sharedServices): Response
    {
        $this->requestStack->getSession()->set("gekozen_rol", 'admin_toernooi');
        $resultArray = $this->toernooiRepository->getToernooienVoorAdmin($this->getUser()->getEmail());

        if (!$resultArray) {
            $this->addFlash("feedback", "U bent geen toernooi administrator voor een actueel toernooi");
            return $this->render('home/home.html.twig');
        } else {
            if (count($resultArray) == 1) {
                $toernooiId = $resultArray[0]['id'];
                $toernooi = $this->toernooiRepository->find($toernooiId);
                $sharedServices->setToernooi($toernooi);
                $this->addFlash(
                    'feedback',
                    'Ingelogd als toernooi administrator voor toernooi: ' . $resultArray[0]['toernooi_naam']
                );
                return $this->render('home/home.html.twig');
            } else {
                $this->addFlash('feedback', 'Ingelogd als toernooi administrator, kies eerst een toernooi!');
                return $this->render('toernooien/toernooien.html.twig', [
                    'toernooien' => $resultArray,
                ]);
            }
        }
    }

    /**
     * @Route("/generate_users", name="generate_users")
     * @IsGranted("ROLE_ADMIN")
     * Generates 270 testusers in the database
     */
    public function generateUsers(UserPasswordHasherInterface $userPasswordHasher): Response
    {
        set_time_limit(300);
        for ($i = 1; $i < 270; $i++) {
            $user = new User();
            // encode the plain password
            $user->setEmail("testuser" . $i . "@tst.nl");
            $user->setRoles(["TEST_USER"]);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    "722pz5@MjXfySB+p"
                )
            );

            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        $this->addFlash('feedback', '270 testusers aangemaakt');
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/toernooi_statussen/{status}", name="toernooi_statussen")
     * @IsGranted("ROLE_ADMIN")
     * Bekijk en bewerk toernooi_statussen
     */
    public function toernooiStatussen(string $status): Response
    {
        $toernooien = $this->toernooiRepository->findBy(($status == 'alle' ? [] : ['toernooi_status' => $status]));

        if (empty($toernooien)) {
            $this->addFlash('feedback', 'geen toernooien gevonden met status "' . $status . '"');
        }
        return $this->render('toernooien/toernooi_statussen.html.twig', [
            'toernooien' => $toernooien,
            'status' => $status,
        ]);
    }

    /**
     * @Route("/setToernooiStatus/{toernooiId}/{status}", name="setToernooiStatus")
     * @IsGranted("ROLE_ADMIN")
     */
    public function setToernooiStatus(?int $toernooiId, string $status): Response
    {
        if (is_numeric($toernooiId)) {
            $toernooi = $this->toernooiRepository->find($toernooiId);
            $toernooi->setToernooiStatus($status);
            $this->entityManager->persist($toernooi);
            $this->entityManager->flush();
        }

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * @Route("/setBlokkeerMails/{toernooiId}/{val}", name="setBlokkeerMails")
     * @IsGranted("ROLE_ADMIN")
     */
    public function setBlokkeerMails(int $toernooiId, bool $val): Response
    {
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $toernooi->setBlokkeerMails($val);
        $this->entityManager->persist($toernooi);
        $this->entityManager->flush();

        return new Response('OK', Response::HTTP_OK);
    }


    /**
     * @Route("/toonAanvraag/{toernooiId}", name="toonAanvraag")
     * @IsGranted("ROLE_ADMIN")
     */
    public function toonAanvraag(int $toernooiId): Response
    {
        $toernooi = $this->toernooiRepository->find($toernooiId);

        // Maak het toernooigegevens formulier aan:
        $form = $this->createForm(AanvraagFormType::class, $toernooi, ['attr' => ['readonly' => true]]);

        return $this->render('toernooi/modal_aanvraag.html.twig', [
            'aanvraagForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/genereer_verh", name="genereer_verh")
     * @IsGranted("ROLE_ADMIN")
     * Genereer testverhinderingen voor het geselecteerde toernooi
     */
    public function genereerVerhinderingen(Request $request): Response
    {
        /*
        Haal de speeltijden op voor het toernooi
        Haal de verhinderingenduur op voor het toernooi
        Haal alle actieve inschrijvingen op
        Voor iedere inschrijving:
            Kies 2 typen verhindering:
                hele dag/avond
                begin avond door de week tot 19u
                blok van 3 uur weekend, vanaf 9u, 12u of 15u
            Kies random dag voor deze typen verhindering (indien van toepassing)
            Haal 1e user van inschrijving op
            maak met 0 gevulde array verhinderingen[aantal dagen][aantal cellen per dag] aan
            Vul verhinderingen voor dit type verhindering
        */
        set_time_limit(120);

        $toernooiId = $request->getSession()->get('huidig_toernooi_id');
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $aantalDagen = $toernooi->getAantalDagen();
        $speeltijden = $this->speeltijdenRepository->getSpeeltijden($toernooiId);
        // Haal alle actieve inschrijvingen op
        $inschrijvingen = $this->inschrijvingRepository->findBy(['toernooi_id' => $toernooiId]);
        // $this->logger->info("genereer_verhinderingen, inschrijvingen: ".print_r($inschrijvingen, true));
        for ($i = 0; $i < count($inschrijvingen); $i++) {
            $bondsnummer = $inschrijvingen[$i]->getDeelnemerA();
            // verwijder eerst de oude verhinderingen voor dit toernooi_id en bondsnummer:
            $this->verhinderingRepository->verwijderVerhinderingen($toernooiId, $bondsnummer);
            // verhinder 2 verschillende hele dagen en de eerste 3 uur van een andere dag:
            $dag1 = rand(1, $aantalDagen);
            $dag2 = ($dag1 + 3) % $aantalDagen + 1;
            $dag3 = ($dag1 + 5) % $aantalDagen + 1;

            $verhindering = new Verhindering();
            $verhindering->setToernooiId($toernooiId);
            $verhindering->setBondsnr($bondsnummer);
            $verhindering->setDagnummer($dag1);
            $verhindering->setHeleDag(true);

            $verhindering2 = new Verhindering();
            $verhindering2->setToernooiId($toernooiId);
            $verhindering2->setBondsnr($bondsnummer);
            $verhindering2->setDagnummer($dag1);
            $verhindering2->setHeleDag(true);

            $verhindering3 = new Verhindering();
            $verhindering3->setToernooiId($toernooiId);
            $verhindering3->setBondsnr($bondsnummer);
            $verhindering3->setDagnummer($dag1);
            $verhindering3->setHeleDag(false);

            $this->logger->info("genereer verhinderingen, verhindering: " . print_r($verhindering, true));
            $this->logger->info("genereer verhinderingen, verhindering2: " . print_r($verhindering2, true));
            $this->logger->info("genereer verhinderingen, verhindering3: " . print_r($verhindering3, true));
            $this->entityManager->persist($verhindering);
            $this->entityManager->flush();

            $verhindering2->setDagnummer($dag2);
            $this->entityManager->persist($verhindering2);
            $this->entityManager->flush();

            $verhindering3->setDagnummer($dag3);
            $verhStart = date_create_from_format("H:i:s", $speeltijden[$dag3 - 1]['starttijd']);
            $this->logger->info("verh start: " . print_r($verhStart, true));
            $verhindering3->setBegintijd($verhStart);
            $verhEind = clone $verhStart;
            date_add($verhEind, date_interval_create_from_date_string("3 hours"));
            $this->logger->info("verh eind: " . print_r($verhEind, true));
            $verhindering3->setEindtijd($verhEind);
            $this->entityManager->persist($verhindering3);
            $this->entityManager->flush();
        }

        $this->addFlash('feedback', 'verhinderingen gegenereerd');
        return $this->redirectToRoute('home');
    }

}
 
