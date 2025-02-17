<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\InschrijvingRepository;
use App\Repository\ToernooiRepository;
use App\Service\PlanningService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class PlanningController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly PlanningService $planningService,
        private readonly ToernooiRepository $toernooiRepository,
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * @Route("/set_rankings", name="set_rankings")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("PLANNEN", subject = 0))
     */
    public function setEffectieveRankings(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            return $this->redirectToRoute('admin_toernooien');
        }

        $this->planningService->setEffectieveRankings($toernooiId);

        $this->addFlash('feedback', 'Effectieve enkel- en dubbel-rankings gezet');
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/maak_combinaties", name="maak_combinaties")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("PLANNEN", subject = 0))
     */
    public function maakCombinaties(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $this->logger->info("maakCombinaties, huidig_toernooi_id: " . $toernooiId);
        if (!$toernooiId) {
            return $this->redirectToRoute('admin_toernooien');
        }
        $this->planningService->makeAllCategoriesCombinations($toernooiId);

        $this->addFlash('feedback', 'Combinaties gemaakt: wedstrijden moeten opnieuw gepland worden');
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/verhinderde_tijdsloten", name="verhinderde_tijdsloten")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("PLANNEN", subject = 0))
     * Deze route is alleen voor testen: de functie wordt aangeroepen in Planwedstrijden
     */
    public function maakVerhinderdeTijdsloten(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $this->logger->info("maakVerhinderdeTijdsloten, huidig_toernooi_id: " . $toernooiId);
        if (!$toernooiId) {
            return $this->redirectToRoute('admin_toernooien');
        }
        $tijdsloten = $this->planningService->maakVerhinderdeTijdsloten($toernooiId);

        $this->addFlash('feedback', 'Verhinderde tijdsloten gemaakt');
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/voorbereiden_plannen", name="voorbereiden_plannen")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("PLANNEN", subject = 0))
     */
    public function voorbereidenPlannen(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $this->logger->info("voorbereidenPlannen, huidig_toernooi_id: " . $toernooiId);
        if (!$toernooiId) {
            return $this->redirectToRoute('admin_toernooien');
        }

        $this->planningService->makeAllCategoriesCombinations($toernooiId);

        $this->addFlash('feedback', 'Combinaties (her)berekend: wedstrijden moeten opnieuw gepland worden');
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/plan_wedstrijden", name="plan_wedstrijden")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("PLANNEN", subject = 0))
     */
    public function planWedstrijden(ToernooiRepository $toernooiRepository): Response
    {
        // het plannen kan wel even duren; zet de time limit op 2 minuten.
        set_time_limit(120);
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $this->logger->info("planWedstrijden, huidig_toernooi_id: " . $toernooiId);
        if (!$toernooiId) {
            return $this->redirectToRoute('admin_toernooien');
        }
        $toernooi = $toernooiRepository->find($toernooiId);
        $this->planningService->planWedstrijden($toernooiId, $toernooi);

        // TODO: presenteer de geplande wedstrijden
        $this->addFlash('feedback', 'Wedstrijden gepland');
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/communiceer_wedstrijden", name="communiceer_wedstrijden")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("PLANNEN", subject = 0))
     */
    public function communiceerWedstrijden(Request $request): Response
    {
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi_naam = $this->requestStack->getSession()->get('huidig_toernooi');
        $toernooi = $this->toernooiRepository->find($toernooiId);
        $blokkeer_mails = ($toernooi->getBlokkeerMails() == 1);

        $inschrijvers = $this->inschrijvingRepository->getAlleInschrijvers($toernooiId);
        $aantal_mails = count($inschrijvers);

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if ($this->isCsrfTokenValid('mail_alle_wedstrijden', $request->request->get('token'))) {
                // Het confirm_mail_wedstrijden form is getoond en gesubmit.
                // We gaan nu alle mails met geplande wedstrijden versturen.
                if ($blokkeer_mails) {
                    $this->addFlash('feedback', "Geen mails verstuurd, dit toernooi is aangemerkt als test-toernooi");
                    return $this->redirectToRoute('home');
                }
                for ($i = 0; $i < count($inschrijvers); $i++) {
                    $email = (new TemplatedEmail())
                        ->from(new Address('noreply@toernooiopmaat.nl', 'Uw Toernooi Op Maat wedstrijden'))
                        ->to(new Address($inschrijvers[$i]['email']))
                        ->subject('Geplande Toernooi Op Maat wedstrijden voor ' . $inschrijvers[$i]['naam'])
                        ->htmlTemplate('toernooi/communiceer_wedstrijden.html.twig')
                        ->context([
                            'naam' => $inschrijvers[$i]['naam'],
                            'toernooi_id' => $toernooiId,
                            'toernooi_naam' => $toernooi_naam,
                        ]);
                        if (!empty($_ENV['SEND_MAILS']) && $_ENV['SEND_MAILS'] == "off") {
                        // stuur geen mails in deze omgeving: log de inhoud van de mail
                        $this->logger->info(
                            "communiceerWedstrijden op dev omgeving, mail gestuurd aan: " . $inschrijvers[$i]['email']
                        );
                        // render de eerste mail als kale html en stop
                        return $this->render(
                            'toernooi/communiceer_wedstrijden.html.twig',
                            [
                                'naam' => $inschrijvers[$i]['naam'],
                                'toernooi_id' => $toernooiId,
                                'toernooi_naam' => $toernooi_naam,
                            ]
                        );
                    } else {
                        try {
                            $this->mailer->send($email);
                        } catch (TransportExceptionInterface $e) {
                            // some error prevented the email sending; display an
                            // error message or try to resend the message
                            $this->logger->error(
                                "aanvraagToernooi: fout bij verzenden geplande wedstrijden mail: " . $e->getMessage()
                            );
                        }
                        $this->logger->info(
                            "communiceerWedstrijden: mail verzonden aan: " . $inschrijvers[$i]['email']
                        );
                    }
                }
                $this->addFlash('feedback', $aantal_mails . " mails met geplande wedstrijden verstuurd");
                return $this->redirectToRoute('home');
            } else {
                // TODO: log CSRF security breach
                return $this->redirectToRoute('home');
            }
        } else {
            // De menu-optie "communiceer wedstrijden" is aangeroepen
            // Toon eerst het confirm_mail_wedstrijden template:
            return $this->render(
                'toernooi/confirm_mail_wedstrijden.html.twig',
                [
                    'aantal_mails' => $aantal_mails,
                    'toernooi_naam' => $this->requestStack->getSession()->get('huidig_toernooi'),
                    'blokkeer_mails' => $blokkeer_mails
                ]
            );
        }
    }

    /**
     * @Route("/start_toernooi", name="start_toernooi")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("PLANNEN", subject = 0))
     */
    public function startToernooi(ToernooiRepository $toernooiRepository): Response
    {
        $toernooiId = $this->requestStack->getSession()->get("huidig_toernooi_id");
        $toernooi = $toernooiRepository->find($toernooiId);
        $toernooi->setToernooiStatus("spelen");
        $toernooi->setSpeeldag(0);
        $this->entityManager->persist($toernooi);
        $this->entityManager->flush();
        $this->requestStack->getSession()->set("toernooi_status", "spelen");
        $this->addFlash("feedback", "De wedstrijden kunnen beginnen! Toernooistatus: spelen");
        return $this->redirectToRoute('home');
    }

}
