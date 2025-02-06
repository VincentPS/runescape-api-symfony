<?php

namespace App\Controller;

use App\Dto\Quest;
use App\Enum\QuestDifficulty;
use App\Repository\PlayerRepository;
use App\Trait\SerializerAwareTrait;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuestsController extends AbstractBaseController
{
    use SerializerAwareTrait;

    #[Route(path: '/quests', name: 'quests')]
    public function index(
        Request $request,
        DataTableFactory $dataTableFactory,
        PlayerRepository $playerRepository
    ): Response {
        $form = $this->headerSearchForm();
        $quests = $playerRepository->findAllQuests($this->getCurrentPlayerName());

        $quests = array_map(
            fn(Quest $quest) => $this->getSerializer()->normalize($quest, Quest::class),
            $quests
        );

        $table = $dataTableFactory
            ->create([
                'paging' => true,
                'pagingType' => 'simple_numbers',
                'ordering' => true,
                'lengthMenu' => [[10, 25, 50, -1], [10, 25, 50, 'All']],
                'jQueryUI' => true,
                'autoWidth' => true,
                'pageLength' => 30,
                'searching' => true,
            ])
            ->add(
                'title',
                TextColumn::class,
                [
                    'searchable' => true,
                    'orderable' => true,
                    'label' => 'Title',
                    'render' => static function ($value) {
                        $url = sprintf(
                            'https://runescape.wiki/w/%s/Quick_guide',
                            str_replace(' ', '_', $value)
                        );

                        return sprintf(
                            '<a class="text-white text-decoration-none" href="%s" target="_blank">%s</a>',
                            $url,
                            $value
                        );
                    }
                ]
            )
            ->add(
                'difficulty',
                TextColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Difficulty',
                    'render' => fn($value) => QuestDifficulty::from($value)->name
                ]
            )
            ->add('questPoints', NumberColumn::class, ['label' => 'Quest Points'])
            ->add(
                'members',
                BoolColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Members',
                    'render' => fn($value) => $value === 'true'
                        ? '<img class="quests-members" 
                                src="https://cdn.runescape.com/assets/img/external/runemetrics/membership-icon.png" 
                                alt="P2P">'
                        : ''
                ]
            )
            ->add(
                'status',
                TextColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Status',
                    'render' => fn($value) => match ($value) {
                        'COMPLETED' => '<span class="badge bg-success">Completed</span>',
                        'STARTED' => '<span class="badge bg-warning">Started</span>',
                        'NOT_STARTED' => '<span class="badge bg-danger">Not Started</span>',
                        default => '<span class="badge bg-secondary">Unknown</span>',
                    }
                ]
            )
            ->addOrderBy('title')
            ->createAdapter(ArrayAdapter::class, $quests)
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('quests.html.twig', [
            'datatable' => $table,
            'form' => $form->createView()
        ]);
    }
}
