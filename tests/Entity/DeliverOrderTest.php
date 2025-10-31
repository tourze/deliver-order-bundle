<?php

namespace DeliverOrderBundle\Tests\Entity;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverOrder::class)]
final class DeliverOrderTest extends AbstractEntityTestCase
{
    private DeliverOrder $deliverOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deliverOrder = new DeliverOrder();
    }

    protected function createEntity(): object
    {
        return new DeliverOrder();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'sn' => ['sn', 'DO202401010001'],
            'sourceType' => ['sourceType', SourceType::ORDER],
            'sourceId' => ['sourceId', '12345'],
            'expressCompany' => ['expressCompany', '顺丰速运'],
            'expressCode' => ['expressCode', 'SF'],
            'expressNumber' => ['expressNumber', 'SF1234567890'],
            'consigneeName' => ['consigneeName', '张三'],
            'consigneePhone' => ['consigneePhone', '13800138000'],
            'consigneeAddress' => ['consigneeAddress', '北京市朝阳区xxx街道'],
            'consigneeRemark' => ['consigneeRemark', '请放门卫处'],
            'status' => ['status', DeliverOrderStatus::PENDING],
            'shippedTime' => ['shippedTime', new \DateTimeImmutable('2024-01-01 10:00:00')],
            'shippedBy' => ['shippedBy', 'user123'],
            'receivedTime' => ['receivedTime', new \DateTimeImmutable('2024-01-02 15:00:00')],
            'receivedBy' => ['receivedBy', 'customer456'],
            'rejectedTime' => ['rejectedTime', new \DateTimeImmutable('2024-01-02 16:00:00')],
            'rejectedBy' => ['rejectedBy', 'customer789'],
            'rejectReason' => ['rejectReason', '商品破损'],
            'createTime' => ['createTime', new \DateTimeImmutable('2024-01-01 09:00:00')],
            'updateTime' => ['updateTime', new \DateTimeImmutable('2024-01-01 10:00:00')],
            'createdBy' => ['createdBy', 'admin'],
            'updatedBy' => ['updatedBy', 'operator'],
        ];
    }

    public function testEntityCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DeliverOrder::class, $this->deliverOrder);
    }

    public function testIdGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getId());

        // ID is usually set by Doctrine, we'll test the getter returns null for new entity
        $this->assertNull($this->deliverOrder->getId());
    }

    public function testSnGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getSn());

        $this->deliverOrder->setSn('D202401010001');
        $this->assertEquals('D202401010001', $this->deliverOrder->getSn());
    }

    public function testSourceTypeAndIdGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getSourceType());
        $this->assertNull($this->deliverOrder->getSourceId());

        $this->deliverOrder->setSourceType(SourceType::ORDER);
        $this->deliverOrder->setSourceId('12345');

        $this->assertEquals(SourceType::ORDER, $this->deliverOrder->getSourceType());
        $this->assertEquals('12345', $this->deliverOrder->getSourceId());
    }

    public function testExpressInfoGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getExpressCompany());
        $this->assertNull($this->deliverOrder->getExpressCode());
        $this->assertNull($this->deliverOrder->getExpressNumber());

        $this->deliverOrder->setExpressCompany('顺丰速运');
        $this->deliverOrder->setExpressCode('SF');
        $this->deliverOrder->setExpressNumber('SF1234567890');

        $this->assertEquals('顺丰速运', $this->deliverOrder->getExpressCompany());
        $this->assertEquals('SF', $this->deliverOrder->getExpressCode());
        $this->assertEquals('SF1234567890', $this->deliverOrder->getExpressNumber());
    }

    public function testConsigneeInfoGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getConsigneeName());
        $this->assertNull($this->deliverOrder->getConsigneePhone());
        $this->assertNull($this->deliverOrder->getConsigneeAddress());
        $this->assertNull($this->deliverOrder->getConsigneeRemark());

        $this->deliverOrder->setConsigneeName('张三');
        $this->deliverOrder->setConsigneePhone('13800138000');
        $this->deliverOrder->setConsigneeAddress('北京市朝阳区xxx街道');
        $this->deliverOrder->setConsigneeRemark('请放门卫处');

        $this->assertEquals('张三', $this->deliverOrder->getConsigneeName());
        $this->assertEquals('13800138000', $this->deliverOrder->getConsigneePhone());
        $this->assertEquals('北京市朝阳区xxx街道', $this->deliverOrder->getConsigneeAddress());
        $this->assertEquals('请放门卫处', $this->deliverOrder->getConsigneeRemark());
    }

    public function testStatusGetterSetter(): void
    {
        $this->assertEquals(DeliverOrderStatus::PENDING, $this->deliverOrder->getStatus());

        $this->deliverOrder->setStatus(DeliverOrderStatus::SHIPPED);
        $this->assertEquals(DeliverOrderStatus::SHIPPED, $this->deliverOrder->getStatus());

        $this->deliverOrder->setStatus(DeliverOrderStatus::RECEIVED);
        $this->assertEquals(DeliverOrderStatus::RECEIVED, $this->deliverOrder->getStatus());

        $this->deliverOrder->setStatus(DeliverOrderStatus::REJECTED);
        $this->assertEquals(DeliverOrderStatus::REJECTED, $this->deliverOrder->getStatus());
    }

    public function testShippedInfoGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getShippedTime());
        $this->assertNull($this->deliverOrder->getShippedBy());

        $shippedTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $this->deliverOrder->setShippedTime($shippedTime);
        $this->deliverOrder->setShippedBy('user123');

        $this->assertSame($shippedTime, $this->deliverOrder->getShippedTime());
        $this->assertEquals('user123', $this->deliverOrder->getShippedBy());
    }

    public function testReceivedInfoGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getReceivedTime());
        $this->assertNull($this->deliverOrder->getReceivedBy());

        $receivedTime = new \DateTimeImmutable('2024-01-02 15:00:00');
        $this->deliverOrder->setReceivedTime($receivedTime);
        $this->deliverOrder->setReceivedBy('customer456');

        $this->assertSame($receivedTime, $this->deliverOrder->getReceivedTime());
        $this->assertEquals('customer456', $this->deliverOrder->getReceivedBy());
    }

    public function testRejectedInfoGetterSetter(): void
    {
        $this->assertNull($this->deliverOrder->getRejectedTime());
        $this->assertNull($this->deliverOrder->getRejectedBy());
        $this->assertNull($this->deliverOrder->getRejectReason());

        $rejectedTime = new \DateTimeImmutable('2024-01-02 16:00:00');
        $this->deliverOrder->setRejectedTime($rejectedTime);
        $this->deliverOrder->setRejectedBy('customer789');
        $this->deliverOrder->setRejectReason('商品破损');

        $this->assertSame($rejectedTime, $this->deliverOrder->getRejectedTime());
        $this->assertEquals('customer789', $this->deliverOrder->getRejectedBy());
        $this->assertEquals('商品破损', $this->deliverOrder->getRejectReason());
    }

    public function testDeliverStocksCollection(): void
    {
        $stocks = $this->deliverOrder->getDeliverStocks();
        $this->assertInstanceOf(Collection::class, $stocks);
        $this->assertTrue($stocks->isEmpty());

        $stock1 = new DeliverStock();
        $stock2 = new DeliverStock();

        $this->deliverOrder->addDeliverStock($stock1);
        $this->assertCount(1, $this->deliverOrder->getDeliverStocks());
        $this->assertTrue($this->deliverOrder->getDeliverStocks()->contains($stock1));

        $this->deliverOrder->addDeliverStock($stock2);
        $this->assertCount(2, $this->deliverOrder->getDeliverStocks());

        $this->deliverOrder->removeDeliverStock($stock1);
        $this->assertCount(1, $this->deliverOrder->getDeliverStocks());
        $this->assertFalse($this->deliverOrder->getDeliverStocks()->contains($stock1));
        $this->assertTrue($this->deliverOrder->getDeliverStocks()->contains($stock2));
    }

    public function testTimestampFields(): void
    {
        $this->assertNull($this->deliverOrder->getCreateTime());
        $this->assertNull($this->deliverOrder->getUpdateTime());

        $createTime = new \DateTimeImmutable('2024-01-01 09:00:00');
        $updateTime = new \DateTimeImmutable('2024-01-01 10:00:00');

        $this->deliverOrder->setCreateTime($createTime);
        $this->deliverOrder->setUpdateTime($updateTime);

        $this->assertSame($createTime, $this->deliverOrder->getCreateTime());
        $this->assertSame($updateTime, $this->deliverOrder->getUpdateTime());
    }

    public function testCreatedByAndUpdatedByFields(): void
    {
        $this->assertNull($this->deliverOrder->getCreatedBy());
        $this->assertNull($this->deliverOrder->getUpdatedBy());

        $this->deliverOrder->setCreatedBy('admin');
        $this->deliverOrder->setUpdatedBy('operator');

        $this->assertEquals('admin', $this->deliverOrder->getCreatedBy());
        $this->assertEquals('operator', $this->deliverOrder->getUpdatedBy());
    }
}
