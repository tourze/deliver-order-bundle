<?php

namespace DeliverOrderBundle\Repository;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<DeliverStock>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: DeliverStock::class)]
class DeliverStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliverStock::class);
    }

    /**
     * 保存库存
     */
    public function save(DeliverStock $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除库存
     */
    public function remove(DeliverStock $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据发货单查找库存
     * @return DeliverStock[]
     */
    public function findByDeliverOrder(DeliverOrder $deliverOrder): array
    {
        return $this->findBy(['deliverOrder' => $deliverOrder]);
    }

    /**
     * 根据SKU ID查找库存
     * @return DeliverStock[]
     */
    public function findBySkuId(string $skuId): array
    {
        return $this->findBy(['skuId' => $skuId]);
    }

    /**
     * 查找未收货的库存
     * @return DeliverStock[]
     */
    public function findUnreceivedStocks(): array
    {
        return $this->findBy(['received' => false]);
    }

    /**
     * 根据批次号查找库存
     * @return array<DeliverStock>
     */
    public function findByBatchNo(string $batchNo): array
    {
        return $this->findBy(['batchNo' => $batchNo]);
    }

    /**
     * 根据序列号查找库存
     * @return array<DeliverStock>
     */
    public function findBySerialNo(string $serialNo): array
    {
        return $this->findBy(['serialNo' => $serialNo]);
    }

    /**
     * 根据发货单统计库存数量
     */
    public function countByDeliverOrder(DeliverOrder $deliverOrder): int
    {
        return $this->count(['deliverOrder' => $deliverOrder]);
    }

    /**
     * 根据发货单获取总数量
     */
    public function getTotalQuantityByDeliverOrder(DeliverOrder $deliverOrder): int
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.quantity) as total')
            ->where('s.deliverOrder = :deliverOrder')
            ->setParameter('deliverOrder', $deliverOrder)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * 查找日期范围内已收货的库存
     * @return array<DeliverStock>
     * @phpstan-return array<DeliverStock>
     */
    public function findReceivedInDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.received = :received')
            ->andWhere('s.receivedTime >= :startDate')
            ->andWhere('s.receivedTime <= :endDate')
            ->setParameter('received', true)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.receivedTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var array<DeliverStock> $result */
    }
}
