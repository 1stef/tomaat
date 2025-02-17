<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TijdslotRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class TijdslotenController extends AbstractController
{

    public function __construct(
        private RequestStack $requestStack,
        private LoggerInterface $logger,
        private TijdslotRepository $tijdslotRepository
    ) {
    }

    /**
     * @Route("/toon_tijdsloten", name="toon_tijdsloten")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     */
    public function toonTijdsloten(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $toernooi_naam = $this->requestStack->getSession()->get('huidig_toernooi');
        $this->logger->info("toonTijdsloten, huidig_toernooi_id: " . $toernooi_id);
        if (!$toernooi_id) {
            return $this->redirectToRoute('admin_toernooien');
        }
        $tijdsloten = $this->tijdslotRepository->toonTijdsloten($toernooi_id);
        $totaal = 0;
        for ($i = 0; $i < count($tijdsloten); $i++) {
            $totaal += $tijdsloten[$i]['aantal_tijdsloten'];
        }

        return $this->render('toernooi/tijdsloten.html.twig', [
            'toernooi_naam' => $toernooi_naam,
            'tijdsloten' => json_encode($tijdsloten),
            'totaal' => $totaal,
        ]);
    }

}
