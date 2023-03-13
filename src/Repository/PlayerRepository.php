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

    public function save(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Player $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string $name
     * @return null|Player
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
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param string $name
     * @return array<string, array<int, int>>
     */
    public function findAllUniqueTotalXpBetweenDatesByNameGroupByDay(
        DateTimeInterface $start,
        DateTimeInterface $end,
        string $name
    ): array {
        $results = $this->createQueryBuilder('p')
            ->select('p.totalXp, DATE(p.createdAt) AS day')
            ->andWhere('p.createdAt >= :start')
            ->andWhere('p.createdAt <= :end')
            ->andWhere('p.name = :name')
            ->setParameters([
                'start' => $start,
                'end' => $end,
                'name' => $name
            ])
            ->addGroupBy('day, p.totalXp')
            ->addOrderBy('day', 'ASC')
            ->addOrderBy('p.totalXp', 'ASC')
            ->setCacheable(true)
            ->getQuery()
            ->getResult();

        $groupedResults = [];
        foreach ($results as $result) {
            $day = $result['day'];

            if (!isset($groupedResults[$day])) {
                $groupedResults[$day] = [];
            }

            $groupedResults[$day][] = $result['totalXp'];
        }

        return $groupedResults;
    }

    /**
     * @param string $name
     * @return array<string, DateTimeImmutable>|null
     * @throws NoResultException
     * @throws NonUniqueResultException
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
