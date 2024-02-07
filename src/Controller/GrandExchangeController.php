<?php

namespace App\Controller;

use App\Enum\CatalogueCategory;
use App\Exception\RateLimitedException;
use App\Service\GrandExchangeApiService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GrandExchangeController extends AbstractController
{
    #[Route(path: '/grand-exchange', name: 'app_dashboard_grand_exchange')]
    public function index(
        Request $request,
        FormFactoryInterface $formFactory,
        GrandExchangeApiService $grandExchangeApiService
    ): Response {
        $itemForm = $formFactory->createNamedBuilder(name: 'item_form', options: [
            'attr' => [
                'class' => 'text-light col-sm-5'
            ]
        ])
            ->add('itemName', TextType::class, [
                'attr' => [
                    'placeholder' => 'Search for an item'
                ],
                'label' => 'Item Name',
            ])
            ->add('itemCategory', ChoiceType::class, [
                'label' => 'Category',
                'choices' => CatalogueCategory::getRepresentableCases()
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-custom',
                ],
                'label' => 'Search',
            ])
            ->getForm();

        $itemForm->handleRequest($request);

        if ($itemForm->isSubmitted() && $itemForm->isValid()) {
            /** @var array{itemName: string, itemCategory: string, submit: string} $data */
            $data = $itemForm->getData();
            $itemCategory = CatalogueCategory::from($data['itemCategory']);

            try {
                $catalogueResponseCollection = match ($itemCategory) {
                    CatalogueCategory::All => $grandExchangeApiService
                        ->getItemInformationFromApi($data['itemName']),
                    default => $grandExchangeApiService
                        ->getItemInformationFromApiByCategory($data['itemName'], $itemCategory)
                };
            } catch (RateLimitedException | GuzzleException $e) {
                $this->addFlash('info', $e->getMessage());
            }
        }

        return $this->render(
            'grand_exchange/index.html.twig',
            [
                'itemForm' => $itemForm->createView(),
                'catalogueResponseCollection' => $catalogueResponseCollection ?? null,
                'multipleCategories' => count($catalogueResponseCollection->categories ?? []) > 1
            ]
        );
    }
}
