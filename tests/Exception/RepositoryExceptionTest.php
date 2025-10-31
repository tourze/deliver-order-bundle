<?php

namespace DeliverOrderBundle\Tests\Exception;

use DeliverOrderBundle\Exception\RepositoryException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(RepositoryException::class)]
final class RepositoryExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeCreated(): void
    {
        $exception = new RepositoryException('Test message');

        $this->assertInstanceOf(RepositoryException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $exception = new RepositoryException('Test message', 500);

        $this->assertEquals(500, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new RepositoryException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
