<?php

namespace DeliverOrderBundle\Tests\Service;

use DeliverOrderBundle\DTO\ExtendedDeliveryOrderDTO;
use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Service\DeliveryOrdersCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DeliveryOrdersCollection::class)]
final class DeliveryOrdersCollectionTest extends TestCase
{
    private DeliveryOrdersCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new DeliveryOrdersCollection();
    }

    public function testConvertToExtendedDTO(): void
    {
        $deliverOrder = new DeliverOrder();
        $this->setPrivateProperty($deliverOrder, 'id', 123);
        $deliverOrder->setSn('SN123456');
        $deliverOrder->setSourceType(SourceType::CONTRACT);
        $deliverOrder->setSourceId('456');
        $deliverOrder->setExpressCompany('顺丰速运');
        $deliverOrder->setExpressNumber('SF123456789');
        $deliverOrder->setShippedTime(new \DateTime('2024-01-01 10:00:00'));

        $deliverStock = new DeliverStock();
        $this->setPrivateProperty($deliverStock, 'id', 789);
        $deliverStock->setSkuCode('SKU001');
        $deliverStock->setQuantity(5);
        $deliverStock->setReceived(false);

        $deliverOrder->addDeliverStock($deliverStock);

        $dto = $this->collection->convertToExtendedDTO($deliverOrder);

        $this->assertInstanceOf(ExtendedDeliveryOrderDTO::class, $dto);
        $this->assertSame('123', $dto->getId());
        $this->assertSame('SN123456', $dto->getSn());
        $this->assertSame('contract', $dto->getSourceType());
        $this->assertSame('456', $dto->getSourceId());
        $this->assertSame('顺丰速运', $dto->getExpressCompany());
        $this->assertSame('SF123456789', $dto->getExpressNumber());
        $this->assertEquals(new \DateTime('2024-01-01 10:00:00'), $dto->getShippedTime());

        $deliverStocks = $dto->getDeliverStocks();
        $this->assertCount(1, $deliverStocks);
        $this->assertSame(789, $deliverStocks[0]->getId());
        $this->assertSame('SKU001', $deliverStocks[0]->getSkuCode());
        $this->assertSame(5, $deliverStocks[0]->getQuantity());
        $this->assertSame('pending', $deliverStocks[0]->getStatus());
    }

    public function testConvertToExtendedDTOWithReceivedStock(): void
    {
        $deliverOrder = new DeliverOrder();
        $this->setPrivateProperty($deliverOrder, 'id', 123);
        $deliverOrder->setSn('SN123456');
        $deliverOrder->setSourceType(SourceType::ORDER);
        $deliverOrder->setSourceId('789');

        $deliverStock = new DeliverStock();
        $this->setPrivateProperty($deliverStock, 'id', 456);
        $deliverStock->setSkuCode('SKU002');
        $deliverStock->setQuantity(3);
        $deliverStock->setReceived(true);

        $deliverOrder->addDeliverStock($deliverStock);

        $dto = $this->collection->convertToExtendedDTO($deliverOrder);

        $deliverStocks = $dto->getDeliverStocks();
        $this->assertCount(1, $deliverStocks);
        $this->assertSame('received', $deliverStocks[0]->getStatus());
    }

    public function testCountShippedOrdersWithNoShippedOrders(): void
    {
        $orders = [
            $this->createOrderDTO(null),
            $this->createOrderDTO(null),
        ];

        $result = $this->collection->countShippedOrders($orders);

        $this->assertSame(0, $result);
    }

    public function testCountShippedOrdersWithSomeShippedOrders(): void
    {
        $orders = [
            $this->createOrderDTO(new \DateTime('2024-01-01')),
            $this->createOrderDTO(null),
            $this->createOrderDTO(new \DateTime('2024-01-02')),
        ];

        $result = $this->collection->countShippedOrders($orders);

        $this->assertSame(2, $result);
    }

    public function testCountShippedOrdersWithAllShippedOrders(): void
    {
        $orders = [
            $this->createOrderDTO(new \DateTime('2024-01-01')),
            $this->createOrderDTO(new \DateTime('2024-01-02')),
            $this->createOrderDTO(new \DateTime('2024-01-03')),
        ];

        $result = $this->collection->countShippedOrders($orders);

        $this->assertSame(3, $result);
    }

    private function createOrderDTO(?\DateTimeInterface $shippedTime): ExtendedDeliveryOrderDTO
    {
        $dto = new ExtendedDeliveryOrderDTO();
        $dto->setShippedTime($shippedTime);

        return $dto;
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }
}
