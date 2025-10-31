<?php

namespace DeliverOrderBundle\Tests\Model;

use DeliverOrderBundle\Model\DeliverContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DeliverContext::class)]
final class DeliverContextTest extends TestCase
{
    private DeliverContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new DeliverContext();
    }

    public function testContextCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DeliverContext::class, $this->context);
    }

    public function testSourceTypeAndIdGetterSetter(): void
    {
        $this->assertNull($this->context->getSourceType());
        $this->assertNull($this->context->getSourceId());

        $this->context->setSourceType('order');
        $this->context->setSourceId('12345');

        $this->assertEquals('order', $this->context->getSourceType());
        $this->assertEquals('12345', $this->context->getSourceId());
    }

    public function testConsigneeGetterSetter(): void
    {
        $this->assertNull($this->context->getConsignee());

        $consignee = [
            'name' => '张三',
            'phone' => '13800138000',
            'address' => '北京市朝阳区xxx街道',
            'remark' => '请放门卫处',
        ];

        $this->context->setConsignee($consignee);
        $this->assertEquals($consignee, $this->context->getConsignee());

        // Test individual consignee field access
        $this->assertEquals('张三', $this->context->getConsigneeName());
        $this->assertEquals('13800138000', $this->context->getConsigneePhone());
        $this->assertEquals('北京市朝阳区xxx街道', $this->context->getConsigneeAddress());
        $this->assertEquals('请放门卫处', $this->context->getConsigneeRemark());
    }

    public function testConsigneeIndividualFieldsWithEmptyConsignee(): void
    {
        $this->assertNull($this->context->getConsigneeName());
        $this->assertNull($this->context->getConsigneePhone());
        $this->assertNull($this->context->getConsigneeAddress());
        $this->assertNull($this->context->getConsigneeRemark());
    }

    public function testItemsGetterSetter(): void
    {
        $this->assertEmpty($this->context->getItems());

        $items = [
            [
                'sku_id' => 'SKU001',
                'sku_code' => 'ABC123',
                'sku_name' => '商品A',
                'quantity' => 2,
                'batch_no' => 'BATCH001',
                'serial_no' => 'SN001',
                'remark' => '测试商品A',
            ],
            [
                'sku_id' => 'SKU002',
                'sku_code' => 'DEF456',
                'sku_name' => '商品B',
                'quantity' => 1,
            ],
        ];

        $this->context->setItems($items);
        $this->assertEquals($items, $this->context->getItems());
        $this->assertCount(2, $this->context->getItems());
    }

    public function testAddItem(): void
    {
        $this->assertEmpty($this->context->getItems());

        $item1 = [
            'sku_id' => 'SKU001',
            'quantity' => 2,
        ];

        $this->context->addItem($item1);
        $this->assertCount(1, $this->context->getItems());
        $this->assertEquals([$item1], $this->context->getItems());

        $item2 = [
            'sku_id' => 'SKU002',
            'quantity' => 1,
        ];

        $this->context->addItem($item2);
        $this->assertCount(2, $this->context->getItems());
        $this->assertEquals([$item1, $item2], $this->context->getItems());
    }

    public function testExtraDataGetterSetter(): void
    {
        $this->assertEmpty($this->context->getExtra());

        $extra = [
            'user_id' => 'user123',
            'order_amount' => 1000.00,
            'notes' => '紧急订单',
        ];

        $this->context->setExtra($extra);
        $this->assertEquals($extra, $this->context->getExtra());
    }

    public function testGetExtraValue(): void
    {
        $extra = [
            'user_id' => 'user123',
            'order_amount' => 1000.00,
            'nested' => [
                'key' => 'value',
            ],
        ];

        $this->context->setExtra($extra);

        $this->assertEquals('user123', $this->context->getExtraValue('user_id'));
        $this->assertEquals(1000.00, $this->context->getExtraValue('order_amount'));
        $this->assertEquals(['key' => 'value'], $this->context->getExtraValue('nested'));
        $this->assertNull($this->context->getExtraValue('non_existent'));
        $this->assertEquals('default', $this->context->getExtraValue('non_existent', 'default'));
    }

    public function testSetExtraValue(): void
    {
        $this->context->setExtraValue('key1', 'value1');
        $this->context->setExtraValue('key2', 123);

        $this->assertEquals('value1', $this->context->getExtraValue('key1'));
        $this->assertEquals(123, $this->context->getExtraValue('key2'));

        $extra = $this->context->getExtra();
        $this->assertArrayHasKey('key1', $extra);
        $this->assertArrayHasKey('key2', $extra);
    }

    public function testFluentInterface(): void
    {
        // Test all setters can be called individually
        $this->context->setSourceType('order');
        $this->context->setSourceId('12345');
        $this->context->setConsignee(['name' => '张三']);
        $this->context->setItems([['sku_id' => 'SKU001']]);
        $this->context->setExtra(['key' => 'value']);
        $this->context->addItem(['sku_id' => 'SKU002']);
        $this->context->setExtraValue('test', true);

        // Verify all values were set correctly
        $this->assertEquals('order', $this->context->getSourceType());
        $this->assertEquals('12345', $this->context->getSourceId());
        $this->assertEquals(['name' => '张三'], $this->context->getConsignee());
        $this->assertEquals([['sku_id' => 'SKU001'], ['sku_id' => 'SKU002']], $this->context->getItems());
        $this->assertEquals(['key' => 'value', 'test' => true], $this->context->getExtra());
    }

    public function testToArray(): void
    {
        $this->context->setSourceType('order');
        $this->context->setSourceId('12345');
        $this->context->setConsignee([
            'name' => '张三',
            'phone' => '13800138000',
        ]);
        $this->context->setItems([
            ['sku_id' => 'SKU001', 'quantity' => 2],
        ]);
        $this->context->setExtra(['user_id' => 'user123']);

        $array = $this->context->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('order', $array['source_type']);
        $this->assertEquals('12345', $array['source_id']);
        $this->assertEquals(['name' => '张三', 'phone' => '13800138000'], $array['consignee']);
        $this->assertEquals([['sku_id' => 'SKU001', 'quantity' => 2]], $array['items']);
        $this->assertEquals(['user_id' => 'user123'], $array['extra']);
    }

    public function testCreateFromArray(): void
    {
        $data = [
            'source_type' => 'order',
            'source_id' => '12345',
            'consignee' => [
                'name' => '张三',
                'phone' => '13800138000',
            ],
            'items' => [
                ['sku_id' => 'SKU001', 'quantity' => 2],
            ],
            'extra' => ['user_id' => 'user123'],
        ];

        $context = DeliverContext::createFromArray($data);

        $this->assertInstanceOf(DeliverContext::class, $context);
        $this->assertEquals('order', $context->getSourceType());
        $this->assertEquals('12345', $context->getSourceId());
        $this->assertEquals($data['consignee'], $context->getConsignee());
        $this->assertEquals($data['items'], $context->getItems());
        $this->assertEquals($data['extra'], $context->getExtra());
    }

    public function testValidation(): void
    {
        // Test empty context validation
        $this->assertFalse($this->context->isValid());
        $errors = $this->context->getValidationErrors();
        $this->assertContains('Source type is required', $errors);
        $this->assertContains('Source ID is required', $errors);
        $this->assertContains('Items cannot be empty', $errors);

        // Test with valid data
        $this->context->setSourceType('order');
        $this->context->setSourceId('12345');
        $this->context->addItem(['sku_id' => 'SKU001', 'quantity' => 1]);

        $this->assertTrue($this->context->isValid());
        $this->assertEmpty($this->context->getValidationErrors());

        // Test invalid item
        $this->context->setItems([['quantity' => 1]]); // Missing sku_id
        $this->assertFalse($this->context->isValid());
        $errors = $this->context->getValidationErrors();
        $this->assertContains('Item 0: SKU ID is required', $errors);

        // Test invalid quantity
        $this->context->setItems([['sku_id' => 'SKU001', 'quantity' => 0]]);
        $this->assertFalse($this->context->isValid());
        $errors = $this->context->getValidationErrors();
        $this->assertContains('Item 0: Quantity must be greater than 0', $errors);
    }

    public function testSanitizeItem(): void
    {
        $item = [
            'sku_id' => 'SKU001',
            'sku_code' => 'ABC123',
            'quantity' => '5',
            'extra_field' => 'should be ignored',
        ];

        $sanitized = $this->context->sanitizeItem($item);

        $this->assertEquals('SKU001', $sanitized['sku_id']);
        $this->assertEquals('ABC123', $sanitized['sku_code']);
        $this->assertSame(5, $sanitized['quantity']);
        $this->assertNull($sanitized['sku_name']);
        $this->assertNull($sanitized['batch_no']);
        $this->assertNull($sanitized['serial_no']);
        $this->assertNull($sanitized['remark']);
        $this->assertArrayNotHasKey('extra_field', $sanitized);
    }

    public function testGetTotalQuantity(): void
    {
        $this->assertEquals(0, $this->context->getTotalQuantity());

        $this->context->setItems([
            ['sku_id' => 'SKU001', 'quantity' => 3],
            ['sku_id' => 'SKU002', 'quantity' => 2],
            ['sku_id' => 'SKU003'], // Default quantity = 1
        ]);

        $this->assertEquals(6, $this->context->getTotalQuantity());
    }

    public function testHasConsignee(): void
    {
        $this->assertFalse($this->context->hasConsignee());

        $this->context->setConsignee(['phone' => '13800138000']);
        $this->assertFalse($this->context->hasConsignee());

        $this->context->setConsignee(['name' => '张三']);
        $this->assertTrue($this->context->hasConsignee());
    }

    public function testClear(): void
    {
        $this->context->setSourceType('order');
        $this->context->setSourceId('12345');
        $this->context->setConsignee(['name' => '张三']);
        $this->context->setItems([['sku_id' => 'SKU001']]);
        $this->context->setExtra(['key' => 'value']);

        $this->context->clear();

        $this->assertNull($this->context->getSourceType());
        $this->assertNull($this->context->getSourceId());
        $this->assertNull($this->context->getConsignee());
        $this->assertEmpty($this->context->getItems());
        $this->assertEmpty($this->context->getExtra());
    }
}
