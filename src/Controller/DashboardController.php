<?php

namespace App\Controller;

use App\Exception\PlayerDataHttpException;
use App\Service\RsApi;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractController
{
    private const PLAYER_NAME = 'VincentS';

    /**
     * @param ChartBuilderInterface $chartBuilder
     * @param RsApi $rsApi
     * @return Response
     * @throws GuzzleException
     */
    #[Route(path: '/', name: 'index')]
    public function chartjs(ChartBuilderInterface $chartBuilder, RsApi $rsApi): Response
    {

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
//        dd($rsApi->getProfile(self::PLAYER_NAME));

        $chart->setData([
            'labels' => ['Completed', 'In Progress', 'Not Started'],
            'datasets' => [
                [
                    'backgroundColor' => [
                        'rgb(225,187,52)',
                        'rgb(52,189,209)',
                        'rgb(197,32,55)',
                    ],
                    'borderColor' => '',
                    'data' => [264, 3, 54]
                ]
            ],
        ])->setOptions([
            'color' => 'rgb(255, 255, 255)'
        ]);

        $playerInfo = $rsApi->getProfile(self::PLAYER_NAME);

        return $this->render('index.html.twig', [
            'chart' => $chart,
            'playerInfo' => $playerInfo,
        ]);
    }
}