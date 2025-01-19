<?php

namespace App\Service;

use App\Entity\Player;
use App\Enum\SkillEnum;
use App\Repository\PlayerRepository;
use DateMalformedStringException;
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

    public function getMonthlyTotalXpChart(string $playerName, string $chartType = Chart::TYPE_LINE): Chart
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
                'scales' => [
                    'y' => ['grid' => ['color' => 'rgba(44, 61, 73, 0.3)']],
                    'x' => ['grid' => ['color' => 'rgba(44, 61, 73, 0.3)']]
                ],
                'color' => 'rgb(181,153,47)',
                'tension' => 0.3,
                'elements' => [
                    'point' => [
                        'radius' => 3
                    ]
                ],
                'plugins' => [
                    'zoom' => [
                        'zoom' => [
                            'wheel' => ['enabled' => true],
                            'pinch' => ['enabled' => true],
                            'mode' => 'xy',
                            'drag' => ['enabled' => true],
                        ],
                    ],
                ]
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
     * Note: Will only return datasets of the skills that have at least one day with a positive xp difference.
     *
     * @param string $playerName
     * @param SkillEnum[] $skills
     * @param string $chartType
     * @return Chart
     * @throws Exception
     * @throws DateMalformedStringException
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
            $xpData = $this->playerRepository
                ->findAllXpDifferencesBetweenDatesByNameGroupByDayAndSkill(
                    $dateTimes['minDate'],
                    $dateTimes['maxDate'],
                    $playerName,
                    $skill
                );

            $hasChanges = false;

            foreach ($xpData as $xpDate) {
                if ($xpDate['xp_difference'] > 0) {
                    $hasChanges = true;
                }
            }

            if ($hasChanges === false) {
                continue;
            }

            $skillsData[] = [
                'skill' => $skill,
                'data' => $xpData
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
                'scales' => [
                    'y' => ['grid' => ['color' => 'rgba(44, 61, 73, 0.3)']],
                    'x' => ['grid' => ['color' => 'rgba(44, 61, 73, 0.3)']]
                ],
                'color' => '#ffffff',
                'font-family' => 'Cinzel, sarif',
                'tension' => 0.19,
                'elements' => [
                    'point' => [
                        'radius' => 3
                    ]
                ],
                'plugins' => [
                    'zoom' => [
                        'zoom' => [
                            'wheel' => ['enabled' => true],
                            'pinch' => ['enabled' => true],
                            'mode' => 'xy',
                            'drag' => ['enabled' => true],
                        ],
                    ],
                ]
            ])
            ->setData([
                'labels' => array_values($labels),
                'datasets' => $dataSets
            ]);
    }
}
