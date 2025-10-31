<?php

namespace DeliverOrderBundle\Tests\Exception;

use DeliverOrderBundle\Exception\DeliverException;
use DeliverOrderBundle\Exception\InsufficientStockException;
use DeliverOrderBundle\Exception\InvalidSourceException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverException::class)]
final class DeliverExceptionTest extends AbstractExceptionTestCase
{
    public function testDeliverExceptionCanBeThrown(): void
    {
        $this->expectException(InvalidSourceException::class);
        $this->expectExceptionMessage('Test exception message');

        throw new InvalidSourceException('Test exception message');
    }

    public function testDeliverExceptionIsThrowable(): void
    {
        $exception = new InvalidSourceException('Test');

        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(DeliverException::class, $exception);
    }

    public function testInvalidSourceExceptionExtendsDeliverException(): void
    {
        $exception = new InvalidSourceException('Invalid source');

        $this->assertInstanceOf(DeliverException::class, $exception);
        $this->assertInstanceOf(InvalidSourceException::class, $exception);
        $this->assertEquals('Invalid source', $exception->getMessage());
    }

    public function testInsufficientStockExceptionExtendsDeliverException(): void
    {
        $exception = new InsufficientStockException('Not enough stock');

        $this->assertInstanceOf(DeliverException::class, $exception);
        $this->assertInstanceOf(InsufficientStockException::class, $exception);
        $this->assertEquals('Not enough stock', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidSourceException('Main exception', 500, $previous);

        $this->assertEquals('Main exception', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidSourceExceptionDefaultMessage(): void
    {
        $exception = new InvalidSourceException();

        $this->assertEquals('发货单来源无效', $exception->getMessage());
    }

    public function testInsufficientStockExceptionDefaultMessage(): void
    {
        $exception = new InsufficientStockException();

        $this->assertEquals('库存不足', $exception->getMessage());
    }

    public function testExceptionContextSupport(): void
    {
        $exception = new InvalidSourceException('Test with context');

        $exception->setContext(['key1' => 'value1', 'key2' => 'value2']);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $exception->getContext());

        $exception->withContext('key3', 'value3');
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'], $exception->getContext());
    }
}
