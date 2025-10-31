<?php

namespace DeliverOrderBundle\Tests\Exception;

use DeliverOrderBundle\Exception\InsufficientStockException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InsufficientStockException::class)]
final class InsufficientStockExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new InsufficientStockException('库存不足');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('库存不足', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('原始异常');
        $exception = new InsufficientStockException('库存不足', 1001, $previous);

        $this->assertEquals('库存不足', $exception->getMessage());
        $this->assertEquals(1001, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
