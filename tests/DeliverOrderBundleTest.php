<?php

namespace DeliverOrderBundle\Tests;

use DeliverOrderBundle\DeliverOrderBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverOrderBundle::class)]
#[RunTestsInSeparateProcesses]
final class DeliverOrderBundleTest extends AbstractBundleTestCase
{
    public function testBundleHasPath(): void
    {
        $kernel = self::getService(Kernel::class);
        $bundle = $kernel->getBundle('DeliverOrderBundle');
        $this->assertStringContainsString('deliver-order-bundle', $bundle->getPath());
    }

    public function testLoadExtension(): void
    {
        $kernel = self::getService(Kernel::class);
        $bundle = $kernel->getBundle('DeliverOrderBundle');
        $this->assertNotNull($bundle->getContainerExtension());
    }

    public function testBuild(): void
    {
        $kernel = self::getService(Kernel::class);
        $bundle = $kernel->getBundle('DeliverOrderBundle');
        $container = new ContainerBuilder();

        $bundle->build($container);

        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
}
