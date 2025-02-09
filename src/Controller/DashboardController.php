<?php

namespace App\Controller;

use App\Repository\KnownPlayerRepository;
use App\Repository\PlayerRepository;
use App\Service\ChartService;
use App\Service\DoubleXpService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractBaseController
{
    #[Route(path: '/', name: 'summary')]
    public function summary(
        PlayerRepository $playerRepository,
        KnownPlayerRepository $knownPlayerRepository,
        ChartService $chartService,
        DoubleXpService $doubleXpService
    ): Response {
        $form = $this->headerSearchForm();
        $playerName = $this->getCurrentPlayerName();
        $player = $playerRepository->findLatestByName($playerName);
        $knownPlayer = $knownPlayerRepository->findOneByName($playerName);

        if (is_null($player)) {
            return $this->redirectToRoute('welcome');
        }

        return $this->render('summary.html.twig', [
            'chart' => $chartService->getQuestChart($player),
            'playerInfo' => $player,
            'clanName' => $knownPlayer?->getClanName(),
            'form' => $form->createView(),
            'isDoubleXpLive' => $doubleXpService->isDoubleXpLive(),
        ]);
    }
}
