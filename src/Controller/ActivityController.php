<?php

namespace App\Controller;

use App\Dto\Activity;
use App\Enum\ActivityFilter;
use App\Repository\PlayerRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ActivityController extends AbstractBaseController
{
    #[Route(path: '/activity', name: 'app_dashboard_activity')]
    public function activity(
        Request $request,
        PlayerRepository $playerRepository
    ): Response {
        $form = $this->headerSearchForm($request);
        $playerName = $this->getPlayerNameFromRequest($request);
        $filterForm = $this->filterForm($request);

        try {
            $activities = [];

            if ($filterForm->isSubmitted() && $filterForm->isValid()) {
                /** @var array{'acitivityCategory': ActivityFilter} $formData */
                $formData = $filterForm->getData();

                if ($formData['acitivityCategory'] !== ActivityFilter::All) {
                    $activities = $playerRepository->findAllUniqueActivitiesByPlayerNameAndActivityFilter(
                        $playerName,
                        $formData['acitivityCategory']
                    );
                }
            }

            // If the filter form is not submitted, or if the filter form is submitted but not valid (e.g. no filter
            // selected), then we want to show all activities.
            if (empty($activities)) {
                $activities = $playerRepository->findAllUniqueActivitiesByPlayerName($playerName);
            }
        } catch (Exception) {
            throw new AccessDeniedHttpException();
        }

        $serializer = Activity::getSerializer();

        /** @var array<int, Activity> $activities */
        $activities = $serializer->deserialize(
            $activities,
            Activity::class . '[]',
            'json'
        );

        usort($activities, function ($a, $b) {
            return $b->date <=> $a->date;
        });

        return $this->render('activity.html.twig', [
            'activities' => $activities,
            'form' => $form->createView(),
            'filterForm' => $filterForm->createView()
        ]);
    }

    private function filterForm(Request $request): FormInterface
    {
        $form = $this->formFactory
            ->createNamedBuilder(name: 'filter_activities_form', options: [
                'attr' => [
                    'class' => 'align-items-center d-flex'

                ]
            ])
            ->add('acitivityCategory', EnumType::class, [
                'attr' => [
                    'class' => 'form-select col-6',
                    'placeholder' => 'filter'
                ],
                'label' => false,
                'class' => ActivityFilter::class
            ])
            ->add('search', SubmitType::class, [
                'label' => 'Filter',
                'attr' => [
                    'class' => 'ms-2 btn btn-custom'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        return $form;
    }
}
