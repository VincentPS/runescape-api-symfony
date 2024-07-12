<?php

namespace App\Controller;

use App\Enum\SkillEnum;
use App\Service\ChartService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LevelsController extends AbstractBaseController
{
    #[Route(path: '/levels/monthly', name: 'app_dashboard_levels')]
    public function levels(Request $request, ChartService $chartService): Response
    {
        $form = $this->headerSearchForm($request);
        $playerName = $this->getPlayerNameFromRequest($request);

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
                    'Bars' => 'bar',
                    'Lines' => 'line'
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
                $chart = $chartService->getMonthlyTotalXpChart($playerName, $data['chartType']);
            } else {
                $chart = $chartService->getMonthlyTotalXpChartBySkills(
                    $playerName,
                    array_map(fn($skill) => SkillEnum::from($skill), $data['skillCategory']),
                    $data['chartType']
                );
            }
        }

        return $this->render('levels.html.twig', [
            'chart' => $chart ?? $chartService->getMonthlyTotalXpChart($playerName),
            'form' => $form->createView(),
            'filterForm' => $filterForm->createView()
        ]);
    }
}
