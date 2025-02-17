<?php
declare(strict_types = 1);

namespace App\Service;

use App\Repository\CombinationsRepository;
use App\Repository\InschrijvingRepository;

class StatistiekenService
{
    public function __construct(
        private readonly InschrijvingRepository $inschrijvingRepository,
        private readonly CombinationsRepository $combinationsRepository
    )
    {
    }

    public function statInschrijvingenPerCategorie(int $toernooiId): array
    {
        $inschrijvingCategorieTotalen = $this->inschrijvingRepository->getInschrijvingCategorieTotalen($toernooiId);
        $categorieGeplandTotalen = $this->combinationsRepository->getCategorieGeplandTotalen($toernooiId);

        // Voeg gepland_aantal toe aan $inschrijvingCategorieTotalen, indien aanwezig in $categorieGeplandTotalen
        // (mogelijk zijn niet voor iedere categorie wedstrijden gepland)
        foreach ($inschrijvingCategorieTotalen as $key => $categorieTotalen) {
            foreach ($categorieGeplandTotalen as $categorieGepland) {
                $geplandAantal =
                    ($categorieTotalen['cat'] == $categorieGepland['cat'] ? $categorieGepland['gepland_aantal'] : 0);
                $inschrijvingCategorieTotalen[$key] += ['gepland_aantal' => $geplandAantal];
           }
        }

        // Voeg een Totaal-rij toe aan $inschrijvingCategorieTotalen:
        $sum_inschrijvingen = array_sum(array_column($inschrijvingCategorieTotalen, 'aantal_inschrijvingen'));
        $sum_gevraagd = array_sum(array_column($inschrijvingCategorieTotalen, 'gevraagd_aantal'));
        $sum_gepland = array_sum(array_column($categorieGeplandTotalen, 'gepland_aantal'));

        $inschrijvingCategorieTotalen[] =
            [
                'cat' => 'Totaal',
                'aantal_inschrijvingen' => $sum_inschrijvingen,
                'gevraagd_aantal' => $sum_gevraagd,
                'gepland_aantal' => $sum_gepland
            ];

        return $inschrijvingCategorieTotalen;
    }
}