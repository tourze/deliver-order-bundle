<?php

namespace DeliverOrderBundle\Tests\Repository;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Repository\DeliverStockRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverStockRepository::class)]
#[RunTestsInSeparateProcesses]
final class DeliverStockRepositoryTest extends AbstractRepositoryTestCase
{
    private DeliverStockRepository $repository;

    protected function getRepository(): DeliverStockRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $deliverOrder = new DeliverOrder();
        $deliverOrder->setSn('DO' . uniqid());
        $deliverOrder->setSourceType(SourceType::ORDER);
        $deliverOrder->setSourceId(uniqid());
        $deliverOrder->setStatus(DeliverOrderStatus::PENDING);
        self::getEntityManager()->persist($deliverOrder);
        self::getEntityManager()->flush();

        $entity = new DeliverStock();
        $entity->setDeliverOrder($deliverOrder);
        $entity->setSkuId('SKU' . uniqid());
        $entity->setQuantity(1);

        return $entity;
    }

    protected function onSetUp(): void
    {
        $container = self::getContainer();
        /** @var DeliverStockRepository $repository */
        $repository = $container->get(DeliverStockRepository::class);
        $this->assertInstanceOf(DeliverStockRepository::class, $repository);
        $this->repository = $repository;

        $currentTest = $this->name();
        if ('testFindAllWhenNoRecordsExistShouldReturnEmptyArray' === $currentTest) {
            // 清理所有数据以确保测试在空数据库上运行
            $em = self::getEntityManager();
            $em->createQuery('DELETE FROM ' . DeliverStock::class)->execute();
        }
    }

    public function testFindDeliverStockById(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $entity = new DeliverStock();
        $entity->setDeliverOrder($deliverOrder);
        $entity->setSkuId('SKU001');
        $entity->setSkuCode('CODE001');
        $entity->setSkuName('测试商品');
        $entity->setQuantity(10);

        self::getEntityManager()->persist($entity);
        self::getEntityManager()->flush();

        $found = $repository->find($entity->getId());

        $this->assertNotNull($found);
        $this->assertEquals('SKU001', $found->getSkuId());
    }

    public function testSaveMethod(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $entity = new DeliverStock();
        $entity->setDeliverOrder($deliverOrder);
        $entity->setSkuId('SKU002');
        $entity->setSkuCode('CODE002');
        $entity->setSkuName('测试商品2');
        $entity->setQuantity(5);

        $repository->save($entity, true);

        $found = $repository->find($entity->getId());
        $this->assertNotNull($found);
        $this->assertEquals('CODE002', $found->getSkuCode());
    }

    public function testRemoveMethod(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $entity = new DeliverStock();
        $entity->setDeliverOrder($deliverOrder);
        $entity->setSkuId('SKU003');
        $entity->setSkuCode('CODE003');
        $entity->setSkuName('测试商品3');
        $entity->setQuantity(3);

        $repository->save($entity, true);
        $id = $entity->getId();
        $this->assertNotNull($repository->find($id));

        $repository->remove($entity, true);
        $this->assertNull($repository->find($id));
    }

    public function testFindByDeliverOrder(): void
    {
        $repository = $this->getRepository();
        $deliverOrder1 = $this->createDeliverOrder('DO001');
        $deliverOrder2 = $this->createDeliverOrder('DO002');

        $stock1 = new DeliverStock();
        $stock1->setDeliverOrder($deliverOrder1);
        $stock1->setSkuId('SKU004');
        $stock1->setQuantity(1);

        $stock2 = new DeliverStock();
        $stock2->setDeliverOrder($deliverOrder1);
        $stock2->setSkuId('SKU005');
        $stock2->setQuantity(2);

        $stock3 = new DeliverStock();
        $stock3->setDeliverOrder($deliverOrder2);
        $stock3->setSkuId('SKU006');
        $stock3->setQuantity(3);

        $repository->save($stock1, true);
        $repository->save($stock2, true);
        $repository->save($stock3, true);

        $results = $repository->findByDeliverOrder($deliverOrder1);
        $this->assertCount(2, $results);

        $results = $repository->findByDeliverOrder($deliverOrder2);
        $this->assertCount(1, $results);
    }

    public function testFindBySkuId(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $stock1 = new DeliverStock();
        $stock1->setDeliverOrder($deliverOrder);
        $stock1->setSkuId('SKU007');
        $stock1->setQuantity(5);

        $stock2 = new DeliverStock();
        $stock2->setDeliverOrder($deliverOrder);
        $stock2->setSkuId('SKU007');
        $stock2->setQuantity(10);

        $stock3 = new DeliverStock();
        $stock3->setDeliverOrder($deliverOrder);
        $stock3->setSkuId('SKU008');
        $stock3->setQuantity(15);

        $repository->save($stock1, true);
        $repository->save($stock2, true);
        $repository->save($stock3, true);

        $results = $repository->findBySkuId('SKU007');
        $this->assertCount(2, $results);

        $results = $repository->findBySkuId('SKU008');
        $this->assertCount(1, $results);
    }

    public function testFindUnreceivedStocks(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $stock1 = new DeliverStock();
        $stock1->setDeliverOrder($deliverOrder);
        $stock1->setSkuId('SKU009');
        $stock1->setQuantity(1);
        $stock1->setReceived(false);

        $stock2 = new DeliverStock();
        $stock2->setDeliverOrder($deliverOrder);
        $stock2->setSkuId('SKU010');
        $stock2->setQuantity(2);
        $stock2->setReceived(true);

        $stock3 = new DeliverStock();
        $stock3->setDeliverOrder($deliverOrder);
        $stock3->setSkuId('SKU011');
        $stock3->setQuantity(3);
        $stock3->setReceived(false);

        $repository->save($stock1, true);
        $repository->save($stock2, true);
        $repository->save($stock3, true);

        $results = $repository->findUnreceivedStocks();
        $this->assertCount(3, $results); // 2 from test + 1 from fixtures
    }

    public function testFindByBatchNo(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $stock1 = new DeliverStock();
        $stock1->setDeliverOrder($deliverOrder);
        $stock1->setSkuId('SKU012');
        $stock1->setQuantity(1);
        $stock1->setBatchNo('BATCH001');

        $stock2 = new DeliverStock();
        $stock2->setDeliverOrder($deliverOrder);
        $stock2->setSkuId('SKU013');
        $stock2->setQuantity(2);
        $stock2->setBatchNo('BATCH001');

        $stock3 = new DeliverStock();
        $stock3->setDeliverOrder($deliverOrder);
        $stock3->setSkuId('SKU014');
        $stock3->setQuantity(3);
        $stock3->setBatchNo('BATCH002');

        $repository->save($stock1, true);
        $repository->save($stock2, true);
        $repository->save($stock3, true);

        $results = $repository->findByBatchNo('BATCH001');
        $this->assertCount(2, $results);

        $results = $repository->findByBatchNo('BATCH002');
        $this->assertCount(1, $results);
    }

    public function testFindBySerialNo(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $stock1 = new DeliverStock();
        $stock1->setDeliverOrder($deliverOrder);
        $stock1->setSkuId('SKU015');
        $stock1->setQuantity(1);
        $stock1->setSerialNo('SN001');

        $stock2 = new DeliverStock();
        $stock2->setDeliverOrder($deliverOrder);
        $stock2->setSkuId('SKU016');
        $stock2->setQuantity(1);
        $stock2->setSerialNo('SN002');

        $repository->save($stock1, true);
        $repository->save($stock2, true);

        $results = $repository->findBySerialNo('SN001');
        $this->assertCount(1, $results);
        $this->assertEquals('SKU015', $results[0]->getSkuId());
    }

    public function testCountByDeliverOrder(): void
    {
        $repository = $this->getRepository();
        $deliverOrder1 = $this->createDeliverOrder('DO003');
        $deliverOrder2 = $this->createDeliverOrder('DO004');

        for ($i = 1; $i <= 3; ++$i) {
            $stock = new DeliverStock();
            $stock->setDeliverOrder($deliverOrder1);
            $stock->setSkuId('SKU' . (100 + $i));
            $stock->setQuantity($i);
            $repository->save($stock, true);
        }

        for ($i = 1; $i <= 2; ++$i) {
            $stock = new DeliverStock();
            $stock->setDeliverOrder($deliverOrder2);
            $stock->setSkuId('SKU' . (200 + $i));
            $stock->setQuantity($i);
            $repository->save($stock, true);
        }

        $this->assertEquals(3, $repository->countByDeliverOrder($deliverOrder1));
        $this->assertEquals(2, $repository->countByDeliverOrder($deliverOrder2));
    }

    public function testGetTotalQuantityByDeliverOrder(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder('DO005');

        $stock1 = new DeliverStock();
        $stock1->setDeliverOrder($deliverOrder);
        $stock1->setSkuId('SKU017');
        $stock1->setQuantity(10);

        $stock2 = new DeliverStock();
        $stock2->setDeliverOrder($deliverOrder);
        $stock2->setSkuId('SKU018');
        $stock2->setQuantity(15);

        $stock3 = new DeliverStock();
        $stock3->setDeliverOrder($deliverOrder);
        $stock3->setSkuId('SKU019');
        $stock3->setQuantity(5);

        $repository->save($stock1, true);
        $repository->save($stock2, true);
        $repository->save($stock3, true);

        $total = $repository->getTotalQuantityByDeliverOrder($deliverOrder);
        $this->assertEquals(30, $total);
    }

    public function testFindReceivedInDateRange(): void
    {
        $repository = $this->getRepository();
        $deliverOrder = $this->createDeliverOrder();

        $stock1 = new DeliverStock();
        $stock1->setDeliverOrder($deliverOrder);
        $stock1->setSkuId('SKU020');
        $stock1->setQuantity(1);
        $stock1->setReceived(true);
        $stock1->setReceivedTime(new \DateTimeImmutable('2024-01-15'));

        $stock2 = new DeliverStock();
        $stock2->setDeliverOrder($deliverOrder);
        $stock2->setSkuId('SKU021');
        $stock2->setQuantity(2);
        $stock2->setReceived(true);
        $stock2->setReceivedTime(new \DateTimeImmutable('2024-01-20'));

        $stock3 = new DeliverStock();
        $stock3->setDeliverOrder($deliverOrder);
        $stock3->setSkuId('SKU022');
        $stock3->setQuantity(3);
        $stock3->setReceived(true);
        $stock3->setReceivedTime(new \DateTimeImmutable('2024-01-25'));

        $stock4 = new DeliverStock();
        $stock4->setDeliverOrder($deliverOrder);
        $stock4->setSkuId('SKU023');
        $stock4->setQuantity(4);
        $stock4->setReceived(false);

        $repository->save($stock1, true);
        $repository->save($stock2, true);
        $repository->save($stock3, true);
        $repository->save($stock4, true);

        $results = $repository->findReceivedInDateRange(
            new \DateTimeImmutable('2024-01-14'),
            new \DateTimeImmutable('2024-01-21')
        );

        $this->assertCount(2, $results);
    }

    private function createDeliverOrder(string $sn = 'DO000001'): DeliverOrder
    {
        $deliverOrder = new DeliverOrder();
        $deliverOrder->setSn($sn);
        $deliverOrder->setSourceType(SourceType::ORDER);
        $deliverOrder->setSourceId('12345');
        $deliverOrder->setStatus(DeliverOrderStatus::PENDING);

        self::getEntityManager()->persist($deliverOrder);
        self::getEntityManager()->flush();

        return $deliverOrder;
    }
}
