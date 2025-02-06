<?php

namespace App\Controller;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WelcomeController extends AbstractBaseController
{
    #[Route(path: '/welcome', name: 'welcome')]
    public function welcome(Request $request): Response
    {
        $form = $this->headerSearchForm();

        $playerNameForm = $this->formFactory->createNamedBuilder(name: 'search_form_welcome', options: [
            'attr' => [
                'class' => 'd-flex align-items-center'
            ]
        ])
            ->add('playerNameWelcome', TextType::class, [
                'attr' => [
                    'class' => 'player-name-search-box form-control',
                    'placeholder' => 'Enter your RSN'
                ],
                'label' => false
            ])
            ->add('searchWelcome', SubmitType::class, [
                'label' => '<i class="fa fa-search"></i>',
                'attr' => [
                    'class' => 'player-name-search-button'
                ],
                'label_html' => true
            ])
            ->getForm();

        $playerNameForm->handleRequest($request);

        if ($playerNameForm->isSubmitted() && $playerNameForm->isValid()) {
            /** @var array{playerNameWelcome: string} $data */
            $data = $playerNameForm->getData();
            $request->getSession()->set('currentPlayerName', $data['playerNameWelcome']);
        }

        return $this->render('welcome.html.twig', [
            'form' => $form->createView(),
            'playerNameForm' => $playerNameForm->createView()
        ]);
    }
}
