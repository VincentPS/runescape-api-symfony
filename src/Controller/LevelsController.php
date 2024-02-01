<?php

namespace App\Controller;

use App\Service\ChartService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LevelsController extends AbstractBaseController
{
    #[Route(path: '/levels/today', name: 'app_dashboard_levels')]
    public function levels(Request $request, ChartService $chartService): Response
    {
        $form = $this->headerSearchForm($request);
        $playerName = $this->getPlayerNameFromRequest($request);

        return $this->render('levels.html.twig', [
            'chart' => $chartService->getMonthlyTotalXpChart($playerName),
            'form' => $form->createView()
        ]);
    }
}
