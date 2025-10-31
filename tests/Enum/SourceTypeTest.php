<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Enum;

use DeliverOrderBundle\Enum\SourceType;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(SourceType::class)]
final class SourceTypeTest extends AbstractEnumTestCase
{
    public function testEnumCases(): void
    {
        $cases = SourceType::cases();

        $this->assertCount(6, $cases);
        $this->assertContains(SourceType::ORDER, $cases);
        $this->assertContains(SourceType::CONTRACT, $cases);
        $this->assertContains(SourceType::AFTERSALES, $cases);
        $this->assertContains(SourceType::REPLENISHMENT, $cases);
        $this->assertContains(SourceType::OMS, $cases);
        $this->assertContains(SourceType::OTHER, $cases);
    }

    public function testToArray(): void
    {
        $item = SourceType::ORDER->toArray();

        $this->assertIsArray($item);
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('label', $item);
        $this->assertEquals('order', $item['value']);
        $this->assertEquals('订单', $item['label']);
    }

    public function testGenOptions(): void
    {
        $options = SourceType::genOptions();

        $this->assertIsArray($options);
        $this->assertCount(6, $options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('text', $option);
            $this->assertArrayHasKey('name', $option);
        }
    }

    public function testEnumComparison(): void
    {
        $type1 = SourceType::ORDER;
        $type2 = SourceType::from('order');

        $this->assertSame($type1, $type2);
        $this->assertNotEquals($type1, SourceType::AFTERSALES);
    }
}
