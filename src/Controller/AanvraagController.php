<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Toernooi;
use App\Entity\ToernooiAdmin;
use App\Form\AanvraagFormType;
use App\Repository\ToernooiAdminRepository;
use App\Repository\ToernooiRepository;
use App\Service\SharedServices;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class AanvraagController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly ToernooiAdminRepository $toernooiAdminRepository,
        private readonly ToernooiRepository $toernooiRepository
    ) {
    }

    /**
     * @Route("/aanvrager_home", name="aanvrager_home")
     * @IsGranted("ROLE_USER")
     */
    public function aanvragerHome(Request $request): Response
    {
        $request->getSession()->set("gekozen_rol", 'aanvrager');
        $this->addFlash('feedback', 'U bent nu ingelogd als aanvrager');
        return $this->redirectToRoute('home');
    }


    /**
     * Vraag een nieuwe toernooi aan
     * @Route("/aanvraag_toernooi", name="aanvraag_toernooi")
     * @IsGranted("ROLE_USER")
     */
    public function aanvraagToernooi(Request $request): Response
    {
        $toernooi = new Toernooi();
        $user = $this->getUser();
        $toernooi->setAdminId($user->getId());
        $toernooi->setAanvragerEmail($user->getEmail());
        $toernooi->setToernooiStatus('aangevraagd');

        // Maak het toernooigegevens formulier aan:
        $form = $this->createForm(AanvraagFormType::class, $toernooi);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $toernooi = $form->getData();

            $this->entityManager->persist($toernooi);
            $this->entityManager->flush();

            // Voeg de aanvrager ook toe als toernooi administrator:
            $toernooiAdmin = new ToernooiAdmin();
            $toernooiAdmin->setToernooiId($toernooi->getId());
            $toernooiAdmin->setAdminEmail($toernooi->getAanvragerEmail());
            $this->entityManager->persist($toernooiAdmin);
            $this->entityManager->flush();

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@toernooiopmaat.nl', 'Toernooi Op Maat aangevraagd'))
                ->to(new Address('stef.steeman@gmail.com'))
                ->subject('Toernooi Op Maat aangevraagd')
                ->htmlTemplate('toernooi/aanvraag_notificatie_email.html.twig')
                ->context([
                    'aanvrager' => $toernooi->getAanvragerEmail(),
                    'toernooi_naam' => $toernooi->getToernooiNaam(),
                    'vereniging' => $toernooi->getVereniging(),
                ]);
            if (!empty($_ENV['SEND_MAILS']) && $_ENV['SEND_MAILS'] == "off") {
                // stuur geen mails in deze omgeving: log de inhoud van de mail
                $rendered_email = $this->render('toernooi/aanvraag_notificatie_email.html.twig', [
                    'aanvrager' => $toernooi->getAanvragerEmail(),
                    'toernooi_naam' => $toernooi->getToernooiNaam(),
                    'vereniging' => $toernooi->getVereniging(),
                ]);
                $this->logger->info("aanvraagToernooi op dev omgeving, inhoud notificatie-mail: " . $rendered_email);
                $this->addFlash('feedback', 'notificatie mail alleen gelogd vanwege dev omgeving');
            } else {
                try {
                    $this->mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    // some error prevented the email sending; display an
                    // error message or try to resend the message
                    $this->logger->error("aanvraagToernooi: fout bij verzenden notificatie-mail: " . $e->getMessage());
                }
                $this->logger->info("aanvraagToernooi: mail verzonden ");
            }

            // TODO: redirect naar een bedankt pagina
            return $this->redirectToRoute('home');
        }

        return $this->render('toernooi/aanvraag.html.twig', [
            'aanvraagForm' => $form->createView()
        ]);
    }

    /**
     * Wijzig gegevens voor een aangevraagd toernooi
     * @Route("/wijzig_aanvraag", name="wijzig_aanvraag")
     * @IsGranted("ROLE_USER", subject = 0)
     */
    public function wijzigAanvraag(Request $request, SharedServices $sharedServices): Response
    {
        $user = $this->getUser();
        $toernooien = $this->toernooiRepository->getToernooienVoorAanvrager($user->getId());
        if (!$toernooien) {
            // toon melding dat deze user geen actuele toernooien heeft
            $this->addFlash('feedback', 'U heeft geen actuele toernooien aangevraagd');
            return $this->redirectToRoute('home');
        } else {
            if (count($toernooien) == 1) {
                // haal de aanvraaggegevens op voor dit toernooi
                $toernooiId = $toernooien[0]['id'];
                $toernooi = $this->toernooiRepository->find($toernooiId);
                $sharedServices->setToernooi($toernooi);
                return $this->wijzigAanvraagMetId($request, $toernooi);
            } else {
                // er zijn meerdere actuele toernooien: laat het formulier voor selecteren van het toernooi zien
                $toernooiId = $request->getSession()->get("huidig_toernooi_id");
                if ($toernooiId) {
                    $toernooi = $this->toernooiRepository->find($toernooiId);
                    return $this->wijzigAanvraagMetId($request, $toernooi);
                } else {
                    return $this->render('toernooien/toernooien.html.twig', [
                        'toernooien' => $toernooien,
                    ]);
                }
            }
        }
    }

    /**
     * Wijzig gegevens voor een aangevraagd toernooi
     */
    public function wijzigAanvraagMetId(Request $request, $toernooi): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AANVRAGER', $toernooi);
        // Maak het toernooiaanvraag formulier aan:
        $form = $this->createForm(AanvraagFormType::class, $toernooi);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $toernooi = $form->getData();
            $this->entityManager->persist($toernooi);
            $this->entityManager->flush();

            // TODO: redirect naar een bedankt pagina
            return $this->redirectToRoute('home');
        }

        return $this->render('toernooi/aanvraag.html.twig', [
            'aanvraagForm' => $form->createView()
        ]);
    }

    /**
     * Wijzig toernooi admins voor een aangevraagd toernooi
     * @Route("/wijzig_admins", name="wijzig_admins")
     * @IsGranted("ROLE_USER", subject = 0)
     */
    public function wijzigAdmins(Request $request, SharedServices $sharedServices): Response
    {
        $user = $this->getUser();
        $toernooien = $this->toernooiRepository->getToernooienVoorAanvrager($user->getId());
        if (!$toernooien) {
            // toon melding dat deze user geen actuele toernooien heeft
            $this->addFlash('feedback', 'U heeft geen actuele toernooien aangevraagd');
            return $this->redirectToRoute('home');
        } else {
            if (count($toernooien) == 1) {
                // haal de aanvraaggegevens op voor dit toernooi
                $toernooiId = $toernooien[0]['id'];
                $toernooi = $this->toernooiRepository->find($toernooiId);
                $sharedServices->setToernooi($toernooi);
                return $this->wijzigAdminsMetId($toernooi);
            } else {
                // er zijn meerdere actuele toernooien: laat het formulier voor selecteren van het toernooi zien
                $toernooiId = $request->getSession()->get("huidig_toernooi_id");
                if ($toernooiId) {
                    $toernooi = $this->toernooiRepository->find($toernooiId);
                    return $this->wijzigAdminsMetId($toernooi);
                } else {
                    return $this->render('toernooien/toernooien.html.twig', [
                        'toernooien' => $toernooien,
                    ]);
                }
            }
        }
    }

    /**
     * Wijzig toernooi admins voor een aangevraagd toernooi
     */
    public function wijzigAdminsMetId(Toernooi $toernooi): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AANVRAGER', $toernooi);
        $toernooiAdmins = $this->toernooiAdminRepository->findBy(['toernooi_id' => $toernooi->getId()]);

        return $this->render('toernooi/wijzig_admins.html.twig', [
            'toernooi' => $toernooi,
            'toernooi_admins' => $toernooiAdmins
        ]);
    }

    /**
     * Voeg een toernooi administrator toe
     * @Route("add_admin/{toernooiId}/{emailAdmin}", name="add_admin")
     * @IsGranted("ROLE_AANVRAGER", subject = 0)
     */
    function addAdmin(int $toernooiId, string $emailAdmin): Response
    {
        $this->logger->info("addAdmin, toernooi_id: " . $toernooiId . " email_admin: " . $emailAdmin);
        $response = new JsonResponse();

        if (empty(
        $this->toernooiAdminRepository->findOneBy(['toernooi_id' => $toernooiId, 'admin_email' => $emailAdmin])
        )) {
            $toernooiAdmin = new ToernooiAdmin();
            $toernooiAdmin->setToernooiId($toernooiId);
            $toernooiAdmin->setAdminEmail($emailAdmin);
            $this->entityManager->persist($toernooiAdmin);
            try {
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->logger->error('addAdmin failed, error message: ' . $e->getMessage());
                $response->setData(
                    ['status' => 'NOK', 'message' => 'Toevoegen toernooi admin mislukt, probeer het later nog eens']
                );
            }
            $response->setData(['status' => 'OK', 'email_admin' => $emailAdmin]);
        } else {
            $message = "Toernooi admin met email '" . $emailAdmin . "' bestaat al voor dit toernooi";
            $response->setData(['status' => 'NOK', 'message' => $message]);
        }
        return $response;
    }

    /**
     * Verwijder een toernooi administrator
     * @Route("delete_admin/{toernooiId}/{emailAdmin}", name="delete_admin")
     * @IsGranted("ROLE_AANVRAGER", subject = 0)
     */
    function deleteAdmin(int $toernooiId, string $emailAdmin): Response
    {
        $this->logger->info("deleteAdmin, toernooi_id: " . $toernooiId . " email_admin: " . $emailAdmin);
        $response = new JsonResponse();

        $user = $this->getUser();
        if ($user->getEmail() == $emailAdmin) {
            $message = "De aanvrager kan niet worden verwijderd als toernooi administrator.";
            $response->setData(['status' => 'NOK', 'message' => $message]);
            return $response;
        }
        $toernooiAdmin = $this->toernooiAdminRepository->findOneBy(
            ['toernooi_id' => $toernooiId, 'admin_email' => $emailAdmin]
        );
        try {
            $this->entityManager->remove($toernooiAdmin);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->logger->error('deleteAdmin failed, error message: ' . $e->getMessage());
            $message = "Verwijderen admin met email '" . $emailAdmin . "' mislukt.";
            $response->setData(['status' => 'NOK', 'message' => $message]);
            return $response;
        }
        $response->setData(['status' => 'OK', 'email_admin' => $emailAdmin]);
        return $response;
    }

}