<?php

namespace App\Controller;

use App\Enum\SkillEnum;
use App\Service\ChartService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

class LevelsController extends AbstractBaseController
{
    #[Route(path: '/levels/monthly', name: 'app_dashboard_levels')]
    public function levels(Request $request, ChartService $chartService): Response
    {
        $form = $this->headerSearchForm($request);
        $filterForm = $this->formFactory
            ->createNamedBuilder(name: 'filter_levels_form', options: [
                'attr' => [
                    'class' => 'text-light col-sm-5'
                ]
            ])
            ->add('skillCategory', ChoiceType::class, [
                'label' => 'Skill',
                'choices' => SkillEnum::toArray(),
                'multiple' => true,
                'required' => false
            ])
            ->add('chartType', ChoiceType::class, [
                'label' => 'Graph Type',
                'choices' => [
                    'Lines' => Chart::TYPE_LINE,
                    'Bars' => Chart::TYPE_BAR,
//                    'Bubble' => Chart::TYPE_BUBBLE,
//                    'Doughnut' => Chart::TYPE_DOUGHNUT,
//                    'Pie' => Chart::TYPE_PIE,
//                    'Polar Area' => Chart::TYPE_POLAR_AREA,
//                    'Radar' => Chart::TYPE_RADAR,
//                    'Scatter' => Chart::TYPE_SCATTER
                ]
            ])
            ->add('search', SubmitType::class, [
                'label' => 'Filter',
                'attr' => [
                    'class' => 'btn-custom'
                ]
            ])
            ->getForm();

        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var array{skillCategory: int[], chartType: string} $data */
            $data = $filterForm->getData();

            if (empty($data['skillCategory'])) {
                $chart = $chartService->getMonthlyTotalXpChart($this->getCurrentPlayerName(), $data['chartType']);
            } else {
                $chart = $chartService->getMonthlyTotalXpChartBySkills(
                    $this->getCurrentPlayerName(),
                    array_map(fn($skill) => SkillEnum::from($skill), $data['skillCategory']),
                    $data['chartType']
                );
            }
        }

        return $this->render('levels.html.twig', [
            'chart' => $chart ?? $chartService->getMonthlyTotalXpChart($this->getCurrentPlayerName()),
            'form' => $form->createView(),
            'filterForm' => $filterForm->createView()
        ]);
    }
}
