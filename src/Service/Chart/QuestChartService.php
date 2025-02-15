<?php

namespace App\Service\Chart;

use App\Entity\Player;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

readonly class QuestChartService
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder
    ) {
    }

    public function getChart(Player $player): ?Chart
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
}
