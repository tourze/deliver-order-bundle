<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Procedure;

use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Param\SyncDeliveryInfoFromOmsParam;
use DeliverOrderBundle\Procedure\SyncDeliveryInfoFromOms;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

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

        $deliverySn = 'TEST-EXECUTE-' . uniqid();
        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: $deliverySn,
            sourceOrderId: 'ORDER-001',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF' . uniqid(),
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '上海市浦东新区测试地址',
            deliveryItems: [
                [
                    'sku' => 'SKU001',
                    'quantity' => 1,
                    'productName' => '测试产品',
                ],
            ]
        );

        $result = $procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertTrue($resultArray['success']);
        $this->assertEquals('发货信息同步成功', $resultArray['message']);
        $this->assertArrayHasKey('deliveryOrderId', $resultArray);

        $deliverOrder = $deliverOrderRepository->find($resultArray['deliveryOrderId']);
        $this->assertNotNull($deliverOrder);
        $this->assertEquals($deliverySn, $deliverOrder->getSn());
    }

    public function testSyncDeliveryInfoFromOmsSuccess(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);
        /** @var DeliverOrderRepository $deliverOrderRepository */
        $deliverOrderRepository = self::getContainer()->get(DeliverOrderRepository::class);

        $deliverySn = 'TEST-' . uniqid();
        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: $deliverySn,
            sourceOrderId: 'ORDER-001',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF' . uniqid(),
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '上海市浦东新区测试地址',
            consigneeRemark: '请轻拿轻放',
            shippedTime: '2024-01-01 10:00:00',
            shippedBy: 'OMS操作员',
            deliveryItems: [
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
            ]
        );

        $result = $procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertTrue($resultArray['success']);
        $this->assertEquals('发货信息同步成功', $resultArray['message']);
        $this->assertArrayHasKey('deliveryOrderId', $resultArray);

        $deliverOrder = $deliverOrderRepository->find($resultArray['deliveryOrderId']);
        $this->assertNotNull($deliverOrder);
        $this->assertEquals($deliverySn, $deliverOrder->getSn());
        $this->assertEquals(SourceType::OMS, $deliverOrder->getSourceType());
        $this->assertEquals(DeliverOrderStatus::SHIPPED, $deliverOrder->getStatus());
        $this->assertCount(2, $deliverOrder->getDeliverStocks());
    }

    public function testSyncDeliveryInfoFromOmsDuplicateSn(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);
        $sn = 'TEST-' . uniqid();

        $param1 = new SyncDeliveryInfoFromOmsParam(
            deliverySn: $sn,
            sourceOrderId: 'ORDER-001',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF' . uniqid(),
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '上海市浦东新区测试地址',
            deliveryItems: [
                [
                    'sku' => 'SKU001',
                    'quantity' => 1,
                    'productName' => '测试产品',
                ],
            ]
        );

        $procedure->execute($param1);

        $param2 = new SyncDeliveryInfoFromOmsParam(
            deliverySn: $sn,
            sourceOrderId: 'ORDER-002',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF' . uniqid(),
            consigneeName: '李四',
            consigneePhone: '13800138001',
            consigneeAddress: '上海市浦东新区测试地址2',
            deliveryItems: [
                [
                    'sku' => 'SKU002',
                    'quantity' => 1,
                    'productName' => '测试产品2',
                ],
            ]
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('发货单号已存在');

        $procedure->execute($param2);
    }

    public function testValidateEmptyDeliveryItems(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);

        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: 'TEST-' . uniqid(),
            sourceOrderId: 'ORDER-001',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF' . uniqid(),
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '上海市浦东新区测试地址',
            deliveryItems: []
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('发货商品列表不能为空');

        $procedure->execute($param);
    }

    public function testValidateInvalidDeliveryItems(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);

        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: 'TEST-' . uniqid(),
            sourceOrderId: 'ORDER-001',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF' . uniqid(),
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '上海市浦东新区测试地址',
            deliveryItems: [
                [
                    'sku' => '',
                    'quantity' => 1,
                    'productName' => '测试产品',
                ],
            ]
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个商品SKU不能为空');

        $procedure->execute($param);
    }

    public function testValidateInvalidQuantity(): void
    {
        /** @var SyncDeliveryInfoFromOms $procedure */
        $procedure = self::getContainer()->get(SyncDeliveryInfoFromOms::class);

        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: 'TEST-' . uniqid(),
            sourceOrderId: 'ORDER-001',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF' . uniqid(),
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '上海市浦东新区测试地址',
            deliveryItems: [
                [
                    'sku' => 'SKU001',
                    'quantity' => 0,
                    'productName' => '测试产品',
                ],
            ]
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个商品数量必须大于0');

        $procedure->execute($param);
    }
}
