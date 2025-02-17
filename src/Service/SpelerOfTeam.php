<?php
declare(strict_types = 1);

namespace App\Service;

class SpelerOfTeam
{
    private int $id;
    private int $aantal;
    private int $aantal_gepland = 0;
    private array$opponents = [];

    public function __construct(int $spelerOfTeamId, int $aantal)
    {
        $this->id = $spelerOfTeamId;
        $this->aantal = $aantal;
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function getAantal(): int
    {
        return $this->aantal;
    }

    public function setAantal(int $aantal): SpelerOfTeam
    {
        $this->aantal = $aantal;
        return $this;
    }

    public function getAantalGepland(): int
    {
        return $this->aantal_gepland;
    }

    public function setAantalGepland(int $aantal_gepland): SpelerOfTeam
    {
        $this->aantal_gepland = $aantal_gepland;
        return $this;
    }

    public function incrementAantalGepland(): SpelerOfTeam
    {
        $this->aantal_gepland++;
        return $this;
    }

    public function getOpponents(): array
    {
        return $this->opponents;
    }

    public function setOpponents(array $opponents): SpelerOfTeam
    {
        $this->opponents = $opponents;
        return $this;
    }

    public function addOpponent(int $opponent): SpelerOfTeam
    {
        $this->opponents[] = $opponent;
        return $this;
    }
}
