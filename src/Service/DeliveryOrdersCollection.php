<?php

namespace DeliverOrderBundle\Service;

use DeliverOrderBundle\DTO\ExtendedDeliveryOrderDTO;
use DeliverOrderBundle\Entity\DeliverOrder;
use OrderCoreBundle\DTO\DeliveryStockDTO;

class DeliveryOrdersCollection
{
    public function convertToExtendedDTO(DeliverOrder $entity): ExtendedDeliveryOrderDTO
    {
        $deliverStocks = $this->convertDeliverStocksToDTO($entity);

        $dto = new ExtendedDeliveryOrderDTO();
        $dto->setId((string) $entity->getId());
        $dto->setExpressCompany($entity->getExpressCompany());
        $dto->setExpressNumber($entity->getExpressNumber());
        $dto->setSn($entity->getSn());
        $dto->setSourceType($entity->getSourceType()->value ?? 'unknown');
        $dto->setSourceId($entity->getSourceId());
        $dto->setShippedTime($entity->getShippedTime());
        $dto->setDeliverStocks($deliverStocks);

        return $dto;
    }

    /**
     * @param ExtendedDeliveryOrderDTO[] $deliveryOrders
     */
    public function countShippedOrders(array $deliveryOrders): int
    {
        $shippedCount = 0;
        foreach ($deliveryOrders as $order) {
            if (null !== $order->getShippedTime()) {
                ++$shippedCount;
            }
        }

        return $shippedCount;
    }

    /**
     * @return DeliveryStockDTO[]
     */
    private function convertDeliverStocksToDTO(DeliverOrder $entity): array
    {
        $deliverStocks = [];
        foreach ($entity->getDeliverStocks() as $stock) {
            // 确保ID和SKU代码不为null，否则跳过该记录
            $id = $stock->getId();
            $skuCode = $stock->getSkuCode();

            if (null === $id || null === $skuCode) {
                continue;
            }

            $deliverStocks[] = new DeliveryStockDTO(
                $id,
                $skuCode,
                $stock->getQuantity(),
                $stock->isReceived() ? 'received' : 'pending'
            );
        }

        return $deliverStocks;
    }
}
