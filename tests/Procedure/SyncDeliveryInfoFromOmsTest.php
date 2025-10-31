<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Procedure;

use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Procedure\SyncDeliveryInfoFromOms;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(SyncDeliveryInfoFromOms::class)]
#[RunTestsInSeparateProcesses]
final class SyncDeliveryInfoFromOmsTest extends AbstractProcedureTestCase
{
    protected function onSetUp(): void
    {
        // Setup logic if needed
    }

    public function testExecute(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);
        /** @var DeliverOrderRepository $deliverOrderRepository */
        $deliverOrderRepository = self::getContainer()->get(DeliverOrderRepository::class);

        $procedure->deliverySn = 'TEST-EXECUTE-' . uniqid();
        $procedure->sourceOrderId = 'ORDER-001';
        $procedure->expressCompany = '顺丰快递';
        $procedure->expressCode = 'SF';
        $procedure->expressNumber = 'SF' . uniqid();
        $procedure->consigneeName = '张三';
        $procedure->consigneePhone = '13800138000';
        $procedure->consigneeAddress = '上海市浦东新区测试地址';
        $procedure->deliveryItems = [
            [
                'sku' => 'SKU001',
                'quantity' => 1,
                'productName' => '测试产品',
            ],
        ];

        $result = $procedure->execute();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('发货信息同步成功', $result['message']);
        $this->assertArrayHasKey('deliveryOrderId', $result);

