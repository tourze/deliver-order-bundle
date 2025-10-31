<?php

namespace DeliverOrderBundle\Tests\Repository;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverOrderRepository::class)]
#[RunTestsInSeparateProcesses]
final class DeliverOrderRepositoryTest extends AbstractRepositoryTestCase
{
    private DeliverOrderRepository $repository;

    protected function getRepository(): DeliverOrderRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $entity = new DeliverOrder();
        $entity->setSn('DO' . uniqid());
        $entity->setSourceType(SourceType::ORDER);
        $entity->setSourceId(uniqid());
        $entity->setStatus(DeliverOrderStatus::PENDING);

        return $entity;
    }

    protected function onSetUp(): void
    {
        $container = self::getContainer();
        /** @var DeliverOrderRepository $repository */
        $repository = $container->get(DeliverOrderRepository::class);
        $this->assertInstanceOf(DeliverOrderRepository::class, $repository);
        $this->repository = $repository;

        $currentTest = $this->name();
        if ('testFindAllWhenNoRecordsExistShouldReturnEmptyArray' === $currentTest) {
            // 清理所有数据以确保测试在空数据库上运行
            $em = self::getEntityManager();
            $em->createQuery('DELETE FROM ' . DeliverOrder::class)->execute();
        }
    }

    public function testFindDeliverOrderById(): void
    {
        $repository = $this->getRepository();

        $entity = new DeliverOrder();
        $entity->setSn('DO' . uniqid());
        $entity->setSourceType(SourceType::ORDER);
        $entity->setSourceId(uniqid());
        $entity->setStatus(DeliverOrderStatus::PENDING);

        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $found = $repository->find($entity->getId());

        $this->assertNotNull($found);
        $this->assertEquals($entity->getSn(), $found->getSn());
    }

    public function testSaveMethod(): void
    {
        $repository = $this->getRepository();

        $entity = new DeliverOrder();
        $entity->setSn('DO00000002');
        $entity->setSourceType(SourceType::ORDER);
        $entity->setSourceId(uniqid());
        $entity->setStatus(DeliverOrderStatus::PENDING);

        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $found = $repository->findOneBySn('DO00000002');
        $this->assertNotNull($found);
        $this->assertEquals($entity->getSourceId(), $found->getSourceId());
    }

    public function testRemoveMethod(): void
    {
        $repository = $this->getRepository();

        $entity = new DeliverOrder();
        $entity->setSn('DO00000003');
        $entity->setSourceType(SourceType::ORDER);
        $entity->setSourceId('12347');
        $entity->setStatus(DeliverOrderStatus::PENDING);

        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();
        $this->assertNotNull($repository->findOneBySn('DO00000003'));

        self::getEntityManager()->remove($entity);
        self::getEntityManager()->flush();
        $this->assertNull($repository->findOneBySn('DO00000003'));
    }

    public function testFindOneBySn(): void
    {
        $repository = $this->getRepository();

        $entity = new DeliverOrder();
        $entity->setSn('DO00000004');
        $entity->setSourceType(SourceType::ORDER);
        $entity->setSourceId('12348');
        $entity->setStatus(DeliverOrderStatus::PENDING);

        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $found = $repository->findOneBySn('DO00000004');
        $this->assertNotNull($found);
        $this->assertEquals('12348', $found->getSourceId());

        $notFound = $repository->findOneBySn('NOTEXIST');
        $this->assertNull($notFound);
    }

    public function testFindBySource(): void
    {
        $repository = $this->getRepository();

        $entity1 = new DeliverOrder();
        $entity1->setSn('DO00000005');
        $entity1->setSourceType(SourceType::ORDER);
        $entity1->setSourceId('12349');
        $entity1->setStatus(DeliverOrderStatus::PENDING);

        $entity2 = new DeliverOrder();
        $entity2->setSn('DO00000006');
        $entity2->setSourceType(SourceType::ORDER);
        $entity2->setSourceId('12349');
        $entity2->setStatus(DeliverOrderStatus::SHIPPED);

        $repository->save($entity1, true);
        $repository->save($entity2, true);

        $results = $repository->findBySource(SourceType::ORDER->value, '12349');
        $this->assertCount(2, $results);

        $results = $repository->findBySource(SourceType::ORDER->value, 'NOTEXIST');
        $this->assertCount(0, $results);
    }

    public function testFindByStatus(): void
    {
        $repository = $this->getRepository();

        // 清理现有数据，确保测试隔离
        foreach ($repository->findAll() as $entity) {
            $repository->remove($entity);
        }
        self::getEntityManager()->flush();

        $entity1 = new DeliverOrder();
        $entity1->setSn('DO00000007');
        $entity1->setSourceType(SourceType::ORDER);
        $entity1->setSourceId('12350');
        $entity1->setStatus(DeliverOrderStatus::PENDING);

        $entity2 = new DeliverOrder();
        $entity2->setSn('DO00000008');
        $entity2->setSourceType(SourceType::ORDER);
        $entity2->setSourceId('12351');
        $entity2->setStatus(DeliverOrderStatus::PENDING);

        $entity3 = new DeliverOrder();
        $entity3->setSn('DO00000009');
        $entity3->setSourceType(SourceType::ORDER);
        $entity3->setSourceId('12352');
        $entity3->setStatus(DeliverOrderStatus::SHIPPED);

        $repository->save($entity1, true);
        $repository->save($entity2, true);
        $repository->save($entity3, true);

        $pending = $repository->findByStatus(DeliverOrderStatus::PENDING->value);
        $this->assertCount(2, $pending);

        $shipped = $repository->findByStatus(DeliverOrderStatus::SHIPPED->value);
        $this->assertCount(1, $shipped);
    }

    public function testCountByStatus(): void
    {
        $repository = $this->getRepository();

        // 清理现有数据，确保测试隔离
        foreach ($repository->findAll() as $entity) {
            $repository->remove($entity);
        }
        self::getEntityManager()->flush();

        $entity1 = new DeliverOrder();
        $entity1->setSn('DO00000010');
        $entity1->setSourceType(SourceType::ORDER);
        $entity1->setSourceId('12353');
        $entity1->setStatus(DeliverOrderStatus::PENDING);

        $entity2 = new DeliverOrder();
        $entity2->setSn('DO00000011');
        $entity2->setSourceType(SourceType::ORDER);
        $entity2->setSourceId('12354');
        $entity2->setStatus(DeliverOrderStatus::PENDING);

        $repository->save($entity1, true);
        $repository->save($entity2, true);

        $count = $repository->countByStatus(DeliverOrderStatus::PENDING->value);
        $this->assertEquals(2, $count);

        $count = $repository->countByStatus('completed');
        $this->assertEquals(0, $count);
    }

    public function testExistsBySn(): void
    {
        $repository = $this->getRepository();

        $entity = new DeliverOrder();
        $entity->setSn('DO00000012');
        $entity->setSourceType(SourceType::ORDER);
        $entity->setSourceId('12355');
        $entity->setStatus(DeliverOrderStatus::PENDING);

        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $this->assertTrue($repository->existsBySn('DO00000012'));
        $this->assertFalse($repository->existsBySn('NOTEXIST'));
    }

    public function testFindPendingOrdersOlderThan(): void
    {
        $repository = $this->getRepository();

        $oldDate = new \DateTimeImmutable('-5 days');
        $recentDate = new \DateTimeImmutable('-1 day');

        $entity1 = new DeliverOrder();
        $entity1->setSn('DO00000013');
        $entity1->setSourceType(SourceType::ORDER);
        $entity1->setSourceId('12356');
        $entity1->setStatus(DeliverOrderStatus::PENDING);
        $entity1->setCreateTime($oldDate);

        $entity2 = new DeliverOrder();
        $entity2->setSn('DO00000014');
        $entity2->setSourceType(SourceType::ORDER);
        $entity2->setSourceId('12357');
        $entity2->setStatus(DeliverOrderStatus::PENDING);
        $entity2->setCreateTime($recentDate);

        $entity3 = new DeliverOrder();
        $entity3->setSn('DO00000015');
        $entity3->setSourceType(SourceType::ORDER);
        $entity3->setSourceId('12358');
        $entity3->setStatus(DeliverOrderStatus::SHIPPED);
        $entity3->setCreateTime($oldDate);

        $repository->save($entity1, true);
        $repository->save($entity2, true);
        $repository->save($entity3, true);

        $cutoffDate = new \DateTimeImmutable('-3 days');
        $results = $repository->findPendingOrdersOlderThan($cutoffDate);

        $this->assertCount(1, $results);
        $this->assertEquals('DO00000013', $results[0]->getSn());
    }

    public function testFindRecentOrders(): void
    {
        $repository = $this->getRepository();

        for ($i = 1; $i <= 15; ++$i) {
            $entity = new DeliverOrder();
            $entity->setSn(sprintf('DO%08d', 100 + $i));
            $entity->setSourceType(SourceType::ORDER);
            $entity->setSourceId('123' . $i);
            $entity->setStatus(DeliverOrderStatus::PENDING);
            $entity->setCreateTime(new \DateTimeImmutable("-{$i} days"));
            self::getEntityManager()->persist($entity);
            self::getEntityManager()->flush();
        }

        $results = $repository->findRecentOrders(10);
        $this->assertCount(10, $results);

        $results = $repository->findRecentOrders(5);
        $this->assertCount(5, $results);
    }

    public function testFindByDateRange(): void
    {
        $repository = $this->getRepository();

        $entity1 = new DeliverOrder();
        $entity1->setSn('DO00000016');
        $entity1->setSourceType(SourceType::ORDER);
        $entity1->setSourceId('12359');
        $entity1->setStatus(DeliverOrderStatus::PENDING);
        $entity1->setCreateTime(new \DateTimeImmutable('2024-01-15'));

        $entity2 = new DeliverOrder();
        $entity2->setSn('DO00000017');
        $entity2->setSourceType(SourceType::ORDER);
        $entity2->setSourceId('12360');
        $entity2->setStatus(DeliverOrderStatus::PENDING);
        $entity2->setCreateTime(new \DateTimeImmutable('2024-01-20'));

        $entity3 = new DeliverOrder();
        $entity3->setSn('DO00000018');
        $entity3->setSourceType(SourceType::ORDER);
        $entity3->setSourceId('12361');
        $entity3->setStatus(DeliverOrderStatus::PENDING);
        $entity3->setCreateTime(new \DateTimeImmutable('2024-01-25'));

        $repository->save($entity1, true);
        $repository->save($entity2, true);
        $repository->save($entity3, true);

        $results = $repository->findByDateRange(
            new \DateTimeImmutable('2024-01-14'),
            new \DateTimeImmutable('2024-01-21')
        );

        $this->assertCount(2, $results);
    }

    public function testGetStatisticsByStatus(): void
    {
        $repository = $this->getRepository();

        // 清理现有数据，确保测试隔离
        foreach ($repository->findAll() as $entity) {
            $repository->remove($entity);
        }
        self::getEntityManager()->flush();

        $statuses = ['pending' => 3, 'shipped' => 2, 'received' => 1];
        $index = 100;

        foreach ($statuses as $status => $count) {
            for ($i = 0; $i < $count; ++$i) {
                $entity = new DeliverOrder();
                $entity->setSn(sprintf('DO%08d', $index++));
                $entity->setSourceType(SourceType::ORDER);
                $entity->setSourceId((string) $index);
                $entity->setStatus(DeliverOrderStatus::from($status));
                self::getEntityManager()->persist($entity);
                self::getEntityManager()->flush();
            }
        }

        $statistics = $repository->getStatisticsByStatus();

        $this->assertIsArray($statistics);
        $this->assertEquals(3, $statistics['pending']);
        $this->assertEquals(2, $statistics['shipped']);
        $this->assertEquals(1, $statistics['received']);
    }
}
