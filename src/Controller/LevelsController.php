<?php

namespace App\Controller;

use App\Enum\SkillEnum;
use App\Service\ChartService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
                'choices' => [
//                    ...['All' => 999],
                    ...SkillEnum::toArray()
                ],
                'multiple' => true
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
            /** @var array{skillCategory: int[]} $data */
            $data = $filterForm->getData();

            $chart = $chartService->getMonthlyTotalXpChartBySkills(
                $playerName,
                array_map(fn($skill) => SkillEnum::from($skill), $data['skillCategory'])
            );
        }

        return $this->render('levels.html.twig', [
            'chart' => $chart ?? $chartService->getMonthlyTotalXpChart($playerName),
            'form' => $form->createView(),
            'filterForm' => $filterForm->createView()
        ]);
    }
}
