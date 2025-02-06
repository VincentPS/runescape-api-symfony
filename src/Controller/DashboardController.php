<?php

namespace App\Controller;

use App\Message\FetchLatestApiData;
use App\Repository\PlayerRepository;
use App\Service\ChartService;
use App\Service\DoubleXpService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractBaseController
{
    #[Route(path: '/', name: 'summary')]
    public function summary(
        PlayerRepository $playerRepository,
        MessageBusInterface $messageBus,
        ChartService $chartService,
        DoubleXpService $doubleXpService
    ): Response {
        $form = $this->headerSearchForm();
        $player = $playerRepository->findLatestByName($this->getCurrentPlayerName());

        if (is_null($player)) {
            $messageBus->dispatch(new FetchLatestApiData($this->getCurrentPlayerName()));

            return $this->redirectToRoute('summary');
        }

        return $this->render('summary.html.twig', [
            'chart' => $chartService->getQuestChart($player),
            'playerInfo' => $player,
            'form' => $form->createView(),
            'isDoubleXpLive' => $doubleXpService->isDoubleXpLive(),
        ]);
    }
}
