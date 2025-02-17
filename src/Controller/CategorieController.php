<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

class CategorieController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CategorieRepository $categorieRepository
    ) {
    }

    /**
     * @Route("/categorieen", name="categorieen")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("VOORBEREIDEN", subject = 0))
     */
    public function categorieen(Request $request): Response
    {
        $toernooiId = $request->getSession()->get('huidig_toernooi_id');
        $toernooiNaam = $request->getSession()->get('huidig_toernooi');
        $categorieen = $this->categorieRepository->findBy(['toernooi_id' => $toernooiId]);
        return $this->render('toernooi/modal_categorieen.html.twig', [
            'toernooi_id' => $toernooiId,
            'toernooi_naam' => $toernooiNaam,
            'categorieen' => $categorieen,
        ]);
    }

    /**
     * @Route("/add_categorie/{cat}/{type}")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("VOORBEREIDEN", subject = 0))
     */
    public function addCategorie(Request $request, $cat, $type): JsonResponse
    {
        $toernooiId = $request->getSession()->get('huidig_toernooi_id');
        $categorie = new Categorie();
        try {
            $this->categorieRepository->add($categorie, $toernooiId, $cat, $type);
        } catch (UniqueConstraintViolationException $e) {
            $this->logger->info('addCategorie failed (bestaat al), message: ' . $e->getMessage());
            return $this->json(
                array('status' => 'ERROR', 'message' => "Toevoegen categorie " . $cat . " mislukt, bestaat al")
            );
        } catch (\Exception $e) {
            $this->logger->info('addCategorie failed, message: ' . $e->getMessage());
            return $this->json(
                array(
                    'status' => 'ERROR',
                    'message' => "Toevoegen categorie " . $cat . " mislukt, probeer het later nog eens"
                )
            );
        }
        return $this->json(array('status' => 'OK'));
    }

    /**
     * @Route("/delete_categorie/{cat}")
     * @IsGranted("ROLE_ADMIN_TOERNOOI", subject = 0))
     * @IsGranted("VOORBEREIDEN", subject = 0))
     */
    public function deleteCategorie(Request $request, $cat): JsonResponse
    {
        $toernooiId = $request->getSession()->get('huidig_toernooi_id');
        $categorie = $this->categorieRepository->findOneBy(['toernooi_id' => $toernooiId, 'cat' => $cat]);
        try {
            $this->categorieRepository->remove($categorie);
        } catch (\Exception $e) {
            $this->logger->info('deleteCategorie failed, message: ' . $e->getMessage());
            return $this->json(array('status' => 'ERROR', 'message' => "Verwijderen categorie " . $cat . " mislukt"));
        }
        return $this->json(array('status' => 'OK'));
    }

}