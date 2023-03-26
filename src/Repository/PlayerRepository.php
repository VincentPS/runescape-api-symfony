<?php

namespace App\Repository;

use App\Entity\Player;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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
     * @throws \Doctrine\DBAL\Exception If an error occurs while executing the query.
     */
    public function findAllUniqueActivitiesByName(string $name): string | bool
    {
        $stmt = <<<SQL
            SELECT COALESCE(jsonb_agg(activity), '[]'::jsonb)
            FROM (SELECT DISTINCT jsonb_array_elements(activities) AS activity
                  FROM player
                  WHERE name = :name) AS all_activities
            WHERE activity IS NOT NULL;
        SQL;

        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, ['name' => $name])
            ->fetchOne();
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
     *     'unique_xp': array<int, string>,
     *     'avg_xp_gained': float
     * }>
     * An array of XP data for each day within the specified date range.
     * Each element of the array is keyed by the date in 'YYYY-MM-DD' format.
     * The value of each element is an array containing the following keys:
     * - 'date': The date in 'YYYY-MM-DD' format.
     * - 'xp_increase': The total amount of XP gained by the player between the first and last XP data for that day.
     * - 'unique_xp': An array containing all the unique XP values recorded for that day, in ascending order.
     * - 'avg_xp_gained': The average amount of XP gained by the player for that day.
     *
     * @throws \Doctrine\DBAL\Exception If an error occurs while executing the database query.
     */
    public function findAllUniqueTotalXpBetweenDatesByNameGroupByDay(
        DateTimeInterface $start,
        DateTimeInterface $end,
        string $name
    ): array {
        $stmt = <<<SQL
            SELECT DATE_TRUNC('day', p.created_at)::date AS date,
                   MAX(p.total_xp) - MIN(p.total_xp) AS xp_increase,
                   ARRAY_AGG(p.total_xp) AS unique_xp
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
            $result['unique_xp'] = explode(
                ',',
                str_replace(['{', '}'], '', $result['unique_xp'])
            );

            $groupedResults[$result['date']] = $result;
            $groupedResults[$result['date']]['avg_xp_gained'] = round($result['xp_increase'] / count($results));
        }

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
}
