<?php

namespace DeliverOrderBundle\Service;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

/**
 * 发货管理菜单服务
 */
#[Autoconfigure(public: true)]
class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private readonly LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('发货管理')) {
            $item->addChild('发货管理');
        }

        $deliverMenu = $item->getChild('发货管理');
        if (null === $deliverMenu) {
            return;
        }

        // 发货单管理菜单
        $deliverMenu->addChild('发货单管理')
            ->setUri($this->linkGenerator->getCurdListPage(DeliverOrder::class))
            ->setAttribute('icon', 'fas fa-truck')
        ;

        // 发货商品管理菜单
        $deliverMenu->addChild('发货商品管理')
            ->setUri($this->linkGenerator->getCurdListPage(DeliverStock::class))
            ->setAttribute('icon', 'fas fa-boxes')
        ;
    }
}
