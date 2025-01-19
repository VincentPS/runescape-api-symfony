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

class LevelsProgressController extends AbstractBaseController
{
    use SerializerAwareTrait;

    #[Route(path: '/levels/progress', name: 'app_dashboard_levels_progress')]
    public function levels(
        Request $request,
        DataTableFactory $dataTableFactory,
        PlayerRepository $playerRepository
    ): Response {
        $playerName = $this->getPlayerNameFromRequest($request);
        $playerData = $playerRepository->findLatestByName($playerName);

        if ($playerData === null) {
            return $this->redirectToRoute('summary');
        }

        $skillValues = array_map(
            function (SkillValue $skillValue) {
                /**
                 * @var array{id: int, xp: float, level: int, rank: int} $array
                 */
                $array = $this->getSerializer()->normalize($skillValue, SkillValue::class);

                //determine progress
                $skill = SkillEnum::from($array['id']);
                $xpEnum = $skill->isElite() ? EliteSkillLevelXpEnum::class : SkillLevelXpEnum::class;
                $currentLevelXp = $xpEnum::{'Level' . $array['level']};
                $nextLevelXp = $xpEnum::{'Level' . ($array['level'] + 1)};

                $array['progress'] =
                    ($array['xp'] - $currentLevelXp->value) / ($nextLevelXp->value - $currentLevelXp->value) * 100;

                $array['xp_left'] = $nextLevelXp->value - $array['xp'];

                return $array;
            },
            $playerData->getSkillValues()
        );

        $table = $dataTableFactory
            ->create([
                'paging' => false,
                'ordering' => false,
                'jQueryUI' => true,
                'autoWidth' => true
            ])
            ->add(
                'id',
                TextColumn::class,
                [
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
                    'label' => 'Level',
                    'render' => static function (int $value) {
                        return $value;
                    }
                ]
            )
            ->add(
                'progress',
                NumberColumn::class,
                [
                    'label' => 'Progress',
                    'render' => static function (float $value) use ($skillValues) {
                        $displayValue = number_format($value, 2);
                        $color = 'rgb(255, 255, 255)';

                        foreach ($skillValues as $skillValue) {
                            if (round($skillValue['progress'], 3) === round($value, 3)) {
                                $color = SkillEnum::from($skillValue['id'])->graphColor();
                                break;
                            }
                        }

                        return <<<HTML
                            <div style="display: flex; border: 3px solid #122937; border-radius: 2px">
                                <div class="progress" style="background-color: rgba(1,11,16,0); width: 70%; height: 21px; border-radius: revert;">
                                    <div class="progress-bar"
                                         role="progressbar"
                                         style="width: $value%; background-color: $color;"
                                         aria-valuenow="$value"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="text-center text-white" style="width: 30%; background-color: #122937;">
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
                    'label' => 'Rank',
                    'render' => static function (int $value) {
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
        ]);
    }
}
