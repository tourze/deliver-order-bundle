<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Controller\Admin;

use DeliverOrderBundle\Controller\Admin\DeliverStockCrudController;
use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use DeliverOrderBundle\Repository\DeliverOrderRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverStockCrudController::class)]
#[RunTestsInSeparateProcesses]
final class DeliverStockCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): DeliverStockCrudController
    {
        $controller = self::getService(DeliverStockCrudController::class);
        self::assertInstanceOf(DeliverStockCrudController::class, $controller);

        return $controller;
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id' => ['ID'];
        yield 'deliverOrder' => ['发货单'];
        yield 'skuId' => ['SKU ID'];
        yield 'skuCode' => ['SKU编码'];
        yield 'skuName' => ['SKU名称'];
        yield 'quantity' => ['数量'];
        yield 'received' => ['是否已收货'];
        yield 'createTime' => ['创建时间'];
        yield 'updatedAt' => ['更新时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'deliverOrder' => ['deliverOrder'];
        yield 'skuId' => ['skuId'];
        yield 'skuCode' => ['skuCode'];
        yield 'skuName' => ['skuName'];
        yield 'quantity' => ['quantity'];
        yield 'batchNo' => ['batchNo'];
        yield 'serialNo' => ['serialNo'];
        yield 'received' => ['received'];
        yield 'remark' => ['remark'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideDetailPageFields(): iterable
    {
        yield 'deliverOrder' => ['deliverOrder'];
        yield 'skuId' => ['skuId'];
        yield 'skuCode' => ['skuCode'];
        yield 'skuName' => ['skuName'];
        yield 'quantity' => ['quantity'];
        yield 'batchNo' => ['batchNo'];
        yield 'serialNo' => ['serialNo'];
        yield 'received' => ['received'];
        yield 'remark' => ['remark'];
        yield 'receivedTime' => ['receivedTime'];
        yield 'createTime' => ['createTime'];
        yield 'updatedAt' => ['updatedAt'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        yield 'deliverOrder' => ['deliverOrder'];
        yield 'skuId' => ['skuId'];
        yield 'skuCode' => ['skuCode'];
        yield 'skuName' => ['skuName'];
        yield 'quantity' => ['quantity'];
        yield 'batchNo' => ['batchNo'];
        yield 'serialNo' => ['serialNo'];
        yield 'received' => ['received'];
        yield 'remark' => ['remark'];
    }

    public function testDashboardAccessible(): void
    {
        $client = self::createAuthenticatedClient();

        $crawler = $client->request('GET', '/admin');

        // 设置静态客户端以支持响应断言
        self::getClient($client);
        $this->assertResponseIsSuccessful();

        // Test navigation to CRUD controller if link exists
        $link = $crawler->filter('a[href*="DeliverStockCrudController"]')->first();
        if ($link->count() > 0) {
            $client->click($link->link());
            $this->assertResponseIsSuccessful();
        }
    }

    public function testDeliverStockCreation(): void
    {
        $client = self::createClientWithDatabase();

        // Create parent deliver order
        $deliverOrder = new DeliverOrder();
        $deliverOrder->setSn('PARENT-ORDER-' . uniqid());
        $deliverOrder->setSourceType(SourceType::OTHER);
        $deliverOrder->setSourceId('parent-source');
        $deliverOrder->setExpressCompany('顺丰速运');
        $deliverOrder->setExpressCode('SF888888');
        $deliverOrder->setConsigneeName('测试收件人');
        $deliverOrder->setConsigneePhone('13700137000');
        $deliverOrder->setConsigneeAddress('测试地址');
        $deliverOrder->setStatus(DeliverOrderStatus::PENDING);

        // Create deliver stock
        $stock = new DeliverStock();
        $stock->setDeliverOrder($deliverOrder);
        $stock->setSkuId('SKU-TEST-001');
        $stock->setSkuCode('TEST-PRODUCT-001');
        $stock->setSkuName('测试商品001');
        $stock->setQuantity(10);
        $stock->setBatchNo('BATCH-TEST-001');
        $stock->setSerialNo('SERIAL-001');
        $stock->setRemark('测试备注');
        $stock->setReceived(false);

        $deliverOrder->addDeliverStock($stock);

        $repository = self::getService(DeliverOrderRepository::class);
        self::assertInstanceOf(DeliverOrderRepository::class, $repository);
        $repository->save($deliverOrder, true);

        // Verify stock was created
        $savedOrder = $repository->findOneBy(['sn' => $deliverOrder->getSn()]);
        $this->assertNotNull($savedOrder);
        $this->assertCount(1, $savedOrder->getDeliverStocks());

        $savedStock = $savedOrder->getDeliverStocks()->first();
        $this->assertNotFalse($savedStock);
        $this->assertEquals('TEST-PRODUCT-001', $savedStock->getSkuCode());
        $this->assertEquals(10, $savedStock->getQuantity());
        $this->assertFalse($savedStock->isReceived());
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        // Test unauthenticated access to admin panel
        $this->expectException(AccessDeniedException::class);
        $client->request('GET', '/admin');
    }

    public function testControllerConfiguration(): void
    {
        $controller = new DeliverStockCrudController();

        // Test entity configuration
        $this->assertEquals(DeliverStock::class, $controller::getEntityFqcn());

        // Test CRUD configuration
        $crud = $controller->configureCrud(Crud::new());
        $this->assertInstanceOf(Crud::class, $crud);

        // Test fields configuration
        $fields = $controller->configureFields(Crud::PAGE_INDEX);
        $this->assertInstanceOf(\Generator::class, $fields);

        // Test filters configuration
        $filters = $controller->configureFilters(Filters::new());
        $this->assertInstanceOf(Filters::class, $filters);
    }
}
