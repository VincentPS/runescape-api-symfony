<?php

namespace App\Service\Chart\Xp;

use App\Enum\SkillEnum;
use App\Repository\PlayerRepository;
use App\Trait\CreateXpChartTrait;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

readonly class DailyChartService
{
    use CreateXpChartTrait;

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private PlayerRepository $playerRepository
    ) {
    }

    /**
     * @param string $playerName
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @param string $chartType
     * @return Chart
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function getTotalXpChart(
        string $playerName,
        DateTimeImmutable $startDate = new DateTimeImmutable('-1 month'),
        DateTimeImmutable $endDate = new DateTimeImmutable(),
        string $chartType = Chart::TYPE_BAR,
    ): Chart {
        $days = $this->playerRepository->findDailyXpRateForTotalXp(
            $startDate,
            $endDate,
            $playerName
        );

        $data = [];
        $labels = [];
        $currentDate = $startDate;
        $count = 0;

        while ($currentDate <= $endDate) {
            $date = $currentDate->format('Y-m-d');
            $data[$date] = $days[$count]['total_xp_gain'] ?? 0;
            $labels[$date] = $date;
            $currentDate = $currentDate->modify('+1 day');
            $count++;
        }

        if ($chartType === 'stackedBar') {
            $chartType = Chart::TYPE_BAR;
        }

        return $this->createTotalXpChart($chartType, $labels, $data);
    }

    /**
     * @param string $playerName
     * @param SkillEnum[] $skills
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @param string $chartType
     * @return Chart
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function getXpPerSkillChart(
        string $playerName,
        array $skills,
        DateTimeImmutable $startDate = new DateTimeImmutable('-1 month'),
        DateTimeImmutable $endDate = new DateTimeImmutable(),
        string $chartType = Chart::TYPE_BAR,
    ): Chart {
        $skillsData = [];

        foreach ($skills as $skill) {
            $xpData = $this->playerRepository->findDailyXpRateForSkillAtDate($startDate, $endDate, $playerName, $skill);

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

        $scales = $this->createScales();

        if ($chartType === 'stackedBar') {
            $chartType = Chart::TYPE_BAR;
            $scales['y']['stacked'] = true;
            $scales['x']['stacked'] = true;
        }

        return $this->createXpPerSkillChart($chartType, $scales, $labels, $dataSets);
    }
}
