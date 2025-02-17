<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BerichtRepository;
use App\Repository\ToernooiRepository;
use App\Service\SharedServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class BerichtenController extends AbstractController
{
    public function __construct(
        private readonly BerichtRepository $berichtRepository,
        private readonly ToernooiRepository $toernooiRepository,
        private readonly SharedServices $sharedServices
    ) {
    }

    /**
     * @Route("/mijn_berichten", name = "mijn_berichten")
     * @IsGranted("ROLE_USER")
     */
    public function mijnBerichten(Request $request): Response
    {
        $toernooi_id = $request->getSession()->get('huidig_toernooi_id');
        // Haal de berichten voor deze user voor dit toernooi op,
        // mits met status "inschrijven", "plannen" en "spelen".
        // Als $toernooi_id == NULL, haal de berichten voor alle toernooien op:
        $ontvangen_berichten = $this->berichtRepository->getOntvangenBerichten($this->getUser()->getId(), $toernooi_id);
        $verzonden_berichten = $this->berichtRepository->getVerzondenBerichten($this->getUser()->getId(), $toernooi_id);
        return $this->render(
            'berichten/mijn_berichten.html.twig',
            ['ontvangen_berichten' => $ontvangen_berichten, 'verzonden_berichten' => $verzonden_berichten]
        );
    }


    /**
     * @Route("/toernooileiding_berichten", name = "toernooileiding_berichten")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     */
    public function toernooileidingBerichten(Request $request): Response
    {
        $toernooi_id = $request->getSession()->get('huidig_toernooi_id');
        // Haal de berichten voor de toernooileiding van dit toernooi op,
        $ontvangen_berichten = $this->berichtRepository->getOntvangenTLBerichten($toernooi_id);
        $verzonden_berichten = $this->berichtRepository->getVerzondenTLBerichten($toernooi_id);
        return $this->render(
            'berichten/toernooileiding_berichten.html.twig',
            ['ontvangen_berichten' => $ontvangen_berichten, 'verzonden_berichten' => $verzonden_berichten]
        );
    }

    /**
     * @Route("/toon_bericht/{bericht_id}", name = "toon_bericht")
     * @IsGranted("ROLE_USER")
     */
    public function toonBericht(int $bericht_id): Response
    {
        $berichtVerrijkt = $this->berichtRepository->getBerichtVerrijkt($bericht_id);
        if (!$berichtVerrijkt) {
            $this->addFlash("feedback", "wedstrijd wijziging is inmiddels verwijderd");
            return $this->redirectToRoute("home");
        }
        // Check of de ingelogde user ontvanger of afzender is
        if (!in_array(
                $this->getUser()->getId(),
                [
                    $berichtVerrijkt['afzender'],
                    $berichtVerrijkt['user1A'],
                    $berichtVerrijkt['user1B'],
                    $berichtVerrijkt['user2A'],
                    $berichtVerrijkt['user2B']
                ]
            )
            && $berichtVerrijkt['alle_deelnemers']
        ) {
            // Gebruiker heeft url gemanipuleerd
            return $this->redirectToRoute('access_denied');
            // TODO: log security event
        } else {
            // Het toernooi waar een bericht over gaat, wordt eerst geselecteerd:
            if ($berichtVerrijkt['toernooi_id'] > 0) {
                $toernooi = $this->toernooiRepository->find($berichtVerrijkt['toernooi_id']);
                $this->sharedServices->setToernooi($toernooi);
            }
            switch ($berichtVerrijkt['berichttype']) {
                case "ww-verplaatsen":
                    return $this->render(
                        'berichten/bericht_verplaatsen.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-afzeggen":
                    return $this->render(
                        'berichten/bericht_afzeggen.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-ontv-afzeggen":
                    return $this->render(
                        'berichten/bericht_ontv_afzeggen.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-ontv-ander-tijdstip":
                    return $this->render(
                        'berichten/bericht_ontv_ander_tijdstip.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-ontv-bevestig-afzeggen":
                    return $this->render(
                        'berichten/bericht_ontv_bevestig_afzeggen.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-ontv-akkoord":
                    return $this->render(
                        'berichten/bericht_ontv_akkoord.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "toevoegen":
                    return $this->render(
                        'berichten/bericht_toevoegen.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-ontv-toevoegen-akkoord":
                    return $this->render(
                        'berichten/bericht_ontv_toevoegen_akkoord.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-ontv-toevoegen-ander-tijdstip":
                    return $this->render(
                        'berichten/bericht_ontv_toevoegen_ander_tijdstip.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                case "ww-ontv-toevoegen-niet-akkoord":
                    return $this->render(
                        'berichten/bericht_ontv_toevoegen_niet_akkoord.html.twig',
                        ['bericht_verrijkt' => $berichtVerrijkt]
                    );
                default:
                    $this->addFlash("feedback", "onbekend berichttype");
                    return $this->redirectToRoute("home");
            }
        }
    }
}