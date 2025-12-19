<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Param;

use DeliverOrderBundle\Param\SyncDeliveryInfoFromOmsParam;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * SyncDeliveryInfoFromOmsParam 单元测试
 *
 * @internal
 */
#[CoversClass(SyncDeliveryInfoFromOmsParam::class)]
final class SyncDeliveryInfoFromOmsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: 'TEST-001',
            sourceOrderId: 'ORDER-001',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF123456',
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '测试地址',
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithRequiredParametersOnly(): void
    {
        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: 'TEST-002',
            sourceOrderId: 'ORDER-002',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF654321',
            consigneeName: '李四',
            consigneePhone: '13900139000',
            consigneeAddress: '上海市浦东新区',
        );

        $this->assertSame('TEST-002', $param->deliverySn);
        $this->assertSame('ORDER-002', $param->sourceOrderId);
        $this->assertSame('顺丰快递', $param->expressCompany);
        $this->assertSame('SF', $param->expressCode);
        $this->assertSame('SF654321', $param->expressNumber);
        $this->assertSame('李四', $param->consigneeName);
        $this->assertSame('13900139000', $param->consigneePhone);
        $this->assertSame('上海市浦东新区', $param->consigneeAddress);
        $this->assertNull($param->consigneeRemark);
        $this->assertNull($param->shippedTime);
        $this->assertNull($param->shippedBy);
        $this->assertSame([], $param->deliveryItems);
    }

    public function testConstructorWithAllParameters(): void
    {
        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: 'TEST-003',
            sourceOrderId: 'ORDER-003',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF789012',
            consigneeName: '王五',
            consigneePhone: '13700137000',
            consigneeAddress: '北京市朝阳区',
            consigneeRemark: '请轻拿轻放',
            shippedTime: '2024-01-01 10:00:00',
            shippedBy: 'OMS操作员',
            deliveryItems: [
                [
                    'sku' => 'SKU001',
                    'quantity' => 2,
                    'productName' => '测试产品',
                ],
            ],
        );

        $this->assertSame('TEST-003', $param->deliverySn);
        $this->assertSame('请轻拿轻放', $param->consigneeRemark);
        $this->assertSame('2024-01-01 10:00:00', $param->shippedTime);
        $this->assertSame('OMS操作员', $param->shippedBy);
        $this->assertCount(1, $param->deliveryItems);
        $this->assertSame('SKU001', $param->deliveryItems[0]['sku']);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(SyncDeliveryInfoFromOmsParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testPropertiesArePublicReadonly(): void
    {
        $reflection = new \ReflectionClass(SyncDeliveryInfoFromOmsParam::class);

        $properties = [
            'deliverySn',
            'sourceOrderId',
            'expressCompany',
            'expressCode',
            'expressNumber',
            'consigneeName',
            'consigneePhone',
            'consigneeAddress',
            'consigneeRemark',
            'shippedTime',
            'shippedBy',
            'deliveryItems',
        ];

        foreach ($properties as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue($property->isPublic(), "{$propertyName} should be public");
            $this->assertTrue($property->isReadOnly(), "{$propertyName} should be readonly");
        }
    }

    public function testValidationFailsWhenRequiredFieldsAreBlank(): void
    {
        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: '',
            sourceOrderId: '',
            expressCompany: '',
            expressCode: '',
            expressNumber: '',
            consigneeName: '',
            consigneePhone: '',
            consigneeAddress: '',
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($param);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationPassesWithValidParameters(): void
    {
        $param = new SyncDeliveryInfoFromOmsParam(
            deliverySn: 'VALID-001',
            sourceOrderId: 'ORDER-VALID',
            expressCompany: '顺丰快递',
            expressCode: 'SF',
            expressNumber: 'SF123456789',
            consigneeName: '张三',
            consigneePhone: '13800138000',
            consigneeAddress: '上海市浦东新区测试路123号',
            deliveryItems: [
                [
                    'sku' => 'SKU001',
                    'quantity' => 1,
                    'productName' => '测试产品',
                ],
            ],
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($param);

        $this->assertCount(0, $violations);
    }

    public function testHasMethodParamAttributes(): void
    {
        $reflection = new \ReflectionClass(SyncDeliveryInfoFromOmsParam::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $attrs = $parameter->getAttributes(\Tourze\JsonRPC\Core\Attribute\MethodParam::class);
            $this->assertNotEmpty($attrs, "Parameter {$parameter->getName()} should have MethodParam attribute");
        }
    }
}
