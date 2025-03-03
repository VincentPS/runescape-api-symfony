<?php

namespace App\Controller;

use App\Enum\SkillEnum;
use App\Service\Chart\Xp\DailyChartService;
use App\Service\Chart\Xp\HourlyChartService;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/xp-tracker')]
class XpTrackerController extends AbstractBaseController
{
    #[Route(path: '/daily', name: 'app_xp_tracker_daily')]
    public function daily(Request $request, DailyChartService $chartService): Response
    {
        $form = $this->headerSearchForm();
        $filterForm = $this->formFactory
            ->createNamedBuilder(name: 'filter_xp_tracker_form', options: [
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
            ->add('from', DateType::class, [
                'label' => 'From',
                'html5' => true,
                'required' => true,
                'data' => new DateTimeImmutable('-1 month'),
            ])
            ->add('to', DateType::class, [
                'label' => 'To',
                'html5' => true,
                'required' => true,
                'data' => new DateTimeImmutable(),
            ])
            ->add('chartType', ChoiceType::class, [
                'label' => 'Graph Type',
                'choices' => [
                    'Stacked Bars' => 'stackedBar',
                    'Lines' => Chart::TYPE_LINE
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
            /** @var array{skillCategory: int[], chartType: string, from: DateTime, to: DateTime} $data */
            $data = $filterForm->getData();

            if (empty($data['skillCategory'])) {
                $chart = $chartService->getTotalXpChart(
                    $this->getCurrentPlayerName(),
                    DateTimeImmutable::createFromMutable($data['from']),
                    DateTimeImmutable::createFromMutable($data['to']),
                    $data['chartType'],
                );
            } else {
                $chart = $chartService->getXpPerSkillChart(
                    $this->getCurrentPlayerName(),
                    array_map(fn($skill) => SkillEnum::from($skill), $data['skillCategory']),
                    DateTimeImmutable::createFromMutable($data['from']),
                    DateTimeImmutable::createFromMutable($data['to']),
                    $data['chartType'],
                );
            }
        }

        return $this->render('xp_tracker.html.twig', [
            'chart' => $chart ?? $chartService->getTotalXpChart(
                $this->getCurrentPlayerName(),
                new DateTimeImmutable('-1 month'),
                new DateTimeImmutable(),
            ),
            'form' => $form->createView(),
            'filterForm' => $filterForm->createView()
        ]);
    }

    #[Route(path: '/hourly', name: 'app_xp_tracker_hourly')]
    public function hourly(Request $request, HourlyChartService $chartService): Response
    {
        $form = $this->headerSearchForm();
        $filterForm = $this->formFactory
            ->createNamedBuilder(name: 'filter_xp_tracker_form', options: [
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
            ->add('date', DateType::class, [
                'label' => 'Date',
                'html5' => true,
                'required' => true,
                'data' => new DateTimeImmutable(),
            ])
            ->add('chartType', ChoiceType::class, [
                'label' => 'Graph Type',
                'choices' => [
                    'Stacked Bars' => 'stackedBar',
                    'Lines' => Chart::TYPE_LINE
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
            /** @var array{skillCategory: int[], chartType: string, date: DateTime} $data */
            $data = $filterForm->getData();

            if (empty($data['skillCategory'])) {
                $chart = $chartService->getTotalXpChart(
                    $this->getCurrentPlayerName(),
                    DateTimeImmutable::createFromMutable($data['date']),
                    $data['chartType'],
                );
            } else {
                $chart = $chartService->getXpPerSkillChart(
                    $this->getCurrentPlayerName(),
                    array_map(fn($skill) => SkillEnum::from($skill), $data['skillCategory']),
                    DateTimeImmutable::createFromMutable($data['date']),
                    $data['chartType'],
                );
            }
        }

        $chartToReturn = $chart
            ?? $chartService->getXpPerSkillChart(
                $this->getCurrentPlayerName(),
                array_map(fn($skill) => SkillEnum::from($skill), SkillEnum::toArray()),
                new DateTimeImmutable(),
                'stackedBar',
            );

        return $this->render('xp_tracker.html.twig', [
            'chart' => $chartToReturn,
            'form' => $form->createView(),
            'filterForm' => $filterForm->createView()
        ]);
    }
}
