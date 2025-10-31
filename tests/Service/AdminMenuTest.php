<?php

declare(strict_types=1);

namespace DeliverOrderBundle\Tests\Service;

use DeliverOrderBundle\Service\AdminMenu;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator->method('getCurdListPage')
            ->willReturn('/admin/test')
        ;

        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
    }

    public function testServiceIsCorrectlyDefined(): void
    {
        // 从容器中获取服务实例进行验证
        $adminMenu = self::getContainer()->get(AdminMenu::class);

        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
        $this->assertInstanceOf(MenuProviderInterface::class, $adminMenu);
        $this->assertIsCallable($adminMenu);
    }

    public function testMenuProviderFunctionality(): void
    {
        // 测试菜单提供者的实际功能
        /** @var AdminMenu $adminMenu */
        $adminMenu = self::getContainer()->get(AdminMenu::class);

        // 创建一个模拟的根菜单项
        $rootMenuItem = $this->createMock(ItemInterface::class);

        // 模拟子菜单项
        $deliverMenuItem = $this->createMock(ItemInterface::class);

        // 配置根菜单项的行为：第一次获取子菜单时返回null
        $rootMenuItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('发货管理')
            ->willReturnOnConsecutiveCalls(null, $deliverMenuItem)
        ;

        $rootMenuItem->expects($this->once())
            ->method('addChild')
            ->with('发货管理')
            ->willReturn($deliverMenuItem)
        ;

        // 创建子菜单项的Mock对象
        $childMenuItem1 = $this->createMock(ItemInterface::class);
        $childMenuItem2 = $this->createMock(ItemInterface::class);

        // 配置子菜单项添加子项的行为 - 需要返回可以链式调用的对象
        $deliverMenuItem->expects($this->exactly(2))
            ->method('addChild')
            ->willReturnOnConsecutiveCalls($childMenuItem1, $childMenuItem2)
        ;

        // 配置链式调用
        $childMenuItem1->expects($this->once())
            ->method('setUri')
            ->willReturnSelf()
        ;
        $childMenuItem1->expects($this->once())
            ->method('setAttribute')
            ->willReturnSelf()
        ;

        $childMenuItem2->expects($this->once())
            ->method('setUri')
            ->willReturnSelf()
        ;
        $childMenuItem2->expects($this->once())
            ->method('setAttribute')
            ->willReturnSelf()
        ;

        // 调用菜单提供者 - 使用显式的invoke方法调用避免动态调用警告
        $adminMenu->__invoke($rootMenuItem);

        // 验证方法被正确调用，测试通过说明功能正常
        // 因为 expects() 方法已经进行了断言，这里不需要额外的断言
    }
}
