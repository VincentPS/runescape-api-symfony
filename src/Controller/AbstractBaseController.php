<?php

namespace App\Controller;

use App\Exception\PlayerApi\PlayerNotAMemberException;
use App\Exception\PlayerApi\PlayerNotFoundException;
use App\Repository\KnownPlayerRepository;
use App\Service\RsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

abstract class AbstractBaseController extends AbstractController
{
    public function __construct(
        protected readonly FormFactoryInterface $formFactory,
        protected readonly RequestStack $requestStack,
        protected readonly RsApiService $rsApiService,
        protected readonly KnownPlayerRepository $knownPlayerRepository
    ) {
    }

    protected function headerSearchForm(): FormInterface
    {
        $form = $this->formFactory->createNamedBuilder(name: 'search_form', options: [
            'attr' => [
                'class' => 'd-flex align-items-center'
            ]
        ])
            ->add('playerName', TextType::class, [
                'attr' => [
                    'class' => 'player-name-search-box form-control',
                    'placeholder' => 'Search for a player'
                ],
                'label' => false,
                'row_attr' => [
                    'class' => 'empty-class'
                ]
            ])
            ->add('search', SubmitType::class, [
                'label' => '<i class="fa fa-search"></i>',
                'attr' => [
                    'class' => 'player-name-search-button'
                ],
                'label_html' => true,
                'row_attr' => [
                    'class' => 'empty-class'
                ]
            ])
            ->getForm();

        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{playerName: string} $data */
            $data = $form->getData();
            $this->setCurrentPlayerNameInSession($data['playerName']);
        }

        return $form;
    }

    protected function getCurrentPlayerName(): string
    {
        /** @var string $playerName */
        $playerName = $this->requestStack->getSession()->get('currentPlayerName');

        if (empty($playerName)) {
            $this->redirectToRoute('welcome');
        }

        return $playerName;
    }

    protected function setCurrentPlayerNameInSession(string $playerName): void
    {
        try {
            $playerExists = $this->knownPlayerRepository->findOneByName($playerName);

            // The if/else here is necessary to handle the case-sensitive nature of the API.
            // The API will provide the name in the correct case, but the user may have entered it in a different
            // case. We want to store the name in the correct case in the database, and in the session.
            if ($playerExists === null) {
                $player = $this->rsApiService->getProfile($playerName);
                $this->requestStack->getSession()->set('currentPlayerName', $player->getName());
            } else {
                $this->requestStack->getSession()->set('currentPlayerName', $playerExists->getName());
            }
        } catch (PlayerNotFoundException | PlayerNotAMemberException $e) {
            $this->addFlash('info', $e->getMessage());
        } catch (Throwable) {
            $this->addFlash('danger', 'An error occurred while fetching player data');
        }
    }
}
