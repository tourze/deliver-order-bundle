<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Service;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Exception\DeliverException;
use DeliverOrderBundle\Service\DeliveryService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(DeliveryService::class)]
#[RunTestsInSeparateProcesses]
final class DeliveryServiceTest extends AbstractIntegrationTestCase
{
    private DeliveryService $deliveryService;

    protected function onSetUp(): void
    {
        // 基类方法，不需要额外的设置
    }

    public function testSyncDeliveryFromOmsSuccessfully(): void
    {
        /** @var DeliveryService $service */
        $service = self::getContainer()->get(DeliveryService::class);
        $this->assertInstanceOf(DeliveryService::class, $service);
        $this->deliveryService = $service;

        $deliveryData = [
            'deliverySn' => 'TEST-INTEGRATION-001',
            'sourceOrderId' => 'ORDER-123',
            'expressCompany' => '顺丰速运',
            'expressCode' => 'SF',
            'expressNumber' => 'SF123456789',
            'consigneeName' => '张三',
            'consigneePhone' => '13800138000',
            'consigneeAddress' => '北京市朝阳区测试地址',
            'shippedTime' => '2024-01-01 10:00:00',
            'deliveryItems' => [
                [
                    'sku' => 'SKU001',
                    'quantity' => 2,
                    'productName' => '测试商品',
                ],
            ],
        ];

        $result = $this->deliveryService->syncDeliveryFromOms($deliveryData);

        $this->assertInstanceOf(DeliverOrder::class, $result);
        $this->assertEquals('TEST-INTEGRATION-001', $result->getSn());
        $this->assertEquals(SourceType::OMS, $result->getSourceType());
        $this->assertEquals(DeliverOrderStatus::SHIPPED, $result->getStatus());
        $this->assertCount(1, $result->getDeliverStocks());

        // 清理测试数据
        self::getEntityManager()->remove($result);
        self::getEntityManager()->flush();
    }

    public function testSyncDeliveryFromOmsThrowsExceptionWhenDeliveryExists(): void
    {
        /** @var DeliveryService $service */
        $service = self::getContainer()->get(DeliveryService::class);
        $this->assertInstanceOf(DeliveryService::class, $service);
        $this->deliveryService = $service;

        // 先创建一个发货单
        $existingOrder = new DeliverOrder();
        $existingOrder->setSn('TEST-DUPLICATE-001');
        $existingOrder->setStatus(DeliverOrderStatus::PENDING);
        self::getEntityManager()->persist($existingOrder);
        self::getEntityManager()->flush();

        $deliveryData = [
            'deliverySn' => 'TEST-DUPLICATE-001',
            'sourceOrderId' => 'ORDER-123',
            'expressCompany' => '顺丰速运',
            'expressCode' => 'SF',
            'expressNumber' => 'SF123456789',
            'consigneeName' => '张三',
            'consigneePhone' => '13800138000',
            'consigneeAddress' => '北京市朝阳区测试地址',
            'deliveryItems' => [],
        ];

        $this->expectException(DeliverException::class);
        $this->expectExceptionMessage('发货单号已存在: TEST-DUPLICATE-001');

        try {
            $this->deliveryService->syncDeliveryFromOms($deliveryData);
        } finally {
            // 清理测试数据
            self::getEntityManager()->remove($existingOrder);
            self::getEntityManager()->flush();
        }
    }

    public function testSyncDeliveryFromOmsThrowsExceptionOnValidationFailure(): void
    {
        /** @var DeliveryService $service */
        $service = self::getContainer()->get(DeliveryService::class);
        $this->assertInstanceOf(DeliveryService::class, $service);
        $this->deliveryService = $service;

        // 使用无效数据触发验证失败（比如太长的字符串）
        $deliveryData = [
            'deliverySn' => str_repeat('X', 200), // 超过长度限制
            'sourceOrderId' => 'ORDER-123',
            'expressCompany' => '顺丰速运',
            'expressCode' => 'SF',
            'expressNumber' => 'SF123456789',
            'consigneeName' => '张三',
            'consigneePhone' => '13800138000',
            'consigneeAddress' => '北京市朝阳区测试地址',
            'deliveryItems' => [],
        ];

        $this->expectException(DeliverException::class);
        $this->expectExceptionMessageMatches('/数据验证失败/');

        $this->deliveryService->syncDeliveryFromOms($deliveryData);
    }

    public function testFindDeliveryBySn(): void
    {
        /** @var DeliveryService $service */
        $service = self::getContainer()->get(DeliveryService::class);
        $this->assertInstanceOf(DeliveryService::class, $service);
        $this->deliveryService = $service;

        // 创建测试数据
        $deliverOrder = new DeliverOrder();
        $deliverOrder->setSn('TEST-FIND-001');
        $deliverOrder->setStatus(DeliverOrderStatus::PENDING);
        self::getEntityManager()->persist($deliverOrder);
        self::getEntityManager()->flush();

        $result = $this->deliveryService->findDeliveryBySn('TEST-FIND-001');

        $this->assertInstanceOf(DeliverOrder::class, $result);
        $this->assertEquals('TEST-FIND-001', $result->getSn());

        // 清理测试数据
        self::getEntityManager()->remove($deliverOrder);
        self::getEntityManager()->flush();
    }

    public function testFindDeliveryBySnReturnsNull(): void
    {
        /** @var DeliveryService $service */
        $service = self::getContainer()->get(DeliveryService::class);
        $this->assertInstanceOf(DeliveryService::class, $service);
        $this->deliveryService = $service;

        $result = $this->deliveryService->findDeliveryBySn('NON-EXISTENT-SN');

        $this->assertNull($result);
    }

    public function testUpdateDeliveryStatusToReceived(): void
    {
        /** @var DeliveryService $service */
        $service = self::getContainer()->get(DeliveryService::class);
        $this->assertInstanceOf(DeliveryService::class, $service);
        $this->deliveryService = $service;

        $deliverOrder = new DeliverOrder();
        $deliverOrder->setSn('TEST-UPDATE-001');
        $deliverOrder->setStatus(DeliverOrderStatus::SHIPPED);
        self::getEntityManager()->persist($deliverOrder);
        self::getEntityManager()->flush();

        $this->deliveryService->updateDeliveryStatus($deliverOrder, DeliverOrderStatus::RECEIVED);

        $this->assertEquals(DeliverOrderStatus::RECEIVED, $deliverOrder->getStatus());
        $this->assertNotNull($deliverOrder->getReceivedTime());
        $this->assertNotNull($deliverOrder->getUpdateTime());

        // 清理测试数据
        self::getEntityManager()->remove($deliverOrder);
        self::getEntityManager()->flush();
    }
}
