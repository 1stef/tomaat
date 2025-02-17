<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Deelnemer;
use App\Form\DeelnemerAccountType;
use App\Repository\DeelnemerRepository;
use Psr\Log\LoggerInterface;

/**
 * @Route("/deelnemer")
 * Je moet ingelogd zijn om je deelnemer account gegevens te kunnen zien en wijzigen
 * @IsGranted("ROLE_USER")
 */
class DeelnemerAccountController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @Route("/account/{actie}", methods="GET|POST", name="deelnemer_account")
     */

    public function editAccount(Request $request, DeelnemerRepository $deelnemerRepository, $actie): Response
    {
        $user = $this->getUser();
        $this->logger->info("editAccount, this->getUser(): " . json_encode($user));
        $this->logger->info("editAccount, request: " . json_encode($request));

        $new_account = false;
        $deelnemer = $deelnemerRepository->find($user->getId());
        if (!$deelnemer) {
            $deelnemer = new Deelnemer($user->getId());
            $new_account = true;
        }
        $this->logger->info("editAccount, deelnemer: " . print_r($deelnemer, true));

        $form = $this->createForm(DeelnemerAccountType::class, $deelnemer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $deelnemer = $form->getData();

            $this->entityManager->persist($deelnemer);
            $this->entityManager->flush();

            if ($actie == "schrijfIn") {
                return $this->redirectToRoute('schrijfIn');
            } else {
                return $this->redirectToRoute('home');
            }
        }

        if ($new_account) {
            return $this->render('deelnemer_account/new_account.html.twig', [
                'accountForm' => $form->createView(),
            ]);
        } else {
            return $this->render('deelnemer_account/account.html.twig', [
                'accountForm' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/update", methods="GET|POST", name="update_account")
     */

    public function updateAccount(Request $request, DeelnemerRepository $deelnemerRepository): Response
    {
        $user = $this->getUser();
        $this->logger->info("updateAccount, this->getUser(): " . json_encode($user));
        $this->logger->info("updateAccount, request: " . json_encode($request));

        $deelnemer = $deelnemerRepository->find($user->getId());
        if (!$deelnemer) {
            $deelnemer = new Deelnemer($user->getId());
        }
        $this->logger->info("updateAccount, deelnemer: " . print_r($deelnemer, true));

        $form = $this->createForm(DeelnemerAccountType::class, $deelnemer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $deelnemer = $form->getData();

            $this->entityManager->persist($deelnemer);
            $this->entityManager->flush();

            return $this->redirectToRoute('schrijfIn');
        }
        return $this->redirectToRoute('schrijfIn');
    }

}
