<?php

namespace App\Controller;

use App\Dto\Activity;
use App\Enum\ActivityFilter;
use App\Enum\SkillEnum;
use App\Repository\PlayerRepository;
use Doctrine\DBAL\Exception;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ActivityController extends AbstractBaseController
{
    #[Route(path: '/activity', name: 'activities')]
    public function activity(
        Request $request,
        PlayerRepository $playerRepository
    ): Response {
        $form = $this->headerSearchForm();
        $filterForm = $this->filterForm($request);

        try {
            $activities = [];

            if ($filterForm->isSubmitted() && $filterForm->isValid()) {
                /** @var array{
                 *     'acitivityCategory': ActivityFilter,
                 *     'skillCategory': SkillEnum
                 * } $formData
                 */
                $formData = $filterForm->getData();

                if ($formData['acitivityCategory'] !== ActivityFilter::All) {
                    if (
                        $formData['acitivityCategory'] == ActivityFilter::Skills
                        && array_key_exists('skillCategory', $formData)
                        && $formData['skillCategory'] !== null
                    ) {
                        $activities = $playerRepository->findAllUniqueActivitiesByPlayerNameAndSkill(
                            $this->getCurrentPlayerName(),
                            $formData['skillCategory']
                        );
                    } else {
                        $activities = $playerRepository->findAllUniqueActivitiesByPlayerNameAndActivityFilter(
                            $this->getCurrentPlayerName(),
                            $formData['acitivityCategory']
                        );
                    }
                }
            }

            // If the filter form is not submitted, or if the filter form is submitted but not valid (e.g. no filter
            // selected), then we want to show all activities.
            if (empty($activities)) {
                $activities = $playerRepository->findAllUniqueActivitiesByPlayerName($this->getCurrentPlayerName());
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

        return $this->render('activities.html.twig', [
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
                    'class' => 'align-items-center d-flex mb-1'

                ]
            ])
            ->add('acitivityCategory', EnumType::class, [
                'attr' => [
                    'class' => 'form-select col-6',
                    'placeholder' => 'filter'
                ],
                'label' => false,
                'class' => ActivityFilter::class
            ]);

        // If the form is submitted and the activity category skills is selected, then show the skill category
        if (
            is_array($request->request->all())
            && array_key_exists('filter_activities_form', $request->request->all())
            && is_array($request->request->all()['filter_activities_form'])
            && array_key_exists('acitivityCategory', $request->request->all()['filter_activities_form'])
            && $request->request->all()['filter_activities_form']['acitivityCategory'] === ActivityFilter::Skills->value
        ) {
            $form->add('skillCategory', EnumType::class, [
                'attr' => [
                    'class' => 'form-select ms-2 pe-4',
                    'placeholder' => 'filter'
                ],
                'label' => false,
                'class' => SkillEnum::class,
            ]);
        }

        $form->add('search', SubmitType::class, [
            'label' => 'Filter',
            'attr' => [
                'class' => 'ms-3 btn btn-custom'
            ]
        ]);

        $form = $form->getForm();
        $form->handleRequest($request);

        return $form;
    }
}
