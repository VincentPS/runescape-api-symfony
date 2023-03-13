<?php

namespace App\Controller;

use App\Enum\KnownPlayers;
use App\Message\FetchLatestApiData;
use App\Repository\PlayerRepository;
use App\Service\ChartService;
use App\Service\CorrectDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    #[Route(path: '/', name: 'app_dashboard_summary')]
    public function summary(
        Request $request,
        PlayerRepository $playerRepository,
        MessageBusInterface $messageBus,
        ChartService $chartService,
        CorrectDataService $correctDataService
    ): Response {
        $form = $this->headerSearchForm($request);

        $playerName = $form->getData()['playerName'] ?? $request->get('playerName') ?: KnownPlayers::VincentS->value;
        $player = $playerRepository->findLatestByName($playerName);

        if (is_null($player)) {
            $messageBus->dispatch(new FetchLatestApiData($playerName));

            return $this->redirectToRoute('app_dashboard_summary');
        }

        $correctDataService->verifyApiDataIntegrity($player);

        return $this->render('summary.html.twig', [
            'chart' => $chartService->getQuestChart($player),
            'playerInfo' => $player,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/levels/today', name: 'app_dashboard_levels')]
    public function levels(Request $request, ChartService $chartService): Response
    {
        $form = $this->headerSearchForm($request);
        $playerName = $form->getData()['playerName'] ?? $request->get('playerName') ?: KnownPlayers::VincentS->value;

        return $this->render('levels.html.twig', [
            'chart' => $chartService->getMonthlyTotalXpChart($playerName),
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/activity', name: 'app_dashboard_activity')]
    public function activity(
        Request $request,
        PlayerRepository $playerRepository,
        MessageBusInterface $messageBus
    ): Response {
        $form = $this->headerSearchForm($request);

        $playerName = $form->getData()['playerName'] ?? $request->get('playerName') ?: KnownPlayers::VincentS->value;
        $player = $playerRepository->findLatestByName($playerName);

        if (is_null($player)) {
            $messageBus->dispatch(new FetchLatestApiData($playerName));

            return $this->redirectToRoute('app_dashboard_summary');
        }

        return $this->render('activity.html.twig', [
            'activities' => $player->getActivities(),
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
