<?php

namespace App\Controller;

use App\Message\FetchLatestApiData;
use App\Repository\PlayerRepository;
use App\Service\ChartService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractBaseController
{
    #[Route(path: '/', name: 'app_dashboard_summary')]
    public function summary(
        Request $request,
        PlayerRepository $playerRepository,
        MessageBusInterface $messageBus,
        ChartService $chartService
    ): Response {
        $form = $this->headerSearchForm($request);
        $playerName = $this->getPlayerNameFromRequest($request);
        $player = $playerRepository->findLatestByName($playerName);

        if (is_null($player)) {
            $messageBus->dispatch(new FetchLatestApiData($playerName));

            return $this->redirectToRoute('app_dashboard_summary');
        }

        return $this->render('summary.html.twig', [
            'chart' => $chartService->getQuestChart($player),
            'playerInfo' => $player,
            'form' => $form->createView()
        ]);
    }
}
