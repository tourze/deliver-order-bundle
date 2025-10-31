<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Controller\Admin;

use DeliverOrderBundle\Controller\Admin\DeliverOrderCrudController;
use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(DeliverOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
final class DeliverOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): DeliverOrderCrudController
    {
        return self::getService(DeliverOrderCrudController::class);
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '发货单号' => ['发货单号'];
        yield '来源类型' => ['来源类型'];
        yield '来源ID' => ['来源ID'];
        yield '快递公司' => ['快递公司'];
        yield '快递编码' => ['快递编码'];
        yield '快递单号' => ['快递单号'];
        yield '收货人' => ['收货人'];
        yield '联系电话' => ['联系电话'];
        yield '状态' => ['状态'];
        yield '发货商品' => ['发货商品'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'sn' => ['sn'];
        yield 'sourceType' => ['sourceType'];
        yield 'sourceId' => ['sourceId'];
        yield 'status' => ['status'];
        yield 'expressCompany' => ['expressCompany'];
        yield 'consigneeName' => ['consigneeName'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        yield 'sn' => ['sn'];
        yield 'sourceType' => ['sourceType'];
        yield 'sourceId' => ['sourceId'];
        yield 'status' => ['status'];
        yield 'expressCompany' => ['expressCompany'];
        yield 'consigneeName' => ['consigneeName'];
    }

    /**
     * 测试字段配置而非页面渲染（避免EasyAdmin客户端问题）
     */
    public function testFieldConfiguration(): void
    {
        $controller = $this->getControllerService();

        // 验证各个页面的字段配置
        foreach ([Crud::PAGE_INDEX, Crud::PAGE_NEW, Crud::PAGE_EDIT] as $page) {
            $fields = $controller->configureFields($page);
            $this->assertInstanceOf(\Generator::class, $fields);

            $fieldNames = [];
            foreach ($fields as $field) {
                if (is_string($field)) {
                    $fieldNames[] = $field;
                } else {
                    $fieldNames[] = $field->getAsDto()->getProperty();
                }
            }

            // 基本字段应该在所有页面都存在
            $this->assertContains('sn', $fieldNames, "Field 'sn' should be configured for {$page} page");
        }
    }

    public function testControllerBasicFunctionality(): void
    {
        $controller = $this->getControllerService();

        // 测试基本配置
        $this->assertInstanceOf(DeliverOrderCrudController::class, $controller);
        $this->assertEquals(DeliverOrder::class, $controller::getEntityFqcn());

        // 测试CRUD配置
        $crud = $controller->configureCrud(Crud::new());
        $this->assertInstanceOf(Crud::class, $crud);

        // 测试过滤器配置
        $filters = $controller->configureFilters(Filters::new());
        $this->assertInstanceOf(Filters::class, $filters);
    }

    public function testControllerInstantiation(): void
    {
        // 测试直接实例化控制器
        $controller = new DeliverOrderCrudController();

        $this->assertEquals(DeliverOrder::class, $controller::getEntityFqcn());

        // 测试字段配置生成器
        $indexFields = $controller->configureFields(Crud::PAGE_INDEX);
        $this->assertInstanceOf(\Generator::class, $indexFields);

        $newFields = $controller->configureFields(Crud::PAGE_NEW);
        $this->assertInstanceOf(\Generator::class, $newFields);

        $editFields = $controller->configureFields(Crud::PAGE_EDIT);
        $this->assertInstanceOf(\Generator::class, $editFields);
    }

    public function testEntityBasicProperties(): void
    {
        // 测试实体的基本属性设置
        $deliverOrder = new DeliverOrder();

        // 测试基本属性设置
        $deliverOrder->setSn('TEST-ORDER-123');
        $this->assertEquals('TEST-ORDER-123', $deliverOrder->getSn());

        $deliverOrder->setSourceType(SourceType::OTHER);
        $this->assertEquals(SourceType::OTHER, $deliverOrder->getSourceType());

        $deliverOrder->setSourceId('test-source-1');
        $this->assertEquals('test-source-1', $deliverOrder->getSourceId());

        $deliverOrder->setExpressCompany('顺丰速运');
        $this->assertEquals('顺丰速运', $deliverOrder->getExpressCompany());

        $deliverOrder->setStatus(DeliverOrderStatus::PENDING);
        $this->assertEquals(DeliverOrderStatus::PENDING, $deliverOrder->getStatus());
    }

    public function testEntityRelationships(): void
    {
        $deliverOrder = new DeliverOrder();

        // 测试集合初始化
        $this->assertCount(0, $deliverOrder->getDeliverStocks());

        // 测试字符串转换
        $deliverOrder->setSn('TEST-STRING');
        $this->assertEquals('TEST-STRING', (string) $deliverOrder);
    }

    public function testControllerFieldCoverage(): void
    {
        $controller = $this->getControllerService();

        // 测试INDEX页面必须包含的关键字段
        $indexFields = $controller->configureFields(Crud::PAGE_INDEX);
        $indexFieldNames = [];
        foreach ($indexFields as $field) {
            if (is_string($field)) {
                $indexFieldNames[] = $field;
            } else {
                $indexFieldNames[] = $field->getAsDto()->getProperty();
            }
        }

        $requiredIndexFields = ['id', 'sn', 'sourceType', 'status', 'createTime'];
        foreach ($requiredIndexFields as $requiredField) {
            $this->assertContains(
                $requiredField,
                $indexFieldNames,
                "INDEX page should include field: {$requiredField}"
            );
        }

        // 测试NEW页面字段
        $newFields = $controller->configureFields(Crud::PAGE_NEW);
        $newFieldNames = [];
        foreach ($newFields as $field) {
            if (is_string($field)) {
                $newFieldNames[] = $field;
            } else {
                $newFieldNames[] = $field->getAsDto()->getProperty();
            }
        }

        $requiredNewFields = ['sn', 'sourceType', 'sourceId', 'status'];
        foreach ($requiredNewFields as $requiredField) {
            $this->assertContains(
                $requiredField,
                $newFieldNames,
                "NEW page should include field: {$requiredField}"
            );
        }
    }
}
