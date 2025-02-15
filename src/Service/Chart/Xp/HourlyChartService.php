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

readonly class HourlyChartService
{
    use CreateXpChartTrait;

    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private PlayerRepository $playerRepository
    ) {
    }

    /**
     * @param string $playerName
     * @param DateTimeImmutable $date
     * @param string $chartType
     * @return Chart
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function getTotalXpChart(
        string $playerName,
        DateTimeImmutable $date = new DateTimeImmutable(),
        string $chartType = Chart::TYPE_LINE,
    ): Chart {
        $hours = $this->playerRepository->findHourlyXpRateForTotalXp(
            $date->modify('00:00'),
            $date->modify('23:59'),
            $playerName
        );

        $data = [];
        $labels = [];
        $currentHour = $date->modify('00:00');
        $lastHour = $date->modify('23:59');

        while ($currentHour <= $lastHour) {
            $formattedHour = $currentHour->format('H:i');
            $data[$formattedHour] = $hours[(int)$currentHour->format('G')]['xp_increase'] ?? 0;
            $labels[] = $formattedHour;
            $currentHour = $currentHour->modify('+1 hour');
        }

        if ($chartType === 'stackedBar') {
            $chartType = Chart::TYPE_BAR;
        }

        return $this->createTotalXpChart($chartType, $labels, $data);
    }

    /**
     * Note: Will only return datasets of the skills that have at least one day with a positive xp difference.
     *
     * @param string $playerName
     * @param SkillEnum[] $skills
     * @param DateTimeImmutable $date
     * @param string $chartType
     * @return Chart
     * @throws Exception
     * @throws DateMalformedStringException
     */
    public function getXpPerSkillChart(
        string $playerName,
        array $skills,
        DateTimeImmutable $date = new DateTimeImmutable(),
        string $chartType = Chart::TYPE_LINE,
    ): Chart {
        $skillsData = [];

        foreach ($skills as $skill) {
            $xpData = $this->playerRepository->findHourlyXpRateForSkillAtDate(
                $date->modify('00:00'),
                $date->modify('23:59'),
                $playerName,
                $skill
            );

            $skillsData[] = [
                'skill' => $skill,
                'data' => $xpData
            ];
        }

        $dataSets = [];
        $labels = [];

        foreach ($skillsData as $skillsDataItem) {
            $data = [];
            $currentHour = $date->modify('00:00');
            $lastHour = $date->modify('23:00');

            while ($currentHour <= $lastHour) {
                foreach ($skillsDataItem['data'] as $hour) {
                    if ($hour['date'] === $currentHour->format('H:i')) {
                        $formattedDate = $currentHour->format('H:i');
                        $data[$formattedDate] = $hour['xp_difference'];
                        $labels[$formattedDate] = $formattedDate;
                    }
                }

                if (!array_key_exists($currentHour->format('H:i'), $data)) {
                    $formattedDate = $currentHour->format('H:i');
                    $data[$formattedDate] = 0;
                    $labels[$formattedDate] = $formattedDate;
                }

                $currentHour = $currentHour->modify('+1 hour');
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
