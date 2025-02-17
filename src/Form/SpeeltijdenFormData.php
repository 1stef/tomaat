<?php

declare(strict_types=1);

namespace App\Form;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use App\Service\MySQLDB;

class SpeeltijdenFormData
{
    /**
     * @Assert\Type("integer")
     */
    public int $toernooi_id;

    /**
     * speeltijden is een ArrayCollection van speeltijden per dag
     * @Assert\Valid()
     */
    public ArrayCollection $speeltijden;

    /**
     * Initialising after construction:
     */
    public function __construct(int $toernooi_id)
    {
        $this->toernooi_id = $toernooi_id;
        $this->speeltijden = new ArrayCollection();
    }

    public function getSpeeltijden(): ArrayCollection
    {
        return $this->speeltijden;
    }
}