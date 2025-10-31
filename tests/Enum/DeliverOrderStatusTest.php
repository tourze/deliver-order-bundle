<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Enum;

use DeliverOrderBundle\Enum\DeliverOrderStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverOrderStatus::class)]
final class DeliverOrderStatusTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $cases = DeliverOrderStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(DeliverOrderStatus::PENDING, $cases);
        $this->assertContains(DeliverOrderStatus::SHIPPED, $cases);
        $this->assertContains(DeliverOrderStatus::RECEIVED, $cases);
        $this->assertContains(DeliverOrderStatus::REJECTED, $cases);
    }

    public function testToArray(): void
    {
        $item = DeliverOrderStatus::PENDING->toArray();

        $this->assertIsArray($item);
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('label', $item);
        $this->assertEquals('pending', $item['value']);
        $this->assertEquals('待发货', $item['label']);
    }

    public function testGenOptions(): void
    {
        $options = DeliverOrderStatus::genOptions();

        $this->assertIsArray($options);
        $this->assertCount(4, $options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('text', $option);
            $this->assertArrayHasKey('name', $option);
        }
    }

    public function testEnumComparison(): void
    {
        $status1 = DeliverOrderStatus::PENDING;
        $status2 = DeliverOrderStatus::from('pending');

        $this->assertSame($status1, $status2);
        $this->assertNotEquals($status1, DeliverOrderStatus::SHIPPED);
    }
}
