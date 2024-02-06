<?php

namespace App\Repository;

use App\Entity\Player;
use App\Enum\ActivityFilter;
use App\Enum\SkillEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @extends ServiceEntityRepository<Player>
 *
 * @method Player|null find($id, $lockMode = null, $lockVersion = null)
 * @method Player|null findOneBy(array $criteria, array $orderBy = null)
 * @method Player[]    findAll()
 * @method Player[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * Saves a player entity to the database.
     *
     * @param Player $entity The player entity to save.
     * @param bool $flush Whether to immediately flush changes to the database.
     *
     * @return void
     */
    public function save(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes a player entity from the database.
     *
     * @param Player $entity The player entity to remove.
     * @param bool $flush Whether to immediately flush changes to the database.
     *
     * @return void
     */
    public function remove(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds the latest Player record by name.
     *
     * @param string $name The name of the player to find.
     *
     * @return Player|null The latest Player record, or null if not found.
     */
    public function findLatestByName(string $name): ?Player
    {
        /** @var array<int, Player> $dataPoints */
        $dataPoints = $this->createQueryBuilder('p')
            ->andWhere('p.name = :name')
            ->setParameter('name', $name)
            ->setCacheable(true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($dataPoints)) {
            return null;
        }

        return $dataPoints[0];
    }

    /**
     * Retrieves a unique list of activities for a player with the specified name.
     *
     * @param string $name The name of the player to retrieve activities for.
     *
     * @return string|bool A JSON string containing a unique list of activities for the specified player, or false
     *                     if an error occurs.
     *
     * The JSON string contains an array of activities, each represented as a JSON object with three properties: "date",
     * "details", and "text". The "date" property is a string representation of the activity date in ISO-8601 format.
     * The "details" and "text" properties contain the corresponding activity details and text, respectively.
     * The activities are unique (i.e. there are no duplicate activities in the array) and are sorted in descending
     * order by their "date" property. If the player has no activities, an empty array is returned.
     *
     * @throws DBALException If an error occurs while executing the query.
     */
    public function findAllUniqueActivitiesByPlayerName(string $name): string|bool
    {
        $stmt = <<<SQL
            SELECT COALESCE(jsonb_agg(activity), '[]'::jsonb)
            FROM (SELECT DISTINCT jsonb_array_elements(activities) AS activity
                  FROM player
                  WHERE name = :name) AS all_activities
            WHERE activity IS NOT NULL;
        SQL;

        $result = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, ['name' => $name])
            ->fetchOne();

        return !is_string($result) ? false : $result;
    }

    /**
     * Retrieves a list of XP data for a given player between two dates.
     *
     * @param DateTimeInterface $start The start date of the XP data range.
     * @param DateTimeInterface $end The end date of the XP data range.
     * @param string $name The name of the player to retrieve XP data for.
     *
     * @return array<string, array{
     *     'date': string,
     *     'xp_increase': int,
     *     'unique_xp': array<int, string>
     * }>
     * An array of XP data for each day within the specified date range.
     * Each element of the array is keyed by the date in 'YYYY-MM-DD' format.
     * The value of each element is an array containing the following keys:
     * - 'date': The date in 'YYYY-MM-DD' format.
     * - 'xp_increase': The total amount of XP gained by the player between the first and last XP data for that day.
     * - 'unique_xp': An array containing all the unique XP values recorded for that day, in ascending order.
     * - 'avg_xp_gained': The average amount of XP gained by the player for that day.
     *
     * @throws DBALException If an error occurs while executing the database query.
     */
    public function findAllUniqueTotalXpBetweenDatesByNameGroupByDay(
        DateTimeInterface $start,
        DateTimeInterface $end,
        string $name
    ): array {
        $serializer = new Serializer([new ArrayDenormalizer()], [new JsonEncoder()]);

        $stmt = <<<SQL
            SELECT DATE_TRUNC('day', p.created_at)::date AS date,
                   MAX(p.total_xp) - MIN(p.total_xp) AS xp_increase,
                   jsonb_agg(p.total_xp) AS unique_xp
            FROM player p
            WHERE p.created_at >= :start
              AND p.created_at <= :end
              AND p.name = :name
            GROUP BY DATE_TRUNC('day', p.created_at)::date
            ORDER BY DATE_TRUNC('day', p.created_at)::date ASC;
        SQL;

        $results = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'name' => $name
            ])
            ->fetchAllAssociative();

        $groupedResults = [];

        foreach ($results as &$result) {
            if (!is_string($result['unique_xp'])) {
                throw new RuntimeException('Unexpected type for "unique_xp" column.');
            }

            $result['unique_xp'] = $serializer->decode($result['unique_xp'], 'json');
            $groupedResults[$result['date']] = $result;
        }

        /** @var array<string, array{
         *     'date': string,
         *     'xp_increase': int,
         *     'unique_xp': array<int, string>,
         * }> $groupedResults
         */
        return $groupedResults;
    }

    /**
     * Finds the earliest and latest DateTime objects for a given name.
     *
     * @param string $name The name to search for.
     *
     * @return array<string, DateTimeImmutable>|null
     * An associative array with the following keys:
     * - 'minDate': A DateTimeImmutable object representing the earliest date found.
     * - 'maxDate': A DateTimeImmutable object representing the latest date found.
     * If no results are found, null is returned.
     *
     * @throws NoResultException If no result is returned.
     * @throws NonUniqueResultException If more than one result is returned.
     */
    public function findFirstAndLastDateTimeByName(string $name): ?array
    {
        /** @var array{'minDate': ?string, 'maxDate': ?string} $dateTimes */
        $dateTimes = $this->createQueryBuilder('p')
            ->select('MIN(p.createdAt) AS minDate, MAX(p.createdAt) AS maxDate')
            ->andWhere('p.name = :name')
            ->setParameter('name', $name)
            ->setCacheable(true)
            ->getQuery()
            ->getSingleResult();

        if (is_null($dateTimes['minDate']) || is_null($dateTimes['maxDate'])) {
            return null;
        }

        try {
            return [
                'minDate' => new DateTimeImmutable($dateTimes['minDate']),
                'maxDate' => new DateTimeImmutable($dateTimes['maxDate']),
            ];
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Finds all player activities of a specific type based on the provided ActivityFilter.
     *
     * @param ActivityFilter $type The type of activities to filter (e.g., 'skills', 'quests', 'bosskills', 'loot').
     * @return string|bool Returns a JSON string containing the aggregated activities of the specified type,
     *                    or `false` if an error occurs during the database query.
     * @throws DBALException If an error occurs during the database query execution.
     */
    public function findAllUniqueActivitiesByPlayerNameAndActivityFilter(
        string $playerName,
        ActivityFilter $type
    ): string|bool {
        $stmt = <<<SQL
            SELECT COALESCE(jsonb_agg(activity), '[]'::jsonb)
            FROM (SELECT DISTINCT jsonb_array_elements(activities) AS activity
                  FROM player
                  WHERE name = :name) AS all_activities
            WHERE activity IS NOT NULL
                      AND CASE
                          WHEN :type = 'skills' THEN activity ->> 'text' ILIKE '%levelled%' OR
                                                     activity ->> 'text' ILIKE '%xp in%'
                          WHEN :type = 'quests' THEN activity ->> 'text' ILIKE '%quest complete%'
                          WHEN :type = 'bosses' THEN activity ->> 'text' ILIKE '%killed%'
                          WHEN :type = 'loot'   THEN activity ->> 'text' ILIKE '%i found%' AND
                                                     activity ->> 'text' NOT ILIKE '%pet%'
                          WHEN :type = 'pets'   THEN activity ->> 'text' ILIKE '%i found%' AND
                                                     activity ->> 'text' ILIKE '%pet%'
                          ELSE FALSE
                      END
        SQL;

        $result = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, ['name' => $playerName, 'type' => strtolower($type->name)])
            ->fetchOne();

        return !is_string($result) ? false : $result;
    }

    /**
     * Finds all player activities of a specific type based on the provided ActivityFilter.
     *
     * @param SkillEnum $skill The skill to filter activities by.
     * @return string|bool Returns a JSON string containing the aggregated activities of the specified type,
     *                    or `false` if an error occurs during the database query.
     * @throws DBALException If an error occurs during the database query execution.
     */
    public function findAllUniqueActivitiesByPlayerNameAndSkill(string $playerName, SkillEnum $skill): string|bool
    {
        $stmt = <<<SQL
            SELECT COALESCE(jsonb_agg(activity), '[]'::jsonb)
            FROM (SELECT DISTINCT jsonb_array_elements(activities) AS activity
                  FROM player
                  WHERE name = :name) AS all_activities
            WHERE activity IS NOT NULL
              AND (activity ->> 'text' ILIKE '%levelled%' OR activity ->> 'text' ILIKE '%xp in%')
              AND activity ->> 'text' ILIKE '%' || :skill || '%'
        SQL;

        $result = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, ['name' => $playerName, 'skill' => $skill->name])
            ->fetchOne();

        return !is_string($result) ? false : $result;
    }
}
