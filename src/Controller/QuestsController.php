<?php

namespace App\Controller;

use App\Dto\Quest;
use App\Enum\KnownPlayers;
use App\Enum\QuestDifficulty;
use App\Repository\PlayerRepository;
use App\Trait\SerializerAwareTrait;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuestsController extends AbstractController
{
    use SerializerAwareTrait;

    #[Route(path: '/quests', name: 'quests')]
    public function index(
        Request $request,
        DataTableFactory $dataTableFactory,
        PlayerRepository $playerRepository
    ): Response {
        $quests = $playerRepository
            ->findAllQuests(KnownPlayers::VincentS->value);

        $quests = array_map(
            fn(Quest $quest) => $this->getSerializer()->normalize($quest, Quest::class),
            $quests
        );

        $table = $dataTableFactory
            ->create([
                'paging' => false,
                'order' => [[0, 'asc']],
                'autoWidth' => true,
                'pageLength' => 0,
            ])
            ->add('title', TextColumn::class, ['label' => 'Title'])
            ->add(
                'difficulty',
                TextColumn::class,
                [
                    'label' => 'Difficulty',
                    'render' => fn($value) => QuestDifficulty::from($value)->name
                ]
            )
            ->add('questPoints', NumberColumn::class, ['label' => 'Quest Points'])
            ->add(
                'members',
                BoolColumn::class,
                [
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
                    'label' => 'Status',
                    'render' => fn($value) => match ($value) {
                        'COMPLETED' => '<span class="badge bg-success">Completed</span>',
                        'STARTED' => '<span class="badge bg-warning">Started</span>',
                        'NOT_STARTED' => '<span class="badge bg-danger">Not Started</span>',
                        default => '<span class="badge bg-secondary">Unknown</span>',
                    }
                ]
            )
            ->createAdapter(ArrayAdapter::class, $quests)
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('quests.html.twig', [
            'datatable' => $table,
        ]);
    }
}
