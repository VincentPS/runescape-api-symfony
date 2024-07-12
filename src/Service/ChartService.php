<?php

namespace App\Service;

use App\Entity\Player;
use App\Enum\SkillEnum;
use App\Repository\PlayerRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

readonly class ChartService
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private PlayerRepository $playerRepository
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

    public function getMonthlyTotalXpChart(string $playerName, string $chartType = Chart::TYPE_BAR): Chart
    {
        try {
            $dateTimes = $this->playerRepository->findFirstAndLastDateTimeByName($playerName);
        } catch (NoResultException | NonUniqueResultException) {
            $dateTimes = null;
        }

        $startDate = new DateTimeImmutable('-1 month');
        $endDate = new DateTimeImmutable();

        if (is_null($dateTimes)) {
            $dateTimes = [
                'minDate' => $startDate,
                'maxDate' => $endDate
            ];
        }

        $days = $this->playerRepository->findAllUniqueTotalXpBetweenDatesByNameGroupByDay(
            $dateTimes['minDate'],
            $dateTimes['maxDate'],
            $playerName
        );

        $data = [];
        $labels = [];

        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $date = $currentDate->format('Y-m-d');
            $data[$date] = $days[$date]['xp_increase'] ?? 0;
            $labels[$date] = $date;
            $currentDate = $currentDate->modify('+1 day');
        }

        return $this->chartBuilder->createChart($chartType)
            ->setOptions([
                'color' => 'rgb(181,153,47)',
                'elements' => [
                    'point' => [
                        'radius' => 0
                    ]
                ],
                'plugins' => [
                    'zoom' => [
                        'zoom' => [
                            'wheel' => ['enabled' => true],
                            'pinch' => ['enabled' => true],
                            'mode' => 'xy',
                            'speed' => 100
                        ],
                    ],
                ],
            ])
            ->setData([
                'labels' => array_values($labels),
                'datasets' => [
                    [
                        'label' => 'Total XP',
                        'backgroundColor' => 'rgb(181,153,47)',
                        'borderColor' => 'rgb(181,153,47)',
                        'data' => $data
                    ],
                ]
            ]);
    }

    /**
     * @param string $playerName
     * @param SkillEnum[] $skills
     * @param string $chartType
     * @return Chart
     * @throws Exception
     */
    public function getMonthlyTotalXpChartBySkills(
        string $playerName,
        array $skills,
        string $chartType = Chart::TYPE_BAR
    ): Chart {
        try {
            $dateTimes = $this->playerRepository->findFirstAndLastDateTimeByName($playerName);
        } catch (NoResultException | NonUniqueResultException) {
            $dateTimes = null;
        }

        $startDate = new DateTimeImmutable('-1 month');
        $endDate = new DateTimeImmutable();

        if (is_null($dateTimes)) {
            $dateTimes = [
                'minDate' => $startDate,
                'maxDate' => $endDate
            ];
        }

        $skillsData = [];

        foreach ($skills as $skill) {
            $skillsData[] = [
                'skill' => $skill,
                'data' => $this->playerRepository
                    ->findAllXpDifferencesBetweenDatesByNameGroupByDayAndSkill(
                        $dateTimes['minDate'],
                        $dateTimes['maxDate'],
                        $playerName,
                        $skill
                    )
            ];
        }

        $dataSets = [];
        $labels = [];

        foreach ($skillsData as $skillsDataItem) {
            $data = [];
            $currentDate = $startDate;

            while ($currentDate <= $endDate) {
                foreach ($skillsDataItem['data'] as $day) {
                    if ($day['date'] === $currentDate->format('Y-m-d')) {
                        $date = $currentDate->format('Y-m-d');
                        $data[$date] = $day['xp_difference'];
                        $labels[$date] = $date;
                    }
                }

                if (!array_key_exists($currentDate->format('Y-m-d'), $data)) {
                    $date = $currentDate->format('Y-m-d');
                    $data[$date] = 0;
                    $labels[$date] = $date;
                }

                $currentDate = $currentDate->modify('+1 day');
            }

            $dataSets[] = [
                'label' => $skillsDataItem['skill']->name . ' XP',
                'backgroundColor' => $skillsDataItem['skill']->graphColor(),
                'borderColor' => $skillsDataItem['skill']->graphColor(),
                'data' => array_values($data)
            ];
        }

        return $this->chartBuilder->createChart($chartType)
            ->setOptions([
                'color' => '#ffffff',
                'font-family' => 'Cinzel, sarif',
                'elements' => [
                    'point' => [
                        'radius' => 0
                    ]
                ]
            ])
            ->setData([
                'labels' => array_values($labels),
                'datasets' => $dataSets
            ]);
    }
}
