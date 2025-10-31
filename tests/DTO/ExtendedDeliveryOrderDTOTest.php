<?php

namespace DeliverOrderBundle\Tests\DTO;

use DeliverOrderBundle\DTO\ExtendedDeliveryOrderDTO;
use OrderCoreBundle\DTO\DeliveryStockDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DeliverOrderBundle\DTO\ExtendedDeliveryOrderDTO
 * @internal
 */
#[CoversClass(ExtendedDeliveryOrderDTO::class)]
final class ExtendedDeliveryOrderDTOTest extends TestCase
{
    private ExtendedDeliveryOrderDTO $dto;

    protected function setUp(): void
    {
        $this->dto = new ExtendedDeliveryOrderDTO();
    }

    public function testSnGetterSetter(): void
    {
        $sn = 'TEST001';
        $this->dto->setSn($sn);
        $this->assertEquals($sn, $this->dto->getSn());
    }

    public function testSourceTypeGetterSetter(): void
    {
        $sourceType = 'contract';
        $this->dto->setSourceType($sourceType);
        $this->assertEquals($sourceType, $this->dto->getSourceType());
    }

    public function testSourceIdGetterSetter(): void
    {
        $sourceId = '123';
        $this->dto->setSourceId($sourceId);
        $this->assertEquals($sourceId, $this->dto->getSourceId());
    }

    public function testShippedTimeGetterSetter(): void
    {
        $shippedTime = new \DateTimeImmutable();
        $this->dto->setShippedTime($shippedTime);
        $this->assertEquals($shippedTime, $this->dto->getShippedTime());
    }

    public function testDeliverStocksGetterSetter(): void
    {
        $stock1 = new DeliveryStockDTO(1, 'SKU001', 5, 'pending');
        $stock2 = new DeliveryStockDTO(2, 'SKU002', 3, 'received');
        $stocks = [$stock1, $stock2];

        $this->dto->setDeliverStocks($stocks);
        $this->assertEquals($stocks, $this->dto->getDeliverStocks());
        $this->assertCount(2, $this->dto->getDeliverStocks());
    }

    public function testFluentInterface(): void
    {
        $sn = 'TEST002';
        $sourceType = 'order';
        $sourceId = '456';
        $shippedTime = new \DateTimeImmutable();
        $stocks = [new DeliveryStockDTO(1, 'SKU001', 1, 'pending')];

        // 测试单独调用每个setter方法（由于setter现在返回void，不再支持链式调用）
        $this->dto->setSn($sn);
        $this->dto->setSourceType($sourceType);
        $this->dto->setSourceId($sourceId);
        $this->dto->setShippedTime($shippedTime);
        $this->dto->setDeliverStocks($stocks);

        // 验证所有值都正确设置
        $this->assertSame($sn, $this->dto->getSn());
        $this->assertSame($sourceType, $this->dto->getSourceType());
        $this->assertSame($sourceId, $this->dto->getSourceId());
        $this->assertSame($shippedTime, $this->dto->getShippedTime());
        $this->assertSame($stocks, $this->dto->getDeliverStocks());
        $this->assertEquals($sn, $this->dto->getSn());
        $this->assertEquals($sourceType, $this->dto->getSourceType());
        $this->assertEquals($sourceId, $this->dto->getSourceId());
        $this->assertEquals($shippedTime, $this->dto->getShippedTime());
        $this->assertEquals($stocks, $this->dto->getDeliverStocks());
    }

    public function testInheritsFromDeliveryOrderDTO(): void
    {
        $this->dto->setId('123');
        $this->dto->setExpressCompany('SF Express');
        $this->dto->setExpressNumber('SF1234567890');

        $this->assertEquals('123', $this->dto->getId());
        $this->assertEquals('SF Express', $this->dto->getExpressCompany());
        $this->assertEquals('SF1234567890', $this->dto->getExpressNumber());
    }

    public function testDefaultValues(): void
    {
        $dto = new ExtendedDeliveryOrderDTO();

        $this->assertNull($dto->getSn());
        $this->assertNull($dto->getSourceType());
        $this->assertNull($dto->getSourceId());
        $this->assertNull($dto->getShippedTime());
        $this->assertEmpty($dto->getDeliverStocks());
    }
}
