<?php

namespace App\Controller;

use App\Enum\KnownPlayers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractBaseController extends AbstractController
{
    public function __construct(
        protected readonly FormFactoryInterface $formFactory
    ) {
    }

    protected function getPlayerNameFromRequest(Request $request): string
    {
        $form = $this->headerSearchForm($request);

        /** @var array{'playerName': ?string} $formData */
        $formData = $form->getData();

        return $formData['playerName']
            ?? $request->query->getAlnum('playerName')
            ?: KnownPlayers::Dapestave->value;
    }

    protected function headerSearchForm(Request $request): FormInterface
    {
        $form = $this->formFactory->createNamedBuilder(name: 'search_form', options: [
            'attr' => [
                'class' => 'd-flex'
            ]
        ])
            ->add('playerName', TextType::class, [
                'attr' => [
                    'class' => 'player-name-search-box form-control',
                    'placeholder' => 'Search for a player'
                ],
                'label' => false,
            ])
            ->add('search', SubmitType::class, [
                'label' => '<i class="fa fa-search"></i>',
                'attr' => [
                    'class' => 'player-name-search-button'
                ],
                'label_html' => true,
            ])
            ->getForm();

        if ($request->request->get('playerName')) {
            $form->setData(['playerName' => $request->request->get('playerName')]);
        }

        $form->handleRequest($request);

        return $form;
    }
}
