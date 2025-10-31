<?php

namespace DeliverOrderBundle\Repository;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<DeliverOrder>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: DeliverOrder::class)]
class DeliverOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliverOrder::class);
    }

    /**
     * 保存发货单
     */
    public function save(DeliverOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除发货单
     */
    public function remove(DeliverOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据序列号查找发货单
     */
    public function findOneBySn(string $sn): ?DeliverOrder
    {
        return $this->findOneBy(['sn' => $sn]);
    }

    /**
     * 根据来源查找发货单
     * @return array<DeliverOrder>
     */
    public function findBySource(string $sourceType, string $sourceId): array
    {
        return $this->findBy([
            'sourceType' => $sourceType,
            'sourceId' => $sourceId,
        ]);
    }

    /**
     * 根据状态查找发货单
     * @return array<DeliverOrder>
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * 根据状态统计发货单数量
     */
    public function countByStatus(string $status): int
    {
        return $this->count(['status' => $status]);
    }

    /**
     * 根据序列号检查发货单是否存在
     */
    public function existsBySn(string $sn): bool
    {
        return $this->count(['sn' => $sn]) > 0;
    }

    /**
     * 查找超过指定日期的待处理订单
     * @return array<DeliverOrder>
     * @phpstan-return array<DeliverOrder>
     */
    public function findPendingOrdersOlderThan(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->andWhere('d.createTime < :date')
            ->setParameter('status', 'pending')
            ->setParameter('date', $date)
            ->orderBy('d.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<DeliverOrder> $result */
    }

    /**
     * 查找最近的订单（带限制）
     * @return array<DeliverOrder>
     * @phpstan-return array<DeliverOrder>
     */
    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        /** @var array<DeliverOrder> $result */
    }

    /**
     * 根据日期范围查找订单
     * @return array<DeliverOrder>
     * @phpstan-return array<DeliverOrder>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.createTime >= :startDate')
            ->andWhere('d.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('d.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<DeliverOrder> $result */
    }

    /**
     * 根据状态获取统计信息
     * @return array<string, int>
     */
    public function getStatisticsByStatus(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.status, COUNT(d.id) as count')
            ->groupBy('d.status')
        ;

        $results = $qb->getQuery()->getResult();

        return $this->processStatisticsResults($results);
    }

    /**
     * @param mixed $results
     * @return array<string, int>
     */
    private function processStatisticsResults($results): array
    {
        $statistics = [];

        if (!is_array($results)) {
            return $statistics;
        }

        foreach ($results as $result) {
            $statusCount = $this->extractStatusCount($result);
            if (null !== $statusCount) {
                [$status, $count] = $statusCount;
                $statistics[$status] = $count;
            }
        }

        return $statistics;
    }

    /**
     * @param mixed $result
     * @return array{string, int}|null
     */
    private function extractStatusCount($result): ?array
    {
        if (!is_array($result) || !isset($result['status'], $result['count'])) {
            return null;
        }

        $status = $result['status'];
        $key = $status instanceof DeliverOrderStatus ? $status->value : (string) $status;
        $count = is_numeric($result['count']) ? (int) $result['count'] : 0;

        return [$key, $count];
    }
}
