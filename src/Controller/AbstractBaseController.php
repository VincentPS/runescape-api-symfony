<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractBaseController extends AbstractController
{
    public function __construct(
        protected readonly FormFactoryInterface $formFactory,
        protected readonly RequestStack $requestStack
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
            $this->requestStack->getCurrentRequest()?->getSession()->set('currentPlayerName', $data['playerName']);
        }

        return $form;
    }

    protected function getCurrentPlayerName(): string
    {
        /** @var string $playerName */
        $playerName = $this->requestStack->getSession()->get('currentPlayerName');

        return $playerName;
    }
}
