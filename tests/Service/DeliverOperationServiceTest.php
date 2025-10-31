<?php

namespace DeliverOrderBundle\Tests\Service;

use DeliverOrderBundle\DTO\ExtendedDeliveryOrderDTO;
use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use DeliverOrderBundle\Service\DeliverOperationService;
use Doctrine\Common\Collections\ArrayCollection;
use OrderCoreBundle\DTO\DeliveryOrderDTO;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Entity\Product;
use OrderCoreBundle\Entity\Sku;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @covers \DeliverOrderBundle\Service\DeliverOperationService
 * @internal
 */
#[CoversClass(DeliverOperationService::class)]
final class DeliverOperationServiceTest extends TestCase
{
    private DeliverOperationService $service;

    private DeliverOrderRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DeliverOrderRepository::class);
        $this->service = new DeliverOperationService($this->repository);
    }

    public function testNotifyShipment(): void
    {
        $contract = $this->createMock(Contract::class);

        $result = $this->service->notifyShipment($contract);

        $this->assertTrue($result);
    }

    public function testHasDeliveryRecordsReturnsTrueWhenRecordsExist(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $deliverOrder = $this->createDeliverOrder();

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with([
                'sourceType' => SourceType::CONTRACT,
                'sourceId' => '123',
            ])
            ->willReturn([$deliverOrder])
        ;

        $result = $this->service->hasDeliveryRecords($contract);

        $this->assertTrue($result);
    }

    public function testHasDeliveryRecordsReturnsFalseWhenNoRecords(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([])
        ;

        $result = $this->service->hasDeliveryRecords($contract);

        $this->assertFalse($result);
    }

    public function testMarkAllDeliveryAsReceived(): void
    {
        $contract = $this->createMock(Contract::class);
        $user = $this->createMock(UserInterface::class);
        $now = new \DateTimeImmutable();

        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([])
        ;

        $result = $this->service->markAllDeliveryAsReceived($contract, $user, $now);

        $this->assertTrue($result);
    }

    public function testGetDeliveryStatusSummary(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $deliverOrder1 = $this->createDeliverOrder();
        $deliverOrder1->setShippedTime(new \DateTimeImmutable());

        $deliverOrder2 = $this->createDeliverOrder();

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$deliverOrder1, $deliverOrder2])
        ;

        $result = $this->service->getDeliveryStatusSummary($contract);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('shipped_count', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('all_received', $result);
        $this->assertEquals(1, $result['shipped_count']);
        $this->assertEquals(2, $result['total_count']);
        $this->assertFalse($result['all_received']);
    }

    public function testGetDeliveryOrdersByContract(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $deliverOrder = $this->createDeliverOrder();

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$deliverOrder])
        ;

        $result = $this->service->getDeliveryOrdersByContract($contract);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(DeliveryOrderDTO::class, $result[0]);
    }

    public function testGetContractFirstDeliveryTime(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $firstTime = new \DateTimeImmutable('2023-01-01');
        $secondTime = new \DateTimeImmutable('2023-01-02');

        $deliverOrder1 = $this->createDeliverOrder();
        $deliverOrder1->setShippedTime($secondTime);

        $deliverOrder2 = $this->createDeliverOrder();
        $deliverOrder2->setShippedTime($firstTime);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$deliverOrder1, $deliverOrder2])
        ;

        $result = $this->service->getContractFirstDeliveryTime($contract);

        $this->assertEquals($firstTime, $result);
    }

    public function testGetContractLastDeliveryTime(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $firstTime = new \DateTimeImmutable('2023-01-01');
        $secondTime = new \DateTimeImmutable('2023-01-02');

        $deliverOrder1 = $this->createDeliverOrder();
        $deliverOrder1->setShippedTime($firstTime);

        $deliverOrder2 = $this->createDeliverOrder();
        $deliverOrder2->setShippedTime($secondTime);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$deliverOrder1, $deliverOrder2])
        ;

        $result = $this->service->getContractLastDeliveryTime($contract);

        $this->assertEquals($secondTime, $result);
    }

    public function testGetContractDeliveredQuantity(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $deliverOrder = $this->createDeliverOrder();
        $stock1 = $this->createDeliverStock();
        $stock1->setQuantity(5);
        $stock2 = $this->createDeliverStock();
        $stock2->setQuantity(3);

        $deliverOrder->addDeliverStock($stock1);
        $deliverOrder->addDeliverStock($stock2);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$deliverOrder])
        ;

        $result = $this->service->getContractDeliveredQuantity($contract);

        $this->assertEquals(8, $result);
    }

    public function testIsContractFullyDelivered(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->expects($this->once())
            ->method('getId')
            ->willReturn(123)
        ;

        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->once())
            ->method('getQuantity')
            ->willReturn(10)
        ;

        $contract->expects($this->once())
            ->method('getProducts')
            ->willReturn(new ArrayCollection([$orderProduct]))
        ;

        $deliverOrder = $this->createDeliverOrder();
        $stock = $this->createDeliverStock();
        $stock->setQuantity(10);
        $deliverOrder->addDeliverStock($stock);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$deliverOrder])
        ;

        $result = $this->service->isContractFullyDelivered($contract);

        $this->assertTrue($result);
    }

    public function testGetOrderProductDeliveredQuantityReturnsZeroWhenNoContract(): void
    {
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->once())
            ->method('getContract')
            ->willReturn(null)
        ;

        $result = $this->service->getOrderProductDeliveredQuantity($orderProduct);

        $this->assertEquals(0, $result);
    }

    public function testGetOrderProductDeliveredQuantityReturnsZeroWhenNoSku(): void
    {
        $contract = $this->createMock(Contract::class);
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->once())
            ->method('getContract')
            ->willReturn($contract)
        ;
        $orderProduct->expects($this->once())
            ->method('getSku')
            ->willReturn(null)
        ;

        $result = $this->service->getOrderProductDeliveredQuantity($orderProduct);

        $this->assertEquals(0, $result);
    }

    public function testGetOrderProductFirstDeliveryTimeReturnsNullWhenNoContract(): void
    {
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->once())
            ->method('getContract')
            ->willReturn(null)
        ;

        $result = $this->service->getOrderProductFirstDeliveryTime($orderProduct);

        $this->assertNull($result);
    }

    public function testGetOrderProductLastDeliveryTimeReturnsNullWhenNoSku(): void
    {
        $contract = $this->createMock(Contract::class);
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->expects($this->once())
            ->method('getContract')
            ->willReturn($contract)
        ;
        $orderProduct->expects($this->once())
            ->method('getSku')
            ->willReturn(null)
        ;

        $result = $this->service->getOrderProductLastDeliveryTime($orderProduct);

        $this->assertNull($result);
    }

    public function testGetDeliveryOrderByIdReturnsNull(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null)
        ;

        $result = $this->service->getDeliveryOrderById(123);

        $this->assertNull($result);
    }

    public function testGetDeliveryOrderByIdReturnsDTO(): void
    {
        $deliverOrder = $this->createDeliverOrder();

        $this->repository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($deliverOrder)
        ;

        $result = $this->service->getDeliveryOrderById(123);

        $this->assertInstanceOf(DeliveryOrderDTO::class, $result);
    }

    public function testGetExpressTrackingData(): void
    {
        $result = $this->service->getExpressTrackingData(123);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('1', $result['status']);
        $this->assertEquals('success', $result['message']);
    }

    private function createDeliverOrder(): DeliverOrder
    {
        $deliverOrder = new DeliverOrder();
        $deliverOrder->setSn('TEST001');
        $deliverOrder->setSourceType(SourceType::CONTRACT);
        $deliverOrder->setSourceId('123');
        $deliverOrder->setStatus(DeliverOrderStatus::PENDING);

        return $deliverOrder;
    }

    private function createDeliverStock(): DeliverStock
    {
        $stock = new DeliverStock();
        $stock->setSkuCode('SKU001');
        $stock->setQuantity(1);

        // 通过反射设置 ID 以避免类型错误
        $reflection = new \ReflectionClass($stock);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($stock, 1);

        return $stock;
    }
}
