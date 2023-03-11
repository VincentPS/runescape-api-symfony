<?php

namespace App\Controller;

use App\Enum\KnownPlayers;
use App\Message\FetchLatestApiData;
use App\Repository\PlayerRepository;
use App\Service\RsApiService;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    #[Route(path: '/', name: 'app_dashboard_summary')]
    public function summary(
        ChartBuilderInterface $chartBuilder,
        Request $request,
        PlayerRepository $playerRepository,
        MessageBusInterface $messageBus
    ): Response {
        $form = $this->headerSearchForm($request);

        $playerName = $form->getData()['playerName'] ?? $request->get('playerName') ?: KnownPlayers::VincentS->value;
        $playerInfo = $playerRepository->findLatestByName($playerName);

        if (is_null($playerInfo)) {
            $messageBus->dispatch(new FetchLatestApiData($playerName));

            return $this->redirectToRoute('app_dashboard_summary');
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT)
            ->setOptions(['color' => 'rgb(255, 255, 255)'])
            ->setData([
                'labels' => ['Completed', 'In Progress', 'Not Started'],
                'datasets' => [
                    [
                        'backgroundColor' => ['rgb(225,187,52)', 'rgb(52,189,209)', 'rgb(197,32,55)'],
                        'borderColor' => 'rgb(0, 0, 0)',
                        'data' => [
                            $playerInfo->getQuestsCompleted(),
                            $playerInfo->getQuestsStarted(),
                            $playerInfo->getQuestsNotStarted()
                        ]
                    ]
                ]
            ]);

        $messageBus->dispatch(new FetchLatestApiData($playerName));

        return $this->render('summary.html.twig', [
            'chart' => $chart,
            'playerInfo' => $playerInfo,
            'form' => $form->createView()
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/levels/today', name: 'app_dashboard_levels')]
    public function levels(
        ChartBuilderInterface $chartBuilder,
        RsApiService $rsApi,
        Request $request,
        PlayerRepository $playerRepository
    ): Response {
        $form = $this->headerSearchForm($request);

        $playerName = $form->getData()['playerName'] ?? $request->get('playerName') ?: KnownPlayers::VincentS->value;

        $timezone = date_default_timezone_get();
        $dateTime = new DateTimeImmutable(timezone: new DateTimeZone($timezone));

        $dataPoints = $playerRepository->findAllUniqueTotalXpBetweenByName(
            $dateTime->setTime(0, 0),
            $dateTime->setTime(23, 59, 59),
            $playerName
        );

        $data = [];
        $labels = [];

        foreach ($dataPoints as $dataPoint) {
            if (!in_array($dataPoint->getTotalXp(), $data)) {
                $hour = intval($dataPoint->getCreatedAt()->format('H'));
                $minute = intval($dataPoint->getCreatedAt()->format('i'));
                $index = $hour . ':' . str_pad($minute, 2, '0', STR_PAD_LEFT);
                $data[$index] = $dataPoint->getTotalXp();
                $labels[$index] = $index;
            }
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE)
            ->setOptions([
                'color' => 'rgb(255,99,132)',
            ])
            ->setData([
                'labels' => array_values($labels),
                'datasets' => [
                    [
                        'label' => 'Total XP',
                        'backgroundColor' => 'rgb(255,99,132)',
                        'borderColor' => 'rgb(255,99,132)',
                        'data' => $data
                    ]
                ]
            ]);

        return $this->render('levels.html.twig', [
            'chart' => $chart,
            'form' => $form->createView()
        ]);
    }

    /**
     * @throws GuzzleException
     */
    #[Route(path: '/activity', name: 'app_dashboard_activity')]
    public function activity(Request $request, RsApiService $rsApi): Response
    {
        $form = $this->headerSearchForm($request);

        $playerName = $form->getData()['playerName'] ?? $request->get('playerName') ?: KnownPlayers::VincentS->value;
        $playerInfo = $rsApi->getProfile($playerName, 20, false);

        return $this->render('activity.html.twig', [
            'activities' => $playerInfo->getActivities(),
            'form' => $form->createView()
        ]);
    }

    private function headerSearchForm(Request $request): FormInterface
    {
        $form = $this->formFactory->createNamedBuilder(name: 'search_form', options: [
            'attr' => [
                'class' => 'row-cols-lg-auto g-3 align-items-center d-flex'
            ]
        ])
            ->add('playerName', TextType::class, [
                'attr' => [
                    'class' => 'player-name-search-box form-control',
                    'placeholder' => 'Search for a player'
                ],
                'label' => false,
            ])
            ->add('search', SubmitType::class, [
                'label' => '<i class="fa fa-search"></i>',
                'attr' => [
                    'class' => 'player-name-search-button'
                ],
                'label_html' => true,
            ])
            ->getForm();

        if ($request->request->get('playerName')) {
            $form->setData(['playerName' => $request->request->get('playerName')]);
        }

        $form->handleRequest($request);

        return $form;
    }
}
