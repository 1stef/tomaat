<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Toernooi;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SharedServices {

    private SessionInterface $session;

    public function __construct(
        private readonly RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    /**
     * setToernooi() is een hulpfunctie zet de session variabelen voor het meegegeven toernooi
     */
    public function setToernooi(Toernooi $toernooi): void
    {
        $this->session->set("huidig_toernooi_id", $toernooi->getId());
        $this->session->set("huidig_toernooi", $toernooi->getToernooiNaam());
        $this->session->set("toernooi_status", $toernooi->getToernooiStatus());
    }


}

