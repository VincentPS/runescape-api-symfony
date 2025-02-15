<?php

namespace App\Trait;

use Symfony\UX\Chartjs\Model\Chart;

trait CreateXpChartTrait
{
    /**
     * @param string $chartType
     * @param array<mixed> $labels
     * @param array<mixed> $data
     * @return Chart
     */
    public function createTotalXpChart(
        string $chartType,
        array $labels,
        array $data
    ): Chart {
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
                        'label' => 'Total XP Gain',
                        'backgroundColor' => 'rgb(181,153,47)',
                        'borderColor' => 'rgb(181,153,47)',
                        'data' => array_values($data)
                    ],
                ]
            ]);
    }

    /**
     * @param string $chartType
     * @param array{y: array{grid: array{color: string}}, x: array{grid: array{color: string}}} $scales
     * @param string[] $labels
     * @param array<mixed>[] $dataSets
     * @return Chart
     */
    public function createXpPerSkillChart(
        string $chartType,
        array $scales,
        array $labels,
        array $dataSets
    ): Chart {
        return $this->chartBuilder->createChart($chartType)
            ->setOptions([
                'scales' => $scales,
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

    /**
     * @return array{y: array{grid: array{color: string}}, x: array{grid: array{color: string}}}
     */
    public function createScales(): array
    {
        return [
            'y' => [
                'grid' => [
                    'color' => 'rgba(44, 61, 73, 0.3)'
                ]
            ],
            'x' => [
                'grid' => [
                    'color' => 'rgba(44, 61, 73, 0.3)'
                ]
            ]
        ];
    }
}
