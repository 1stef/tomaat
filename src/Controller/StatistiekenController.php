<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\InschrijvingRepository;
use App\Service\StatistiekenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class StatistiekenController extends AbstractController
{
    public function __construct(
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly RequestStack $requestStack,
        private readonly StatistiekenService $statistiekenService
    ) {
    }

    /**
     * @Route("statistieken/inschrijvingen", name="statistieken_inschrijvingen")
     */
    public function inschrijvingen_per_categorie(): Response
    {
        // Bepaal het huidige toernooi_id:
        $toernooiId = $this->requestStack->getSession()->get('huidig_toernooi_id');
        if (!$toernooiId) {
            $this->addFlash("feedback", "Kies eerst een toernooi");
            return $this->redirectToRoute('toernooien');
        }
        $inschrijvingenPerCat = $this->statistiekenService->statInschrijvingenPerCategorie($toernooiId);

        return $this->render('statistieken/inschrijvingen.html.twig', [
            'inschrijvingen_per_cat' => json_encode($inschrijvingenPerCat)
        ]);
    }

    /**
     * @Route("/minder_gepland", name="minder_gepland")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("SPELEN", subject = 0))
     */
    public function ToonMinderGepland(): Response
    {
        $toernooi_id = $this->requestStack->getSession()->get('huidig_toernooi_id');
        $incomplete_inschrijvingen = $this->inschrijvingRepository->getIncompleteInschrijvingen($toernooi_id);

        // toon het overzicht van reserveringen
        return $this->render('statistieken/minder_gepland.html.twig', [
            'incomplete_inschrijvingen' => $incomplete_inschrijvingen,
        ]);
    }

}
