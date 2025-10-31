<?php

namespace DeliverOrderBundle\Tests\Exception;

use DeliverOrderBundle\Exception\InvalidSourceException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidSourceException::class)]
final class InvalidSourceExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new InvalidSourceException('无效的来源');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('无效的来源', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('原始异常');
        $exception = new InvalidSourceException('无效的来源', 2001, $previous);

        $this->assertEquals('无效的来源', $exception->getMessage());
        $this->assertEquals(2001, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
