<?php

namespace App\Service;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use DateTimeImmutable;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ChartService
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly PlayerRepository $playerRepository
    ) {
    }

    public function getQuestChart(Player $player): ?Chart
    {
        return $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT)
            ->setOptions(['color' => 'rgb(255, 255, 255)'])
            ->setData([
                'labels' => ['Completed', 'In Progress', 'Not Started'],
                'datasets' => [
                    [
                        'backgroundColor' => ['rgb(225,187,52)', 'rgb(52,189,209)', 'rgb(197,32,55)'],
                        'borderColor' => 'rgb(0, 0, 0)',
                        'data' => [
                            $player->getQuestsCompleted(),
                            $player->getQuestsStarted(),
                            $player->getQuestsNotStarted()
                        ]
                    ]
                ]
            ]);
    }

    public function getMonthlyTotalXpChart(string $playerName): Chart
    {
        try {
            $dateTimes = $this->playerRepository->findFirstAndLastDateTimeByName($playerName);
        } catch (NoResultException | NonUniqueResultException) {
            $dateTimes = null;
        }

        if (is_null($dateTimes)) {
            $dateTimes = [
                'minDate' => new DateTimeImmutable(),
                'maxDate' => new DateTimeImmutable()
            ];
        }

        $days = $this->playerRepository->findAllUniqueTotalXpBetweenDatesByNameGroupByDay(
            $dateTimes['minDate'],
            $dateTimes['maxDate'],
            $playerName
        );

        $totalXpDiff = [];
        $averageXpDiffByDay = [];

        foreach ($days as $day => $records) {
            $totalXpDiff[$day] = end($records) - reset($records);
        }

        foreach ($totalXpDiff as $day => $totalXpDiffValue) {
            $averageXpDiffByDay[$day] = round($totalXpDiffValue / count($totalXpDiff));
        }

        $startDate = new DateTimeImmutable('first day of this month');
        $endDate = new DateTimeImmutable('last day of this month');

        $data = [];
        $labels = [];

        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $date = $currentDate->format('Y-m-d');
            $data[$date] = $totalXpDiff[$date] ?? 0;
            $labels[$date] = $date;
            $currentDate = $currentDate->modify('+1 day');
        }

        return $this->chartBuilder->createChart(Chart::TYPE_BAR)
            ->setOptions([
                'color' => 'rgb(181,153,47)',
                'elements' => [
                    'point' => [
                        'radius' => 0
                    ]
                ]
            ])
            ->setData([
                'labels' => array_values($labels),
                'datasets' => [
                    [
                        'label' => 'Average Total XP Diff',
                        'type' => 'line',
                        'fill' => true,
                        'backgroundColor' => 'rgba(95,37,37, 0.7)',
                        'borderColor' => 'rgba(95,37,37, 0.7)',
                        'borderWidth' => 1,
                        'tension' => 0.5,
                        'data' => $averageXpDiffByDay,
                    ],
                    [
                        'label' => 'Total XP',
                        'backgroundColor' => 'rgb(181,153,47)',
                        'borderColor' => 'rgb(181,153,47)',
                        'data' => $data
                    ],
                ]
            ]);
    }
}
