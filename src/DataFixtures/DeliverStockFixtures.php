<?php

namespace DeliverOrderBundle\DataFixtures;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Entity\DeliverStock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DeliverStockFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $deliverOrder = $this->getReference(DeliverOrderFixtures::DELIVER_ORDER_TEST_REFERENCE, DeliverOrder::class);
        assert($deliverOrder instanceof DeliverOrder);

        $stock = new DeliverStock();
        $stock->setDeliverOrder($deliverOrder);
        $stock->setSkuId('SKU-001');
        $stock->setSkuCode('TEST-SKU');
        $stock->setSkuName('Test Product');
        $stock->setQuantity(10);
        $stock->setBatchNo('BATCH-001');
        $stock->setReceived(false);

        $manager->persist($stock);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            DeliverOrderFixtures::class,
        ];
    }
}
