<?php

namespace App\Repository;

use App\Dto\Quest;
use App\Entity\Player;
use App\Enum\ActivityFilter;
use App\Enum\SkillEnum;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

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
     * @return Quest[]
     * @throws NonUniqueResultException
     */
    public function findAllQuests(string $playerName): array
    {
        /**
         * @var array{quests: Quest[] | null} $result
         */
        $result = $this->createQueryBuilder('p')
            ->select('p.quests')
            ->where('p.name = :name')
            ->setParameter('name', $playerName)
            ->orderBy('p.createdAt', Criteria::DESC)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['quests'] ?? [];
    }

    /**
     * @param string $playerName
     * @return Player[]
     */
    public function findAllByName(string $playerName): array
    {
        /** @var Player[] $result */
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.name = :name')
            ->setParameter('name', $playerName)
            ->getQuery()
            ->getResult();

        return $result;
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
FROM (
    SELECT DISTINCT jsonb_array_elements(activities) AS activity
    FROM player
    WHERE name = :name
) AS all_activities
WHERE activity IS NOT NULL
  AND CASE
      WHEN :type = 'skills' THEN 
           activity ->> 'text' ILIKE '%levelled%' OR
           activity ->> 'text' ILIKE '%xp in%'
      WHEN :type = 'quests' THEN 
           activity ->> 'text' ILIKE '%quest complete%'
      WHEN :type = 'bosses' THEN 
           activity ->> 'text' ILIKE '%killed%'
      WHEN :type = 'loot' THEN 
           activity ->> 'text' ILIKE '%i found%' AND
           activity ->> 'text' NOT ILIKE '%pet%'
      WHEN :type = 'pets' THEN 
           activity ->> 'text' ILIKE '%i found%' AND
           activity ->> 'text' ILIKE '%pet%'
      WHEN :type = 'dungeoneering' THEN 
           activity ->> 'details' ILIKE '%daemonheim%'
      ELSE 
           FALSE
  END;
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

    /**
     * Retrieves a list of XP data for a given player between two dates.
     *
     * @param DateTimeImmutable $start The start date of the XP data range.
     * @param DateTimeImmutable $end The end date of the XP data range.
     * @param string $name The name of the player to retrieve XP data for.
     *
     * @return array<array{
     *      date: string,
     *      total_xp_gain: string
     *  }>
     * An array of XP data for each day within the specified date range.
     * Each element of the array is keyed by the date in 'YYYY-MM-DD' format.
     * The value of each element is an array containing the following keys:
     * - 'date': The date in 'YYYY-MM-DD' format.
     * - 'total_xp_gain': The total XP gain for the day.
     *
     * @throws DBALException If an error occurs while executing the database query.
     * @throws DateMalformedStringException
     */
    public function findDailyXpRateForTotalXp(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        string $name
    ): array {
        $stmt = <<<SQL
WITH date_series AS (
    -- Generate a series of dates between the start and end date
    SELECT generate_series(:start_date::date, :end_date::date, '1 day') AS date
),
skill_xp_differences AS (
    SELECT
        p.name,
        DATE_TRUNC('day', p.created_at)::date AS date,
        s.skill_id,
        s.xp AS current_xp,
        LAG(s.xp) OVER (
            PARTITION BY p.name, s.skill_id
            ORDER BY p.created_at
        ) AS previous_xp
    FROM player p
    CROSS JOIN LATERAL (
        SELECT (jsonb_array_elements(p.skill_values) ->> 'id')::int AS skill_id,
               (jsonb_array_elements(p.skill_values) ->> 'xp')::numeric AS xp
    ) s
    WHERE p.created_at BETWEEN :start_date AND :end_date
      AND p.name = :name
),
xp_results AS (
    SELECT
        date,
        SUM(CASE
                WHEN previous_xp IS NULL THEN 0
                WHEN previous_xp <> current_xp THEN current_xp - previous_xp
                ELSE 0
            END) AS total_xp_gain
    FROM skill_xp_differences
    GROUP BY date
)
SELECT
    ds.date,
    COALESCE(xr.total_xp_gain, 0) AS total_xp_gain -- Fill in missing dates with 0
FROM date_series ds
LEFT JOIN xp_results xr ON ds.date = xr.date
ORDER BY ds.date;
SQL;

        /**
         * @var array<array{
         *     date: string,
         *     total_xp_gain: string
         * }> $results
         */
        $results = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, [
                'start_date' => $start->modify('00:00:00')->format('Y-m-d H:i:s'),
                'end_date' => $end->modify('23:59:59')->format('Y-m-d H:i:s'),
                'name' => $name
            ])
            ->fetchAllAssociative();

        return $results;
    }

    /**
     * @return array{int: array{date: string, xp_difference: string}}
     * @throws DBALException
     * @throws DateMalformedStringException
     */
    public function findDailyXpRateForSkillAtDate(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        string $name,
        SkillEnum $skillEnum
    ): array {
        $stmt = <<<SQL
WITH min_max_values AS (
    SELECT
        DATE_TRUNC('day', p.created_at)::date AS date,
        FIRST_VALUE(CAST(jsonb_element ->> 'xp' AS numeric)) OVER (
            PARTITION BY DATE_TRUNC('day', p.created_at)::date
            ORDER BY p.created_at
            ) AS first_xp,
        LAST_VALUE(CAST(jsonb_element ->> 'xp' AS numeric)) OVER (
            PARTITION BY DATE_TRUNC('day', p.created_at)::date
            ORDER BY p.created_at ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
            ) AS last_xp
    FROM player p
             CROSS JOIN LATERAL jsonb_array_elements(p.skill_values) AS jsonb_element
    WHERE p.created_at BETWEEN :start_date AND :end_date
      AND jsonb_element ->> 'id' = :skill_id
      AND p.name = :name
)
SELECT DISTINCT
    date,
    last_xp - first_xp AS xp_difference
FROM min_max_values
ORDER BY date;
SQL;

        /** @var array{int: array{date: string, xp_difference: string}} $results */
        $results = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, [
                'start_date' => $start->modify('00:00:00')->format('Y-m-d H:i:s'),
                'end_date' => $end->modify('23:59:59')->format('Y-m-d H:i:s'),
                'name' => $name,
                'skill_id' => $skillEnum->value
            ])
            ->fetchAllAssociative();

        return $results;
    }

    /**
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @param string $name
     * @return array<array{
     *      'date': string,
     *      'xp_increase': int
     *  }>
     * @throws DBALException
     * @throws DateMalformedStringException
     */
    public function findHourlyXpRateForTotalXp(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        string $name
    ): array {
        $stmt = <<<SQL
WITH hours AS (
    -- Generate a series of all hours between start and end time
    SELECT generate_series(
                   DATE_TRUNC('hour', :start::TIMESTAMP),
                   DATE_TRUNC('hour', :end::TIMESTAMP),
                   INTERVAL '1 hour'
           ) AS date
),
     latest_xp_per_hour AS (
         -- Get only the LATEST XP value per hour
         SELECT DISTINCT ON (DATE_TRUNC('hour', p.created_at))
             DATE_TRUNC('hour', p.created_at) AS date,
             p.total_xp
         FROM player p
         WHERE p.created_at BETWEEN :start::TIMESTAMP AND :end::TIMESTAMP
           AND p.name = :name
         ORDER BY date, p.created_at DESC  -- Take the latest XP value per hour
     ),
     xp_filled AS (
         -- Ensure all hours are present and use the last known XP as fallback
         SELECT
             h.date,
             COALESCE(xp.total_xp, LAG(xp.total_xp) OVER (ORDER BY h.date)) AS total_xp
         FROM hours h
                  LEFT JOIN latest_xp_per_hour xp ON h.date = xp.date
     ),
     xp_final AS (
         -- Calculate the XP increase per hour
         SELECT
             date,
             total_xp,
             total_xp - LAG(total_xp) OVER (ORDER BY date) AS xp_increase
         FROM xp_filled
     )
SELECT
    TO_CHAR(date, 'HH24:MI') AS date,
    GREATEST(xp_increase, 0) AS xp_increase  -- Correct XP increase per hour
FROM xp_final
ORDER BY date;
SQL;

        /** @var array<array{
         *     'date': string,
         *     'xp_increase': int
         * }> $results
         */
        $results = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, [
                'start' => $start->modify('00:00:00')->format('Y-m-d H:i:s'),
                'end' => $end->modify('23:59:59')->format('Y-m-d H:i:s'),
                'name' => $name
            ])
            ->fetchAllAssociative();

        return $results;
    }

    /**
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     * @param string $name
     * @param SkillEnum $skillEnum
     * @return array{int: array{date: string, xp_difference: string}}
     * @throws DBALException
     * @throws DateMalformedStringException
     */
    public function findHourlyXpRateForSkillAtDate(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        string $name,
        SkillEnum $skillEnum
    ): array {
        $stmt = <<<SQL
WITH hourly_xp AS (
    -- Get the XP logs per hour for a specific skill and player
    SELECT DISTINCT ON (DATE_TRUNC('hour', p.created_at))
        DATE_TRUNC('hour', p.created_at) AS hour_date,
        CAST(jsonb_element ->> 'xp' AS numeric) AS xp
    FROM player p
             CROSS JOIN LATERAL jsonb_array_elements(p.skill_values) AS jsonb_element
    WHERE jsonb_element ->> 'id' = :skill_id
      AND p.created_at BETWEEN :start_date AND :end_date
      AND p.name = :name
    ORDER BY hour_date, p.created_at DESC  -- Get the latest XP log per hour
),
     xp_with_gaps AS (
         -- Ensure all hours are present and use the previous XP as fallback
         SELECT
             h.hour_date,
             COALESCE(x.xp, LAG(x.xp) OVER (ORDER BY h.hour_date)) AS xp
         FROM (
                  -- Generate a list of all hours between start and end date
                  SELECT generate_series(
                                 DATE_TRUNC('hour', :start_date::TIMESTAMP),
                                 DATE_TRUNC('hour', :end_date::TIMESTAMP),
                                 INTERVAL '1 hour'
                         ) AS hour_date
              ) h
                  LEFT JOIN hourly_xp x ON h.hour_date = x.hour_date
     ),
     xp_final AS (
         -- Use the last XP of the previous day as fallback for the first value today
         SELECT
             w.hour_date,
             COALESCE(w.xp, (
                 SELECT xp FROM hourly_xp
                 WHERE hour_date < :start_date
                 ORDER BY hour_date DESC
                 LIMIT 1
             )) AS xp
         FROM xp_with_gaps w
     )
SELECT
    TO_CHAR(hour_date, 'HH24:MI') AS date,
    GREATEST(xp - LAG(xp) OVER (ORDER BY hour_date), 0) AS xp_difference  -- Correct XP difference per hour
FROM xp_final
ORDER BY hour_date;
SQL;

        /** @var array{int: array{date: string, xp_difference: string}} $results */
        $results = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($stmt, [
                'start_date' => $start->modify('00:00:00')->format('Y-m-d H:i:s'),
                'end_date' => $end->modify('23:59:59')->format('Y-m-d H:i:s'),
                'name' => $name,
                'skill_id' => $skillEnum->value
            ])
            ->fetchAllAssociative();

        return $results;
    }
}
