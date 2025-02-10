<?php

namespace App\Controller;

use App\Dto\SkillValue;
use App\Enum\EliteSkillLevelXpEnum;
use App\Enum\SkillEnum;
use App\Enum\SkillLevelXpEnum;
use App\Repository\PlayerRepository;
use App\Trait\SerializerAwareTrait;
use NumberFormatter;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SkillLevelProgressionController extends AbstractBaseController
{
    use SerializerAwareTrait;

    #[Route(path: '/levels/progress', name: 'app_dashboard_skill_level_progression')]
    public function skillLevelProgression(
        Request $request,
        DataTableFactory $dataTableFactory,
        PlayerRepository $playerRepository
    ): Response {
        $form = $this->headerSearchForm();
        $playerData = $playerRepository->findLatestByName($this->getCurrentPlayerName());

        if ($playerData === null) {
            return $this->redirectToRoute('summary');
        }

        $skillValues = array_map(
            function (SkillValue $skillValue) {
                /**
                 * @var array{id: int, xp: float, level: int, rank: int} $array
                 */
                $array = $this->getSerializer()->normalize($skillValue, SkillValue::class);

                //todo: fix the progress bar for skills that have virtual levels (100-127)

                //determine progress
                $skill = SkillEnum::from($array['id']);
                $xpEnum = $skill->isElite() ? EliteSkillLevelXpEnum::class : SkillLevelXpEnum::class;
                $currentLevelXp = $xpEnum::{'Level' . $array['level']};
                $nextLevelXp = $xpEnum::{'Level' . ($array['level'] + 1)};

                $array['progress'] = $array['xp'] > $nextLevelXp->value
                    ? 100
                    : ($array['xp'] - $currentLevelXp->value) / ($nextLevelXp->value - $currentLevelXp->value) * 100;

                $array['xp_left'] = $array['xp'] > $nextLevelXp->value ? null : $nextLevelXp->value - $array['xp'];

                // correct the level for elite skills
                if ($skill->isElite()) {
                    $array['level'] = $xpEnum::getLevelByXp($array['xp']);
                }

                return $array;
            },
            $playerData->getSkillValues()
        );

        $table = $dataTableFactory
            ->create([
                'paging' => false,
                'ordering' => true,
                'jQueryUI' => true,
                'autoWidth' => true
            ])
            ->add(
                'id',
                TextColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Skill',
                    'render' => static function (int $value) {
                        $skill = SkillEnum::from($value);

                        $imageUrl = <<<HTML
                        <img src="https://runescape.wiki/images/%s-icon.png" class="skill-icon" alt="%s" title="%s">
                        HTML;

                        return
                            sprintf($imageUrl, $skill->name, $skill->name, $skill->name)
                            . '<span class="text-white">' . $skill->name . '</span>';
                    }
                ]
            )
            ->add(
                'xp',
                NumberColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Total XP',
                    'render' => static function (float $value) {
                        $numberFormatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
                        $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
                        return $numberFormatter->format($value);
                    }
                ]
            )
            ->add(
                'level',
                NumberColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Level',
                    'render' => static fn(int $value) => $value
                ]
            )
            ->add(
                'progress',
                NumberColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Progress',
                    'render' => static function (float $value, array $skillValue) {
                        $displayValue = number_format($value, 2);
                        $color = SkillEnum::from($skillValue['id'])->graphColor();

                        return <<<HTML
                            <div class="skills-progression-outer-container">
                                <div class="progress skills-progression-container">
                                    <div class="progress-bar"
                                         role="progressbar"
                                         style="width: $value%; background-color: $color;"
                                         aria-valuenow="$value"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="text-center text-white skills-progression-percentage">
                                    $displayValue%
                                </div>
                            </div>
                        HTML;
                    }
                ]
            )
            ->add(
                'xp_left',
                NumberColumn::class,
                [
                    'orderable' => true,
                    'label' => 'XP Left',
                    'render' => static function (float $value) {
                        $numberFormatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
                        $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
                        return $numberFormatter->format($value);
                    }
                ]
            )
            ->add(
                'rank',
                TextColumn::class,
                [
                    'orderable' => true,
                    'label' => 'Rank',
                    'render' => static function (int | string $value) {
                        $value = (int)$value;
                        if ($value === 0) {
                            return '';
                        }

                        $numberFormatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
                        return $numberFormatter->format($value);
                    }
                ]
            )
            ->addOrderBy('progress', 'desc')
            ->createAdapter(ArrayAdapter::class, $skillValues)
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('levels_progress.html.twig', [
            'datatable' => $table,
            'playerData' => $playerData,
            'form' => $form->createView()
        ]);
    }
}
