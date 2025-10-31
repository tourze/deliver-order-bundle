<?php

namespace DeliverOrderBundle\DataFixtures;

use DeliverOrderBundle\Entity\DeliverOrder;
use DeliverOrderBundle\Enum\DeliverOrderStatus;
use DeliverOrderBundle\Enum\SourceType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeliverOrderFixtures extends Fixture
{
    public const DELIVER_ORDER_TEST_REFERENCE = 'deliver-order-test';

    public function load(ObjectManager $manager): void
    {
        $entity = new DeliverOrder();
        $entity->setSn('DO-TEST-001');
        $entity->setSourceType(SourceType::OTHER);
        $entity->setSourceId('TEST-001');
        $entity->setStatus(DeliverOrderStatus::PENDING);
        $entity->setCreatedBy('test');
        $entity->setUpdatedBy('test');

        $manager->persist($entity);
        $manager->flush();

        $this->addReference(self::DELIVER_ORDER_TEST_REFERENCE, $entity);
    }
}
