<?php
declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ToernooiAdminRepository")
 */
class ToernooiAdmin
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
   private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $toernooi_id;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private string $admin_email;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToernooiId(): ?int
    {
        return $this->toernooi_id;
    }

    public function setToernooiId(int $toernooi_id): self
    {
        $this->toernooi_id = $toernooi_id;
        return $this;
    }

    public function getAdminEmail(): ?string
    {
        return $this->admin_email;
    }

    public function setAdminEmail(string $admin_email): self
    {
        $this->admin_email = $admin_email;
        return $this;
    }
}