        $deliverOrder = $deliverOrderRepository->find($result['deliveryOrderId']);
        $this->assertNotNull($deliverOrder);
        $this->assertEquals($procedure->deliverySn, $deliverOrder->getSn());
    }

    public function testSyncDeliveryInfoFromOmsSuccess(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);
        /** @var DeliverOrderRepository $deliverOrderRepository */
        $deliverOrderRepository = self::getContainer()->get(DeliverOrderRepository::class);

        $procedure->deliverySn = 'TEST-' . uniqid();
        $procedure->sourceOrderId = 'ORDER-001';
        $procedure->expressCompany = '顺丰快递';
        $procedure->expressCode = 'SF';
        $procedure->expressNumber = 'SF' . uniqid();
        $procedure->consigneeName = '张三';
        $procedure->consigneePhone = '13800138000';
        $procedure->consigneeAddress = '上海市浦东新区测试地址';
        $procedure->consigneeRemark = '请轻拿轻放';
        $procedure->shippedTime = '2024-01-01 10:00:00';
        $procedure->shippedBy = 'OMS操作员';
        $procedure->deliveryItems = [
            [
                'sku' => 'SKU001',
                'quantity' => 2,
                'productName' => '测试产品1',
                'productCode' => 'PROD001',
                'batchNo' => 'BATCH001',
                'remark' => '备注1',
            ],
            [
                'sku' => 'SKU002',
                'quantity' => 1,
                'productName' => '测试产品2',
            ],
        ];

        $request = new JsonRpcRequest();
        $request->setMethod('SyncDeliveryInfoFromOms');
        /** @var array{success: bool, message: string, deliveryOrderId: string} $result */
        $result = $procedure->__invoke($request);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('发货信息同步成功', $result['message']);
        $this->assertArrayHasKey('deliveryOrderId', $result);

        $deliverOrder = $deliverOrderRepository->find($result['deliveryOrderId']);
        $this->assertNotNull($deliverOrder);
        $this->assertEquals($procedure->deliverySn, $deliverOrder->getSn());
        $this->assertEquals(SourceType::OMS, $deliverOrder->getSourceType());
        $this->assertEquals(DeliverOrderStatus::SHIPPED, $deliverOrder->getStatus());
        $this->assertCount(2, $deliverOrder->getDeliverStocks());
    }

    public function testSyncDeliveryInfoFromOmsDuplicateSn(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);
        $sn = 'TEST-' . uniqid();

        $procedure->deliverySn = $sn;
        $procedure->sourceOrderId = 'ORDER-001';
        $procedure->expressCompany = '顺丰快递';
        $procedure->expressCode = 'SF';
        $procedure->expressNumber = 'SF' . uniqid();
        $procedure->consigneeName = '张三';
        $procedure->consigneePhone = '13800138000';
        $procedure->consigneeAddress = '上海市浦东新区测试地址';
        $procedure->deliveryItems = [
            [
                'sku' => 'SKU001',
                'quantity' => 1,
                'productName' => '测试产品',
            ],
        ];

        $request = new JsonRpcRequest();
        $request->setMethod('SyncDeliveryInfoFromOms');
        $procedure->__invoke($request);

        /** @var SyncDeliveryInfoFromOms $procedure2 */
        $procedure2 = self::getContainer()->get(SyncDeliveryInfoFromOms::class);
        $procedure2->deliverySn = $sn;
        $procedure2->sourceOrderId = 'ORDER-002';
        $procedure2->expressCompany = '顺丰快递';
        $procedure2->expressCode = 'SF';
        $procedure2->expressNumber = 'SF' . uniqid();
        $procedure2->consigneeName = '李四';
        $procedure2->consigneePhone = '13800138001';
        $procedure2->consigneeAddress = '上海市浦东新区测试地址2';
        $procedure2->deliveryItems = [
            [
                'sku' => 'SKU002',
                'quantity' => 1,
                'productName' => '测试产品2',
            ],
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('发货单号已存在');

        $request2 = new JsonRpcRequest();
        $request2->setMethod('SyncDeliveryInfoFromOms');
        $procedure2->__invoke($request2);
    }

    public function testValidateEmptyDeliveryItems(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);

        $procedure->deliverySn = 'TEST-' . uniqid();
        $procedure->sourceOrderId = 'ORDER-001';
        $procedure->expressCompany = '顺丰快递';
        $procedure->expressCode = 'SF';
        $procedure->expressNumber = 'SF' . uniqid();
        $procedure->consigneeName = '张三';
        $procedure->consigneePhone = '13800138000';
        $procedure->consigneeAddress = '上海市浦东新区测试地址';
        $procedure->deliveryItems = [];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('发货商品列表不能为空');

        $request = new JsonRpcRequest();
        $request->setMethod('SyncDeliveryInfoFromOms');
        $procedure->__invoke($request);
    }

    public function testValidateInvalidDeliveryItems(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);

        $procedure->deliverySn = 'TEST-' . uniqid();
        $procedure->sourceOrderId = 'ORDER-001';
        $procedure->expressCompany = '顺丰快递';
        $procedure->expressCode = 'SF';
        $procedure->expressNumber = 'SF' . uniqid();
        $procedure->consigneeName = '张三';
        $procedure->consigneePhone = '13800138000';
        $procedure->consigneeAddress = '上海市浦东新区测试地址';
        $procedure->deliveryItems = [
            [
                'sku' => '',
                'quantity' => 1,
                'productName' => '测试产品',
            ],
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个商品SKU不能为空');

        $request = new JsonRpcRequest();
        $request->setMethod('SyncDeliveryInfoFromOms');
        $procedure->__invoke($request);
    }

    public function testValidateInvalidQuantity(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);

        $procedure->deliverySn = 'TEST-' . uniqid();
        $procedure->sourceOrderId = 'ORDER-001';
        $procedure->expressCompany = '顺丰快递';
        $procedure->expressCode = 'SF';
        $procedure->expressNumber = 'SF' . uniqid();
        $procedure->consigneeName = '张三';
        $procedure->consigneePhone = '13800138000';
        $procedure->consigneeAddress = '上海市浦东新区测试地址';
        $procedure->deliveryItems = [
            [
                'sku' => 'SKU001',
                'quantity' => 0,
                'productName' => '测试产品',
            ],
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个商品数量必须大于0');

        $request = new JsonRpcRequest();
        $request->setMethod('SyncDeliveryInfoFromOms');
        $procedure->__invoke($request);
    }

    public function testGetMockResult(): void
    {
        $mockResult = SyncDeliveryInfoFromOms::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertTrue($mockResult['success']);
        $this->assertEquals('发货信息同步成功', $mockResult['message']);
        $this->assertEquals('12345', $mockResult['deliveryOrderId']);
    }
}
