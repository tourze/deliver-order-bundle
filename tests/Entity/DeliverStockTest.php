<?php

namespace DeliverOrderBundle\Tests\Entity;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverStock::class)]
final class DeliverStockTest extends AbstractEntityTestCase
{
    private DeliverStock $deliverStock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deliverStock = new DeliverStock();
    }

    protected function createEntity(): object
    {
        return new DeliverStock();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'skuId' => ['skuId', 'SKU001'],
            'skuCode' => ['skuCode', 'ABC123'],
            'skuName' => ['skuName', '商品A'],
            'quantity' => ['quantity', 5],
            'batchNo' => ['batchNo', 'BATCH20240101'],
            'serialNo' => ['serialNo', 'SN1234567890'],
            'remark' => ['remark', '测试备注信息'],
            'received' => ['received', true],
            'receivedTime' => ['receivedTime', new \DateTimeImmutable('2024-01-02 15:00:00')],
            'createTime' => ['createTime', new \DateTimeImmutable('2024-01-01 09:00:00')],
            'updateTime' => ['updateTime', new \DateTimeImmutable('2024-01-01 10:00:00')],
        ];
    }

    public function testEntityCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DeliverStock::class, $this->deliverStock);
    }

    public function testIdGetterSetter(): void
    {
        $this->assertNull($this->deliverStock->getId());
    }

    public function testDeliverOrderAssociation(): void
    {
        $this->assertNull($this->deliverStock->getDeliverOrder());

        $deliverOrder = new DeliverOrder();
        $this->deliverStock->setDeliverOrder($deliverOrder);

        $this->assertSame($deliverOrder, $this->deliverStock->getDeliverOrder());
    }

    public function testSkuInfoGetterSetter(): void
    {
        $this->assertNull($this->deliverStock->getSkuId());
        $this->assertNull($this->deliverStock->getSkuCode());
        $this->assertNull($this->deliverStock->getSkuName());

        $this->deliverStock->setSkuId('SKU001');
        $this->deliverStock->setSkuCode('ABC123');
        $this->deliverStock->setSkuName('商品A');

        $this->assertEquals('SKU001', $this->deliverStock->getSkuId());
        $this->assertEquals('ABC123', $this->deliverStock->getSkuCode());
        $this->assertEquals('商品A', $this->deliverStock->getSkuName());
    }

    public function testQuantityGetterSetter(): void
    {
        $this->assertEquals(1, $this->deliverStock->getQuantity());

        $this->deliverStock->setQuantity(5);
        $this->assertEquals(5, $this->deliverStock->getQuantity());

        $this->deliverStock->setQuantity(0);
        $this->assertEquals(0, $this->deliverStock->getQuantity());
    }

    public function testBatchNoGetterSetter(): void
    {
        $this->assertNull($this->deliverStock->getBatchNo());

        $this->deliverStock->setBatchNo('BATCH20240101');
        $this->assertEquals('BATCH20240101', $this->deliverStock->getBatchNo());
    }

    public function testSerialNoGetterSetter(): void
    {
        $this->assertNull($this->deliverStock->getSerialNo());

        $this->deliverStock->setSerialNo('SN1234567890');
        $this->assertEquals('SN1234567890', $this->deliverStock->getSerialNo());
    }

    public function testRemarkGetterSetter(): void
    {
        $this->assertNull($this->deliverStock->getRemark());

        $this->deliverStock->setRemark('测试备注信息');
        $this->assertEquals('测试备注信息', $this->deliverStock->getRemark());
    }

    public function testReceivedStatusGetterSetter(): void
    {
        $this->assertFalse($this->deliverStock->isReceived());

        $this->deliverStock->setReceived(true);
        $this->assertTrue($this->deliverStock->isReceived());

        $this->deliverStock->setReceived(false);
        $this->assertFalse($this->deliverStock->isReceived());
    }

    public function testReceivedTimeGetterSetter(): void
    {
        $this->assertNull($this->deliverStock->getReceivedTime());

        $receivedTime = new \DateTimeImmutable('2024-01-02 15:00:00');
        $this->deliverStock->setReceivedTime($receivedTime);

        $this->assertSame($receivedTime, $this->deliverStock->getReceivedTime());
    }

    public function testTimestampFields(): void
    {
        $this->assertNull($this->deliverStock->getCreateTime());
        $this->assertNull($this->deliverStock->getUpdateTime());

        $createTime = new \DateTimeImmutable('2024-01-01 09:00:00');
        $updateTime = new \DateTimeImmutable('2024-01-01 10:00:00');

        $this->deliverStock->setCreateTime($createTime);
        $this->deliverStock->setUpdateTime($updateTime);

        $this->assertSame($createTime, $this->deliverStock->getCreateTime());
        $this->assertSame($updateTime, $this->deliverStock->getUpdateTime());
    }

    public function testFluentInterface(): void
    {
        $deliverOrder = new DeliverOrder();

        // 测试单独调用每个setter方法（由于setter现在返回void，不再支持链式调用）
        $this->deliverStock->setDeliverOrder($deliverOrder);
        $this->deliverStock->setSkuId('SKU001');
        $this->deliverStock->setSkuCode('ABC123');
        $this->deliverStock->setSkuName('商品A');
        $this->deliverStock->setQuantity(3);
        $this->deliverStock->setBatchNo('BATCH001');
        $this->deliverStock->setSerialNo('SN001');
        $this->deliverStock->setRemark('测试');
        $this->deliverStock->setReceived(true);

        // 验证所有值都正确设置
        $this->assertSame($deliverOrder, $this->deliverStock->getDeliverOrder());
        $this->assertSame('SKU001', $this->deliverStock->getSkuId());
        $this->assertSame('ABC123', $this->deliverStock->getSkuCode());
        $this->assertSame('商品A', $this->deliverStock->getSkuName());
        $this->assertSame(3, $this->deliverStock->getQuantity());
        $this->assertSame('BATCH001', $this->deliverStock->getBatchNo());
        $this->assertSame('SN001', $this->deliverStock->getSerialNo());
        $this->assertSame('测试', $this->deliverStock->getRemark());
        $this->assertTrue($this->deliverStock->isReceived());
    }
}
