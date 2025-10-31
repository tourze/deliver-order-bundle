<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Exception;

use DeliverOrderBundle\Exception\DeliverException;
use DeliverOrderBundle\Exception\DeliverOperationException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverOperationException::class)]
final class DeliverOperationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromDeliverException(): void
    {
        $exception = new DeliverOperationException();
        $this->assertInstanceOf(DeliverException::class, $exception);
    }

    public function testDefaultConstructor(): void
    {
        $exception = new DeliverOperationException();

        $this->assertEquals('发货操作失败', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $message = '自定义发货操作失败消息';
        $exception = new DeliverOperationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = '完整的发货操作失败消息';
        $code = 500;
        $previous = new \RuntimeException('Previous exception');

        $exception = new DeliverOperationException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionIsThrowable(): void
    {
        $this->expectException(DeliverOperationException::class);
        $this->expectExceptionMessage('测试异常抛出');

        throw new DeliverOperationException('测试异常抛出');
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new DeliverOperationException('Stack trace test');

        $this->assertIsString($exception->getTraceAsString());
        $this->assertIsArray($exception->getTrace());
        $this->assertStringContainsString(__FILE__, $exception->getFile());
        $this->assertIsInt($exception->getLine());
    }

    public function testExceptionToString(): void
    {
        $message = '异常转字符串测试';
        $exception = new DeliverOperationException($message);

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString('DeliverOperationException', $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
    }
}
